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

namespace Admin\Controller;

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
    /**************************************************************/
    //-------------------[ General Events ]-----------------------//
    /**************************************************************/
    
    public function UnauthorizedAccess($params)
    {
        if($params && !empty($params['ip']) && !empty($params['url']))
        {
            $this->NewActivity("UNAUTHORIZED_ACCESS:[url:{$params['url']}]", 
                    $params['ip']);
        }
    }
    
    /**************************************************************/
    //-----------[ MasterAdminController Events ]-----------------//
    /**************************************************************/
    
    public function NewAdminCreated($params)
    {
        if($params && !empty($params['adminName']))
        {
            $this->NewActivity("NEW_ADMIN_CREATED:[username:{$params['adminName']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'New admin created: ' . $params['adminName'];
            $message = 'A new admin has been created. Username: ' . 
                    $params['adminName'];
            $this->$this->EmailAdmin($title, $message);
        }
    }
    
    public function NewPermissionsSet($params)
    {
        if($params && !empty($params['adminid']))
        {
            $this->NewActivity("NEW_ADMIN_PERMISSIONS_SET:[id:{$params['adminid']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'Admin Permissions Changed';
            $message = 'The permissions of ' . $params['adminid'] . 'was changed at ' .
                    date('Y-m-d H:i:s');
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AdminSuspended($params)
    {
        if($params && !empty($params['adminid']))
        {
            $this->NewActivity("ADMIN_SUSPENDED:[id:{$params['adminid']}]", $params['ip']);
            //send am email address to website admin
            $title = 'Admin Suspended';
            $message = 'Admin ' . $params['adminid'] . 'was suspended at ' .
                    date('Y-m-d H:i:s');
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AdminSuspensionRemoved($params)
    {
        if($params && !empty($params['adminid']))
        {
            $this->NewActivity("ADMIN_SUSPENSION_REMOVED:[id:{$params['adminid']}]", $params['ip']);
            //send am email address to website admin
            $title = 'Admin Suspension Removed';
            $message = 'The suspension of ' . $params['adminid'] . 'was removed at ' .
                    date('Y-m-d H:i:s');
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AdminDeleted($params)
    {
        if($params && !empty($params['adminid']))
        {
            $this->NewActivity("ADMIN_DELETED:[id:{$params['adminid']}]", $params['ip']);
            //send am email address to website admin
            $title = 'Admin Deleted';
            $message = 'The admin ' . $params['adminid'] . ' was deleted at ' .
                    date('Y-m-d H:i:s');
            $this->EmailAdmin($title, $message);
        }
    }
    
    /**************************************************************/
    //-------------[ SystemLogController Events ]-----------------//
    /**************************************************************/
    
    public function PastesClosed($params)
    {
        if($params && !empty($params['pasteids']))
        {
            $pasteids = json_encode($params['pasteids']);
            $this->NewActivity("PASTES_CLOSED:[pasteids:$pasteids]", $params['ip']);
        }
    }
    
    public function PastesOpened($params)
    {
        if($params && !empty($params['pasteids']))
        {
            $pasteids = json_encode($params['pasteids']);
            $this->NewActivity("PASTES_OPENED:[pasteids:$pasteids]", $params['ip']);
        }
    }
    
    public function PastesDeleted($params)
    {
        if($params && !empty($params['pasteids']))
        {
            $pasteids = json_encode($params['pasteids']);
            $this->NewActivity("PASTES_DELETED:[pasteids:$pasteids]", $params['ip']);
        }
    }
    
    public function AdCampaignAccepted($params)
    {
        if($params && !empty($params['campaignid']))
        {
            $this->NewActivity("AD_CAMPAIGN_ACCEPTED:[campaignid:{$params['campaignid']}]", 
                    $params['ip']);
            
            //send sms to inform the campaign owner
            $ksms = new KSMS($this->serviceLocator);
            $campaignsTable = $this->serviceLocator->get('Advertiser\Model\CampaignsTable');
            $campaign = $campaignsTable->fetchCampaign(array('campaignid' => $params['campaignid']));
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $campaign->userid);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('Your ad campaign has been accepted.');
            $ksms->sendMessage($user->cell_number, $message);
            
            //and also inform via email
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, $translator->translate('Ad Campaign accepted!'), 
                    sprintf($this->translate('Ad campaign #%s has been accepted.' . 
                            ' Find more info in your control panel.'), $params['campaignid'])
            );
        }
    }
        
    public function AdCampaignRejected($params)
    {
        if($params && !empty($params['campaignid']))
        {
            $this->NewActivity("AD_CAMPAIGN_ACCEPTED:[campaignid:{$params['campaignid']}]", 
                    $params['ip']);
            
            $ksms = new KSMS($this->serviceLocator);
            $campaignsTable = $this->serviceLocator->get('Advertiser\Model\CampaignsTable');
            $campaign = $campaignsTable->fetchCampaign(array('campaignid' => $params['campaignid']));
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $campaign->userid);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('Your ad campaign has been rejected.');
            $ksms->sendMessage($user->cell_number, $message);
            
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, $translator->translate('Ad Campaign rejected!'), 
                    sprintf($this->translate('Ad campaign #%s has been rejected.' . 
                            ' Find more info in your control panel.'), $params['campaignid'])
            );
        }
    }
    
    public function PayoutMade($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity("PAYOUT_MADE:[checkoutid:{$params['checkoutid']}]", $params['ip']);
            
            $ksms = new KSMS($this->serviceLocator);
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('The requested amount was deposited into your account.');
            $ksms->sendMessage($user->cell_number, $message);
            
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, $translator->translate('Checkout Request Was Fulfilled'), 
                    sprintf($this->translate('The requested checkout amount (%s) has been successfully' . 
                            ' deposited into your bank account.'), $params['amount'])
            );
        }
    }
    
    public function CheckoutDenied($params)
    {
        if($params && !empty($params['userid']) && !empty($params['amount']))
        {
            $this->NewActivity("CHECKOUT_DENIED:[userid:{$params['userid']}]" . 
                    ",[amount:{$params['amount']}]", $params['ip']);
                    
            $ksms = new KSMS($this->serviceLocator);
            $usersTable = $this->serviceLocator->get('User\Model\UserTable');
            $user = $usersTable->fetchUser('userid', $params['userid']);
            $translator = $this->serviceLocator->get('translator');
            $message = $translator->translate('Checkout request denied!');
            $ksms->sendMessage($user->cell_number, $message);
            
            $emailSender = new EmailSender($this->settings['site-url'], $translator, 
                    substr($this->settings['language'], 0, 2));
            $emailSender->sendMessage($user->email, false, $translator->translate('Checkout Request Denied'), 
                    sprintf($this->translate('The requested checkout amount (%s) was denied.' . 
                            ' Seek the reason in your user control panel.'), $params['amount'])
            );
        }
    }
    
    /**************************************************************/
    //-------------[ SystemSettingsController Events ]------------//
    /**************************************************************/
    
    public function SystemSettingsChanged($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity("SYSTEM_SETTINGS_CHANGED:[userid:{$params['userid']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'System Settings Changed';
            $message = 'System settings was changed at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AdsLayoutChanged($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity("ADS_LAYOUT_CHANGED:[userid:{$params['userid']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'Ads Layout Changed';
            $message = 'Ads Layout was changed at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function TermsAndPrivacyChanged($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity("TERMS_CHANGES:[userid:{$params['userid']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'Terms, Privacy Policy Or Q&A changed';
            $message = 'Terms, Privacy Policy Or Q&A was changed at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AnnouncementPublished($params)
    {
        if($params && !empty($params['userid']))
        {
            $this->NewActivity("ANNOUNCEMENT_PUBLISHED:[userid:{$params['userid']}]", 
                    $params['ip']);
            
            //send am email address to website admin
            $title = 'A new announcement published';
            $message = 'A new announcement was published at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function AnnouncementDeleted($params)
    {
        if($params && !empty($params['userid']) && !empty($params['aid']))
        {
            $this->NewActivity("ANNOUNCEMENT_DELETED:[userid:{$params['userid']}]"
                    . ", [announcementid:{$params['aid']}]", $params['ip']);
            
            //send am email address to website admin
            $title = 'An announcement was deleted';
            $message = 'An announcement was deleted at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['userid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    /**************************************************************/
    //-------------[ UserManagementController Events ]------------//
    /**************************************************************/
    
    public function UserSuspended($params)
    {
        if($params && !empty($params['userid']) && !empty($params['adminid']))
        {
            $this->NewActivity("USER_SUSPENDED:[userid:{$params['userid']}]"
                    . ", [adminid:{$params['adminid']}]", $params['ip']);
            
            //send am email address to website admin
            $title = 'A user was suspended';
            $message = 'A user was suspended at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['adminid'];
            $this->EmailAdmin($title, $message);
        }
    }
    
    public function UserSuspensionRemoved($params)
    {
        if($params && !empty($params['userid']) && !empty($params['adminid']))
        {
            $this->NewActivity("USER_SUSPENSION_REMOVED:[userid:{$params['userid']}]"
                    . ", [adminid:{$params['adminid']}]", $params['ip']);
            
            //send am email address to website admin
            $title = 'A user\'s suspension was removed';
            $message = 'A user\'s suspension was removed at ' . date('Y-m-d H:i:s') . ' by ' 
                    . $params['adminid'];
            $this->EmailAdmin($title, $message);
        }
    }
}

?>
