<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    KEventManager.php
 * @createdat   Sep 25, 2013 12:20:53 PM
 */

namespace Support\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container as SessionContainer;
use Zend\Authentication\AuthenticationService;

use KpasteCore\EmailSender;

class KEventManager
{
    protected $serviceLocator;
    protected $settings;
    protected $authData;
    
    public function __construct(ServiceLocatorInterface $serviceLocator) 
    {
        $this->serviceLocator = $serviceLocator;
        $settings       = new SessionContainer('settings');
        $this->settings = $settings->settings;
        $auth           = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
    }
    
    /**
     * Triggers an event and if there's a method with that name, 
     * calls it.
     * @param type $event   The event name
     * @param type $params  The parameters that will be passed to the method, all in one array
     */
    static public function trigger($event, $params, $serviceLocator)
    {
        $eventmanager = new static($serviceLocator);
        try {
            $eventmanager->{$event}($params);
        }catch(\Exception $e){}
    }
    
    public function NewActivity($activity, $ip)
    {
        $systemActivitiesTable = $this->serviceLocator->
                get('KpasteCore\Model\SystemActivitiesTable');
        $userid = ($this->authData && $this->authData->userid) ? 
                $this->authData->userid : NULL;
        $systemActivitiesTable->insertActivity($activity, $userid, $ip, 
                date('Y-m-d H:i:s'));
    }
    
    public function EmailAdmin($title, $message)
    {
        $emailSender = new EmailSender($this->settings['site-url'], 
                $this->serviceLocator->get('translator'), 'en');
        $emailSender->sendMessage($this->settings['admin-email'], false, $title, $message);
    }
    
    public function UnauthorizedAccess($params)
    {
        if($params && !empty($params['ip']) && !empty($params['url']))
        {
            $this->NewActivity('UNAUTHORIZED_ACCESS_TO:[url:' . $params['url'] . ']', 
                    $params['ip']);
        }
    }
    
    public function NewTicketSubmitted($params)
    {
        if($params && !empty($params['title']))
        {
            $this->NewActivity('NEW_TICKET', $params['ip']);
            
            $title = 'New Support Ticket';
            $message = 'A new support ticket has been submitted: "' . $params['title'] . '".';
            $this->EmailAdmin($title, $message);
            
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            
            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, 
                    $translator->translate('New Ticket Opened'), 
                    sprintf($translator->translate('A new ticket has been opened for you. <br />' . 
                            'Title: %s <br />' . 
                            'Message: %s'), $params['title'], $params['message'])
            );
        }
    }
    
    public function NewTicketMessageSubmitted($params)
    {
        if($params && !empty($params['message']))
        {
            $this->NewActivity('NEW_TICKET_MESSAGE', $params['ip']);
            
            $title = 'New Ticket Message';
            $message = 'A new ticket message has been submitted: <br />' . $params['message'];
            $this->EmailAdmin($title, $message);
            
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            
            //and also inform via email
            
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, 
                    $translator->translate('New Message In Your Ticket'), 
                    sprintf($translator->translate('There\'s a new message in your ticket: <br />' .
                            '%s'), $params['message'])
            );
        }
    }
    
    
    public function AttachmentDownloaded($params)
    {
        if($params && !empty($params['tmsgid']))
        {
            $this->NewActivity('ATTACHMENT_DOWNLOADED:[tmsgid:'.$params['tmsgid'].']',
                    $params['ip']);
        }
    }
    
    public function NewGuestSupport($params)
    {
        if($params && !empty($params['tmsgid']))
        {
            $this->NewActivity('SUPPORT_EMAIL_SENT:[email:'.$params['email'].']',
                    $params['ip']);
        }
    }
}

?>
