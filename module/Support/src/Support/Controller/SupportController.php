<?php

namespace Support\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Where;
use Zend\Session\Container as SessionContainer;
use Support\Form\SubmitTicketForm;
use Support\Form\GuestSupportForm;
use Support\Model\SupportTickets;
use Support\Model\TicketMessages;
use KpasteCore\EmailSender;

class SupportController extends AbstractActionController 
{
    public $authData;
    public $acl;
    public $settings;
    
    public function __construct() 
    {
        $auth           = new AuthenticationService();
        $this->authData = unserialize( $auth->getStorage()->read() );
        $settings       = new SessionContainer( 'settings' );
        $this->settings = $settings->settings;
        $session        = new SessionContainer( 'acl' );
        $this->acl      = $session->acl;
    }    
    
    public function indexAction() 
    {
        return $this->redirect()->toRoute('support', array('action' => 'ViewTickets'));
    }
    
    public function SubmitTicketAction()
    {
        if( $this->authData && $this->authData->userid )
        {
            $attachmentSize = (int)$this->settings['max-attachment-size'];
            $departments = $this->getDepartmentsTable()->fetchDepartments();
            $form = new SubmitTicketForm();
            $recipient = null;
            
            //if this is an admin opening the ticket, we should set the target user(paster or advertiser)
            if( $this->authData->user_type == 'admin' || $this->authData->user_type == 'masteradmin' )
            {
                $recipientid = (int)$this->params()->fromRoute('param1');
                $recipient = $this->getUsersTable()->fetchUser( 'userid', $recipientid );
                if( !$recipient )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'RECIPIENT_NOT_SPECIFIED',
                    ) );
                }
            }
            
            $request = $this->getRequest();
            if( $request->isPost() )
            {
                //merge post and files
                $post = array_merge( 
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );
                $form->setData( $request->getPost() );
                if( $form->isValid() )
                {
                    $ticket = new SupportTickets();
                    $message= new TicketMessages();
                    
                    $data = $form->getData();
                    
                    $ticket->exchangeArray ( $data );
                    $message->exchangeArray( $data );
                    
                    $file = $post['attachment'];

                    if( $file['name'] )
                    {
                        if( $file['error'] )
                        {
                            return( array(
                                'error'     => true,
                                'result'    => 'ATTACHMENT_UPLOAD_FAILED',
                                'form'      => $form,
                                'recipient' => $recipient,
                                'attachmentSize'=>$attachmentSize,
                                'departments'=>$departments,
                            ) );
                        }

                        if((int)$file['size'] > (int)$attachmentSize)
                        {
                            return( array(
                                'error'     => true,
                                'result'    => 'ATTACHMENT_SIZE_LIMIT_EXCEEDED',
                                'form'      => $form,
                                'recipient' => $recipient,
                                'attachmentSize'=>$attachmentSize,
                                'departments'=>$departments,
                            ) );
                        }

                        if(!in_array($file['type'], $this->settings['allowed-attachment-types']))
                        {
                            return array(
                                'error'     => true,
                                'result'    => 'INVALID_ATTACHMENT_TYPE',
                                'form'      => $form,
                                'recipient' => $recipient,
                                'attachmentSize'=>$attachmentSize,
                                'departments'=>$departments,
                            ); 
                        }
                        $extension = end(explode('.', $file['name']));
                        $newName = md5( $file['name'] . $this->authData->username . microtime() ) . ".$extension";
                        move_uploaded_file($file['tmp_name'], "attachments/$newName");
                        $message->attachment = $newName;
                        $message->attachment_name = $file['name'];
                    }
                    
                    if( $recipient )
                    {
                        $ticket->userid = $recipient->userid;
                        $ticket->status = 'pending_user';
                    }
                    else
                    {
                        $ticket->userid = $this->authData->userid;
                        $ticket->status = 'pending_admin';
                    }
                    $message->userid = $this->authData->userid;
                    $ticket->opened_at = date( 'Y-m-d H:i:s' );

                    $message->ticketid = $this->getSupportTicketsTable()->saveTicket( $ticket );
                    $message->status = 'unread';
                    $message->msgdate = date( 'Y-m-d H:i:s' );
                    
                    $this->getTicketMessagesTable()->saveTicketMessage( $message );
                    KEventManager::trigger('NewTicketSubmitted', array(
                        'title'     => $ticket->title,
                        'userid'    => $ticket->userid,
                        'message'   => $message->message,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    return( array(
                        'error'    => false,
                        'result'   => 'TICKET_SUBMITTED',
                    ));
                }
                else
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_DATA_PROVIDED',
                        'form'      => $form,
                        'recipient' => $recipient,
                        'attachmentSize'=>$attachmentSize,
                        'departments'=>$departments,
                    ) );
                }
            }
            
            return( array(
                'form'          => $form,
                'recipient'     => $recipient,
                'attachmentSize'=>$attachmentSize,
                'departments'   =>$departments,
            ) );
        }
        else
        {
            $form = new GuestSupportForm();
            $request = $this->getRequest();
            if($request->isPost())
            {
                $form->setData($request->getPost());
                if($form->isValid())
                {
                    $email = $form->get('email')->getValue();
                    $title = $form->get('title')->getValue();
                    $message = $form->get('message')->getValue();
                    $emailSender = new EmailSender($this->settings['site-url'], $this->getServiceLocator()->
                            get('translator'), substr($this->settings['language'], 0, 2));
                    $domain = str_replace("http://", "", $this->settings['site-url']);
                    $domain = str_replace("https://", "", $domain);
                    $domain = str_replace("/", "", $domain);                    
                    
                    $emailSender->sendMessage("support@$domain", $email, $title, $message);
                                        
                    KEventManager::trigger('NewGuestSupport', array(
                        'email'     => $email,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    
                    return(array(
                        'error'     => false,
                        'result'    => 'SUPPORT_EMAIL_SENT',
                    ));
                }
                else
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_DATA_PROVIDED',
                        'guestForm' => $form,
                    ) );
                }
            }
                        
            return( array(
                'guestForm'    => $form,
            ) );
        }
    }
    
    public function ViewTicketsAction()
    {
        if( $this->authData && $this->authData->userid )
        {
            $ticketid = (int)$this->params()->fromRoute('param1');
            $ticket = $this->getSupportTicketsTable()->fetchTickets( array( 'ticketid' => $ticketid ) )->current();
            if( $ticket )
            {
                $attachmentSize = $this->settings['max-attachment-size'];
                if( $this->authData->user_type == 'paster' || $this->authData->user_type == 'advertiser' )
                {
                    if( $this->authData->userid != $ticket->userid )
                    {
                        return( array(
                            'error'     => true,
                            'result'    => 'ACCESS_DENIED',
                        ) );
                    }
                }
                $request = $this->getRequest();
                if( $request->getPost( 'action' ) == 'close' )
                {
                    $ticket->status = 'closed';
                    $this->getSupportTicketsTable()->saveTicket( $ticket );
                    return $this->redirect()->toRoute('support', array('action'=>'ViewTickets'));
                }
                if( $request->isPost() )
                {
                    $post = array_merge(
                        $request->getPost()->toArray(),
                        $request->getFiles()->toArray()
                    );
                    $newmsg = new TicketMessages();
                    $newmsg->ticketid = $ticketid;
                    $newmsg->userid = $this->authData->userid;
                    $newmsg->message = $post['message'];
                    $newmsg->status = 'unread';
                    $newmsg->msgdate = date( 'Y-m-d H:i:s' );
                    $file = $post['attachment'];
                    if( $file['name'] )
                    {
                        if( $file['error'] )
                        {
                            return( array(
                                'error'     => true,
                                'result'    => 'ATTACHMENT_UPLOAD_FAILED',
                            ) );
                        }

                        if((int)$file['size'] > (int)$attachmentSize)
                        {
                            return( array(
                                'error'     => true,
                                'result'    => 'ATTACHMENT_SIZE_LIMIT_EXCEEDED',
                            ) );
                        }

                        if(!in_array($file['type'], $this->settings['allowed-attachment-types']))
                        {
                            return array(
                                'error'     => true,
                                'result'    => 'INVALID_ATTACHMENT_TYPE',
                            ); 
                        }
                        $extension = end(explode('.', $file['name']));
                        $newName = md5( $file['name'] . $this->authData->username . microtime() ) . ".$extension";
                        move_uploaded_file($file['tmp_name'], "attachments/$newName");
                        $newmsg->attachment = $newName;
                        $newmsg->attachment_name = $file['name'];
                    }
                    
                    $this->getTicketMessagesTable()->saveTicketMessage( $newmsg );
                    
                    KEventManager::trigger('NewTicketMessageSubmitted', array(
                        'userid'    => $ticket->userid,
                        'message'   => $newmsg->message,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    
                    if( $this->authData->user_type == 'admin' || $this->authData->user_type == 'masteradmin' )
                        $ticket->status = 'pending_user';
                    else
                        $ticket->status = 'pending_admin';
                    $this->getSupportTicketsTable()->saveTicket( $ticket );
                }
                
                $messages = $this->getTicketMessagesTable()->fetchTicketMessages( array( 
                    'ticketid'  => $ticketid
                ) );
                $this->getTicketMessagesTable()->setAsRead($ticketid, $this->authData->userid);
                return( array(
                    'ticket'    => $ticket,
                    'messages'  => $messages,
                    'attachmentSize'=> $attachmentSize,
                ) );
            }
            else
            {
                if( $this->authData->user_type == 'admin' || $this->authData->user_type == 'masteradmin' )
                {
                    $pendingTickets = $this->getSupportTicketsTable()->fetchTickets( array( 
                        'status'    => 'pending_admin',
                    ), true );
                    $pendingTickets->setCurrentPageNumber( $this->params()->fromQuery('ppage', 1) );
                    $pendingTickets->setItemCountPerPage( $this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
                    
                    $where = new Where();
                    $where->notEqualTo('status', 'pending_admin');
                    $otherTickets = $this->getSupportTicketsTable()->fetchTickets( $where, true );
                        
                    $otherTickets->setCurrentPageNumber( $this->params()->fromQuery('opage', 1) );
                    $otherTickets->setItemCountPerPage( $this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
                    
                    return( array(
                        'pendingTickets'    => $pendingTickets,
                        'otherTickets'      => $otherTickets,
                    ) );
                }
                else
                {
                    $tickets = $this->getSupportTicketsTable()->fetchTickets( array(
                        'support_tickets.userid'    => $this->authData->userid
                    ), true );
                    
                    return( array( 'tickets' => $tickets ) );
                }
            }
        }
        
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function DownloadAttachmentAction()
    {
        $tmsgid = (int)$this->params()->fromRoute('param1');
        
        if( !$tmsgid )
        {
            return( array( 
                'error'     => true,
                'result'    => 'INVALID_ATTACHMENT_ID',
            ));
        }
        
        $tmsg = $this->getTicketMessagesTable()->fetchTicketMessages( array( 'tmsgid' => $tmsgid ) )->current();
        
        if( !$tmsg || !$tmsg->attachment )
        {
            return( array( 
                'error'     => true,
                'result'    => 'INVALID_ATTACHMENT_ID',
            ));
        }
        
        if( !$this->authData || !$this->authData->userid )
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
        
        if( 
            $this->authData->user_type != 'masteradmin' && 
            $this->authData->user_type != 'admin' && 
            $this->authData->userid != $tmsg->userid
        )
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
        
        $file = 'attachments/' . $tmsg->attachment;
        
        if( !file_exists( $file ) )
        {
            return( array(
                'error'     => true,
                'result'    => 'ATTACHMENT_NOT_FOUND'
            ));
        }
        
        $type = \KpasteCore\Utilities::getMime($tmsg->attachment);
        
        $response = new \Zend\Http\Response\Stream();
        $response->setStream(fopen($file, 'rb'));
        $response->setStatusCode(200);
        
        $headers = new \Zend\Http\Headers();
        $headers->addHeaderLine('Content-Type', $type)
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $tmsg->attachment_name . '"')
            ->addHeaderLine('Content-Length', filesize($file));
        
        $response->setHeaders($headers);
        
        KEventManager::trigger('AttachmentDownloaded', array(
            'tmsgid'    => $tmsgid,
            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
        ), $this->getServiceLocator());
        
        return $response;
    }
    
    public function getUsersTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
    
    public function getSupportTicketsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Support\Model\SupportTicketsTable');
    }
    
    public function getTicketMessagesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Support\Model\TicketMessagesTable');
    }
    
    public function getDepartmentsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Support\Model\DepartmentsTable');
    }
}