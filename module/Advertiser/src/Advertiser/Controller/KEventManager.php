<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    MasterAdminController.php
 * @createdat    Jul 27, 2013 12:20:53 PM
 */

namespace Advertiser\Controller;

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
    
    public function NewCampaignCreated($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('NEW_CAMPAIGN_CREATED:[campaignid:' . $params['campaignid'] . ']', 
                    $params['ip']);
            $title = 'A new campaign created!';
            $message = 'A new ad campaign was created at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function NewCampaignBannerUploaded($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('NEW_CAMPAIGN_BANNER_UPLOADED:[campaignid:' . $params['campaignid'] 
                    . ']', $params['ip']);
        }
    }
    
    
    public function CampaignExtended($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('CAMPAIGN_EXTENDED:[campaignid:' . $params['campaignid'] 
                    . ']', $params['ip']);
        }
    }
    
    public function CampaignPaused($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('CAMPAIGN_PAUSED:[campaignid:' . $params['campaignid'] 
                    . ']', $params['ip']);
        }
    }
    
    public function CampaignResumed($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('CAMPAIGN_RESUMED:[campaignid:' . $params['campaignid'] 
                    . ']', $params['ip']);
        }
    }
    
    public function AccountRecharged($params)
    {
        if($params && !empty($params['userid']) && !empty($params['campaignid']))
        {
            $this->NewActivity('ACCOUNT_RECHARGED:[campaignid:' . $params['campaignid']
                    . ']', $params['ip']);
            //email admin
            $title = 'An account was recharged!';
            $message = 'The account of ' . $params['userid'] . ' was recharged. Amount: ' . 
                    $params['amount'];
            $this->EmailAdmin($title, $message);
            
            //send text messsage to user
            $ksms = new KSMS($this->serviceLocator);
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('Your account was recharged. New balance: %s');
            $ksms->sendMessage($user->cell_number, sprintf($message, $params['newBalance']));
            
            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, $translator->translate('Account Recharged!'), 
                    sprintf($this->translate('Your account has been recharged.' . 
                            ' New account balance is %s.'), $params['newBalance'])
            );
        }
    }    
}

?>
