<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace KpasteCore\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use KpasteCore\Form\PastePasswordForm;
use KpasteCore\Cipher\Cipher;

class ViewPasteController extends AbstractActionController
{
    protected $settings;
    
    public function __construct()
    {
        $settings       = new SessionContainer('settings');
        $this->settings = $settings->settings;
    }
    
    public function indexAction()
    {
        return $this->redirect()->toRoute('kpastecore', array('controller' => 'home'));
    }
    
    public function viewAction()
    {
        $pasteid = (int)$this->params()->fromRoute('param1');
        $auth = new AuthenticationService();
        $authData = unserialize($auth->getStorage()->read());
        if(!$pasteid)
        {
            $this->redirect()->toRoute('kpastecore', array('controller' => 'Home'));
        }
        
        $paste = $this->getPastesTable()->fetchPaste(array('pasteid' => $pasteid));
        
        if($paste)
        {
            if($paste->status == 'closed')
            {
                return array('error' => true, 'result' => 'PASTE_IS_CLOSED', 'settings' => $this->settings);
            }
            
            if($paste->exposure == 'private')
            {
                if(!$authData || ($authData->userid != $paste->userid && 
                        ($authData->user_type != 'admin' && $authData->user_type != 'masteradmin')))
                {
                    return array('error' => true, 'result' => 'PASTE_IS_PRIVATE', 'settings' => $this->settings);
                }
            }            
            
            if($paste->password_test && strlen($paste->password_test))
            {
                $request = $this->getRequest();
                $form = new PastePasswordForm();
                if($request->isPost())
                {
                    $form->setData($request->getPost());
                    if($form->isValid())
                    {
                        $cipher = new Cipher($request->getPost('password'));
                        $passwordTest = trim($cipher->decrypt($paste->password_test));
                        if($passwordTest == "PASSWORD_IS_OK")
                        {
                            $user = null;
                            if($paste->userid)
                            {
                                $user = $this->getUsersTable()->fetchUser('userid', $paste->userid);
                                $user = $user->username;
                            }

                            $thumbsUpCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'up');
                            $thumbsDownCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'down');
                            $isOwner = ($authData && $authData->userid == $paste->userid) ? true : false;
                            return array(
                                'pasteid'           => $paste->pasteid,
                                'pasteUserId'       => $paste->userid,
                                'pasteOwner'        => $user,
                                'pasteTitle'        => $paste->title,
                                'paste'             => trim($cipher->decrypt($paste->paste)),
                                'syntax'            => $paste->syntax,
                                'pastedOn'          => 
                                \KpasteCore\KDateTime\KDateTime::PrefDate($paste->pasted_on),
                                'settings'          => $this->settings,
                                'thumbsUpCount'     => $thumbsUpCount,
                                'thumbsDownCount'   => $thumbsDownCount,  
                                'isOwner'           => $isOwner,
                            );
                        }
                        else
                        {
                            KEventManager::trigger('IncorrectPastePassword', array(
                                'pasteid'       => $newPasteId,
                                'ip'            => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            return array('error' => true, 'result' => 'INCORRECT_PASSWORD'
                                ,'passwordForm' => $form, 'pasteid' => $pasteid, 
                                'settings' => $this->settings);
                        }
                    }
                    else
                    {
                        return array(
                            'error'     => true, 
                            'result'    => 'INVALID_DATA_PROVIDED',
                            'settings' => $this->settings
                        );
                    }
                }
                else
                {
                    return array(
                        'passwordForm'  => $form, 
                        'pasteid'       => $pasteid,
                        'settings' => $this->settings
                    );
                }
            }
            else
            {
                $user = null;
                if($paste->userid)
                {
                    $user = $this->getUsersTable()->fetchUser('userid', $paste->userid);
                    $user = $user->username;
                }
                
                $thumbsUpCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'up');
                $thumbsDownCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'down');
                $isOwner = ($authData && $authData->userid == $paste->userid) ? true : false;
                return array(
                    'pasteid'           => $paste->pasteid,
                    'pasteUserId'       => $paste->userid,
                    'pasteOwner'        => $user,
                    'pasteTitle'        => $paste->title,
                    'paste'             => $paste->paste,
                    'syntax'            => $paste->syntax,
                    'pastedOn'          => 
                    \KpasteCore\KDateTime\KDateTime::PrefDate($paste->pasted_on),
                    'settings'          => $this->settings,
                    'thumbsUpCount'     => $thumbsUpCount,
                    'thumbsDownCount'   => $thumbsDownCount,
                    'isOwner'           => $isOwner,
                );
            }
        }
        else
        {
            return array('error' => true, 'result' => 'PASTE_NOT_FOUND', 'settings' => $this->settings);
        }
    }
    
    public function downloadAction()
    {
        $pasteid = (int)$this->params()->fromRoute('param1');
        $paste = $this->getPastesTable()->fetchPastes(array(
                        'pastes.pasteid'=>$pasteid
                    ))->current();
        if($paste)
        {
            $request = $this->getRequest();
            $pasteText = "";
            $form = new PastePasswordForm();
            
            if($paste->password_test && strlen($paste->password_test))
            {
                if($request->isPost())
                {
                    $form->setData($request->getPost());
                    if($form->isValid())
                    {
                        $cipher = new Cipher($request->getPost('password'));
                        $passwordTest = trim($cipher->decrypt($paste->password_test));
                        $pasteText .= $passwordTest;
                        if($passwordTest == "PASSWORD_IS_OK")
                        {
                            $pasteText = trim($cipher->decrypt($paste->paste));
                            $headers = new \Zend\Http\Headers();
                            $headers->addHeaderLine('Content-Type', 'text/plain')
                            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $paste->title . '.txt"')
                            ->addHeaderLine('Content-Length', strlen($paste->paste));

                            return( $this->getResponse()->setHeaders($headers)->setContent($pasteText) );
                        }
                        else
                        {
                            return(array(
                                'error'             => true,
                                'result'            => 'INCORRECT_PASSWORD',
                                'passwordForm'      => $form,
                                'pasteid'           => $paste->pasteid,
                            ));
                        }
                    }
                    else
                    {
                        return(array(
                            'error'             => true,
                            'result'            => 'INCORRECT_PASSWORD',
                            'passwordForm'      => $form,
                            'pasteid'           => $paste->pasteid,
                        ));
                    }
                }
                else
                {
                    return( array( 
                        'passwordForm'      => $form,
                        'pasteid'           => $paste->pasteid,
                    ));
                }
            }
            
            $headers = new \Zend\Http\Headers();
            $headers->addHeaderLine('Content-Type', 'text/plain')
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $paste->title . '.txt"')
            ->addHeaderLine('Content-Length', strlen($paste->paste));
            
            return( $this->getResponse()->setHeaders($headers)->setContent($paste->paste) );
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'INVALID_PASTE_ID',
            ));
        }
    }
    
    public function userAction()
    {
        $userid = (int)$this->params()->fromRoute('param1');
        $user = $this->getUsersTable()->fetchUser('userid', $userid);
        if($user != null)
        {
            $pastes = $this->getPastesTable()->fetchPastes(array(
                'pastes.userid'    => $user->userid,
                'exposure'  => 'public',
                'status'    => 'active',
            ), true);
            
            $pastes->setCurrentPageNumber((int)$this->params()->fromQuery('page', 1));
            $pastes->setItemCountPerPage((int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE));
            
            return(array(
                'pastes'        => $pastes,
                'username'      => $user->username,
                'userid'        => $userid,
            ));
        }
        else
        {
            $pasters = $this->getUsersTable()->fetchAll(array(
                'user_type'         => 'paster',
                'account_status'    => 'verified',
            ), false, 'total_pastes DESC', true);
            
            return(array('pasters' => $pasters));
        }
    }
    
    public function bannerAction() 
    {
        $cid = (int)$this->params()->fromRoute('param1');
        $campaign = $this->getCampaignsTable()->fetchCampaign(array(
            'campaignid'    => $cid,
        ));
        if($campaign && $campaign->campaign_banner && file_exists('banners/' . $campaign->campaign_banner))
        {
            $type = \KpasteCore\Utilities::getMime($campaign->campaign_banner);
            $headers = new \Zend\Http\Headers();
            $headers->addHeaderLine('Content-Type', $type);
            return( $this->getResponse()->setHeaders($headers)->setContent(file_get_contents(
                    'banners/' . $campaign->campaign_banner)) );
        }
    }
    
    public function getCampaignsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\CampaignsTable');
    }
    
    public function getPastesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PastesTable');
    }
    
    public function getThumbsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ThumbsTable');
    }
    
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
}
