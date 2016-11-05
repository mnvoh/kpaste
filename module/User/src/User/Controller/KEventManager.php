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

namespace User\Controller;

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
        $emailSender->sendMessage($this->settings['admin-email'], 
                false, $title, $message);
    }
    
    public function UnauthorizedAccess($params)
    {
        if($params && !empty($params['ip']) && !empty($params['url']))
        {
            $this->NewActivity('UNAUTHORIZED_ACCESS_TO:[url:' . $params['url'] . ']', 
                    $params['ip']);
        }
    }
    
    public function UserLoggedIn($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('USER_LOGGEDIN:[userid:' . $params['userid'] . ']', $params['ip']);
        }
    }
    
    public function WrongLoginAttempt($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('WRONG_LOGGIN_ATTEMPT', $params['ip']);
        }
    }
    
    
    public function NewUserSignedUp($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('NEW_USER_SIGNED_UP[userid:' . $params['userid'] . ']', $params['ip']);
            
            $title = 'New User Signed up';
            $message = 'A new user has been signed up: ' . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function WrongEmailVerification($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('WRONG_EMAIL_VERIFICATION', $params['ip']);
        }
    }
    
    public function EmailVerified($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('EMAIL_VERIFIED[userid:' . $params['userid'] . ']', $params['ip']);
        }
    }
    
    public function NewVerificationEmailSent($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('NEW_VERIFICATION_EMAIL_SENT[userid:' . $params['userid'] . ']', 
                    $params['ip']);
        }
    }    
    
    public function PasswordChanged($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('PASSWORD_CHANGED[userid:' . $params['userid'] . ']', 
                    $params['ip']);

            //send text messsage to user
            $translator = $this->serviceLocator->get('translator');

            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            
            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, 
                    $translator->translate('Account Password Changed!'), 
                    $this->translate('Your account password has been changed.')
            );
        }
    }  
    
    public function PasswordChangeFailed($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('PASSWORD_CHANGE_FAILED[userid:' . $params['userid'] . ']', 
                    $params['ip']);
        }
    }  
    
    public function InvalidEmailForPasswordChange($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('INVALID_EMAIL_FOR_PASSWORD_CHANGE', $params['ip']);
        }
    }
    
    public function TooManyPasswordChangeRequests($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('TOO_MANY_PASSWORD_CHANGE_REQUESTS', $params['ip']);
        }
    }
    
    public function PasswordChangeRequestRegistered($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('PASSWORD_CHANGE_REQUEST_REGISTERED', $params['ip']);
        }
    }
    
    public function AccountInformationChanged($params)
    {
        if($params && !empty($params['ip']))
        {
            $this->NewActivity('ACCOUNT_INFORMATION_CHANGED', $params['ip']);
            
            //send text messsage to user
            $translator = $this->serviceLocator->get('translator');

            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($params['email'], false, 
                    $translator->translate('Account Information Changed!'), 
                    $this->translate('Your account information has been changed.')
            );
        }
    }
}

?>
