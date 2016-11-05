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

namespace Paster\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container as SessionContainer;
use Zend\Authentication\AuthenticationService;

use KpasteCore\EmailSender;
use KpasteCore\KSMS;

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
    
    public function NewPasteSubmitted($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('NEW_PASTE_SUBMITTED:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    public function PasteEdited($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('PASTE_EDITTED:[pasteid:' . $params['pasteid'] . ']', $params['ip']);
        }
    }
    
    
    public function PasteDeleted($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('PASTE_DELETED:[pasteid:' . $params['pasteid'] . ']', $params['ip']);
        }
    }
    
    public function CheckoutRegistered($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity('CHECKOUT_REGISTERED', $params['ip']);
            
            //send text messsage to user
            $ksms = new KSMS($this->serviceLocator);
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('Your request has been registered and its result ' . 
                    'will be texted to you.');
            $ksms->sendMessage($user->cell_number, $message);
            
            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, 
                $translator->translate('Checkout Request Registered!'), 
                $this->translate('Checkout request has been registered and will be processed shortly.')
            );
        }
    }
}

?>
