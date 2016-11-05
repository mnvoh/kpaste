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

namespace KpasteCore\Controller;

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
    
    /**************************************************************/
    //-------------------[ General Events ]-----------------------//
    /**************************************************************/
    
    public function UnauthorizedAccess($params)
    {
        if($params && !empty($params['ip']) && !empty($params['url']))
        {
            $this->NewActivity('UNAUTHORIZED_ACCESS_TO:[url:' . $params['url'] . ']', 
                    $params['ip']);
        }
    }
    
    public function LowSmsAccountBalance($params)
    {
        $title = 'SMS Acount Balance Is Low';
        $message = 'SMS account balance is running low. Current balance is: ' . $params['balance'];
        $this->EmailAdmin($title, $message);
    }
    
    /**************************************************************/
    //----------------[ AjaxController Events ]-------------------//
    /**************************************************************/
    
    public function PasteReported($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('PASTE_REPORTED:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    public function PasteUpVoted($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('PASTE_UPVOTED:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    
    public function PasteDownVoted($params)
    {
        if($params && !empty($params['userid']) && !empty($params['pasteid']))
        {
            $this->NewActivity('PASTE_DOWNVOTED:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    /**************************************************************/
    //----------------[ HomeController Events ]-------------------//
    /**************************************************************/
    
    public function NewPasteSubmitted($params)
    {
        if($params && !empty($params['pasteid']))
        {
            $this->NewActivity('NEW_PASTE_SUBMITTED:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    /**************************************************************/
    //--------------[ ViewPasteController Events ]----------------//
    /**************************************************************/
    
    public function IncorrectPastePassword($params)
    {
        if($params && !empty($params['pasteid']))
        {
            $this->NewActivity('INCORRECT_PASTE_PASSWORD:[pasteid:' . $params['pasteid'] . ']', 
                    $params['ip']);
        }
    }
    
    
    /**************************************************************/
    //---------------[ EPaymentController Events ]----------------//
    /**************************************************************/    
    public function InvoiceCreated($params)
    {
        if($params && !empty($params['amount']))
        {
            $this->NewActivity('INVOICE_CREATED:[transactionid:' . $params['transactionid'] . 
                'gateway:' . $params['gateway'] . ']', $params['ip']);
            
            $mailSender = new EmailSender($this->settings['site-url'], 
                    $this->serviceLocator->get('translator'), $params['lang']);
            $mailSender->sendInvoice($params['description'], $params['amount'], $params['date'], $params['to']);
        }
    }
    
    public function InvoicePaid($params)
    {
        if($params && !empty($params['amount']))
        {
            $this->NewActivity('INVOICE_PAID:[transactionid:' . $params['transactionid'] . 
                'gateway:' . $params['gateway'] . ']', $params['ip']);
            
            $mailSender = new EmailSender($this->settings['site-url'], 
                    $this->serviceLocator->get('translator'), $params['lang']);
            $mailSender->sendInvoice($params['description'], $params['amount'], $params['date'], $params['to']);
        }
    }
}

?>
