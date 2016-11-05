<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    PasterController.php
 * @createdat    Jul 11, 2013 12:20:53 PM
 */

namespace Paster\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use Paster\Model\Pastes;
use Paster\Form\NewPasteForm;
use Paster\Model\Checkouts;
use KpasteCore\Cipher\Cipher;
use KpasteCore\Form\PastePasswordForm;
use KpasteCore\IpToCountry\IpToCountry;
use KpasteCore\KChart\LineChart;

class PasterController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    
    public function __construct() 
    {
        $auth = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings = new SessionContainer('settings');
        $this->settings = $settings->settings;
    }
    
    public function indexAction() 
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'paster')
        {
            return(array(
                'theme'     => $this->settings['theme'],
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function newPasteAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'paster')
        {
            $form = new NewPasteForm();
            $request = $this->getRequest();

            if($request->isPost())
            {
                $form->setData($request->getPost());
                if($form->isValid())
                {
                    $newPaste = new Pastes();
                    $newPaste->exchangeArray($request->getPost());

                    //Encrypt the paste if password is set
                    if($request->getPost('password') && strlen($request->getPost('password')))
                    {
                        $cipher = new Cipher($request->getPost('password'));
                        $newPaste->password_test = $cipher->encrypt('PASSWORD_IS_OK');
                        $newPaste->paste = $cipher->encrypt($newPaste->paste);
                    }
                    
                    $newPaste->userid = $this->authData->userid;

                    $newPaste->status = 'active';
                    $newPaste->pasted_on = date('Y-m-d H:i:s');

                    $newPasteId = $this->getPastesTable()->savePaste($newPaste);

                    KEventManager::trigger('NewPasteSubmitted', array(
                        'pasteid'   => $newPasteId,
                        'userid'    => $newPaste,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());

                    return $this->redirect()->toRoute('kpastecore', array(
                        'controller'    => 'ViewPaste',
                        'action'        => 'view',
                        'param1'        => $newPasteId,
                    ));

                    //return array('error' => false, 'result' => 'PASTE_SAVED_SUCCESSFULLY');
                }
                else
                {
                    return array(
                        'error'     => true, 
                        'result'    => 'INVALID_DATA_PROVIDED',
                        'newPasteForm'=> $form,
                    );
                }
            }        

            return array('newPasteForm' => $form);
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function viewMyPastesAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'paster')
        {
            $pastes = $this->getPastesTable()->fetchPastersPastes($this->authData->userid, true);
            $pastes->setCurrentPageNumber((int)$this->params()->fromQuery('page', 1));
            $pastes->setItemCountPerPage((int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE));
            return array('myPastes' => $pastes);
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function viewPasteStatsAction()
    {
        $pasteid = (int)$this->params()->fromRoute('param1');
        if(!$pasteid)
        {
            return $this->redirect()->toRoute('paster', array('action' => 'viewMyPastes'));
        }
        
        if($this->authData && $this->authData->userid)
        {
            $paste = $this->getPastesTable()->fetchPaste(array('pasteid' => $pasteid));
            if($this->authData->userid == $paste->userid)
            {
                $pasteViews = $this->getPasteViewsTable()->fetchPasteViews(array('pasteid' => $pasteid));
                $ipInfo = new IpToCountry($this->getServiceLocator());
                $points = $this->getPasteViewsTable()->fetchPasteChartData($pasteid);
                
                $this->RemoveOldCharts();
                $path = count($points) ? "/images/charts/paste$pasteid.png" : null;
                
                if(!file_exists(ROOT_PATH . $path))
                {
                    $lineChart = new LineChart(700, 300, $points);
                    $lineChart->drawChart(ROOT_PATH . $path);
                }
                                
                return array(
                    'paste'         => $paste, 
                    'pasteViews'    => $pasteViews,
                    'ipInfo'        => $ipInfo,
                    'graph'         => $path,
                );
            }
            else
            {
                KEventManager::trigger('UnauthorizedAccess', array(
                    'pasteid'   => $this->getRequest()->getUri(),
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return array('error' => true, 'result' => 'ACCESS_DENIED');
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function deletePasteAction()
    {
        $pasteid = (int)$this->params()->fromRoute('param1');
        if(!$pasteid)
        {
            $this->redirect()->toRoute('paster', array('action' => 'viewMyPastes'));
        }
        
        $auth = new AuthenticationService();
        $this->authData = unserialize($auth->getStorage()->read());
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'paster')
        {
            $paste = $this->getPastesTable()->fetchPaste(array('pasteid' => $pasteid));
            if($this->authData->userid == $paste->userid)
            {
                $request = $this->getRequest();
                if($request->isPost())
                {
                    $del = $request->getPost('confirm', 'NO');

                    if('YES' == $del)
                    {
                        $this->getReportedPastesTable()->deleteReports( array( 
                                'pasteid'   => (int)$pasteid
                            ) );
                            $this->getThumbsTable()->deleteThumbs( array( 
                                'pasteid'   => (int)$pasteid
                            ) );
                        $this->getPasteViewsTable()->deletePasteViews(array('pasteid' => $pasteid));
                        $this->getPastesTable()->deletePaste(array('pasteid' => $pasteid));
                        KEventManager::trigger('PasteDeleted', array(
                            'pasteid'   => $pasteid,
                            'userid'    => $this->authData->userid,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                    }
                    return $this->redirect()->toRoute( 'paster', array('action' => 'viewMyPastes') );
                }
                return array('pasteTitle' => $paste->title, 'pasteId' => $pasteid);
            }
            else
            {
                return array('error' => true, 'result' => 'ACCESS_DENIED');
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function editPasteAction()
    {
        $pasteid = (int)$this->params()->fromRoute('param1');
        if(!$pasteid)
        {
            $this->redirect()->toRoute('paster', array('action' => 'viewMyPastes'));
        }
        
        //if the user is logged in
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'paster')
        {
            $paste = $this->getPastesTable()->fetchPaste(array('pasteid' => $pasteid));
            //if the logged in user is the owner of the paste
            if($this->authData->userid == $paste->userid)
            {
                //check if the paste is not closed
                if('closed' == $paste->status)
                {
                    return array('error' => true, 'result' => 'PASTE_IS_CLOSED');
                }
                $request = $this->getRequest();
                //if the paste is encrypted, we have to decrypt it before showing it for edition
                if($paste->password_test)
                {
                    //if the password for decrypting the paste has been submitted
                    if($request->isPost() && $request->getPost('_password'))
                    {
                        $cipher = new Cipher($request->getPost('_password'));
                        $passwordTest = trim($cipher->decrypt($paste->password_test));
                        if($passwordTest != 'PASSWORD_IS_OK')
                        {
                            return array('error' => true, 'result' => 'INVALID_PASSWORD');
                        }
                        $paste->paste = $cipher->decrypt($paste->paste);
                        $form = new NewPasteForm();
                        $form->bind($paste);
                        return array('pasteForm' => $form, 'pasteid' => $pasteid);
                    }
                    //if the password for decrypting the paste is not submitted but the 
                    //request is post, then the new paste info has been submitted
                    else if($request->isPost())
                    {
                        $form = new NewPasteForm();
                        $form->setData($request->getPost());
                        if($form->isValid())
                        {
                            $newPaste = new Pastes();
                            $newPaste->exchangeArray($request->getPost());
                            //Encrypt the paste if password is set
                            if($request->getPost('password') && strlen($request->getPost('password')))
                            {
                                $cipher = new Cipher($request->getPost('password'));
                                $newPaste->password_test = $cipher->encrypt('PASSWORD_IS_OK');
                                $newPaste->paste = $cipher->encrypt($newPaste->paste);
                            }
                            else
                            {
                                $newPaste->password_test = null;
                            }
                            $newPaste->pasteid = $pasteid;
                            $newPaste->userid = $this->authData->userid;
                            $newPaste->status = 'active';
                            $newPaste->pasted_on = date('Y-m-d H:i:s');

                            $this->getPastesTable()->savePaste($newPaste);
                            KEventManager::trigger('PasteEdited', array(
                                'pasteid'   => $pasteid,
                                'userid'    => $this->authData->userid,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            return array('error' => false, 'PASTE_SAVED_SUCCESSFULLY');
                        }
                        else
                        {
                            return array('error' => true, 'result' => 'INVALID_DATA_PROVIDED', 
                                'pasteForm' => $form);
                        }
                    }
                    //if there is no post data, show the paste password form
                    else
                    {
                        $form = new PastePasswordForm();
                        //we have to change this field to distinguish between the pastePasswordForm
                        //and the newPasteForm
                        $form->get('password')->setAttribute('name', '_password');
                        return array('pastePasswordForm' => $form, 'pasteid' => $pasteid);
                    }
                }
                //if the paste is not encrypted we can show it for edition right away
                else 
                {
                    $form = new NewPasteForm();
                    $request = $this->getRequest();
                    if($request->isPost())
                    {
                        $form->setData($request->getPost());
                        if($form->isValid())
                        {
                            $newPaste = new Pastes();
                            $newPaste->exchangeArray($request->getPost());
                            //Encrypt the paste if password is set
                            if($request->getPost('password') && strlen($request->getPost('password')))
                            {
                                $cipher = new Cipher($request->getPost('password'));
                                $newPaste->password_test = $cipher->encrypt('PASSWORD_IS_OK');
                                $newPaste->paste = $cipher->encrypt($newPaste->paste);
                            }
                            else
                            {
                                $newPaste->password_test = null;
                            }
                            $newPaste->pasteid = $pasteid;
                            $newPaste->userid = $this->authData->userid;
                            $newPaste->status = 'active';
                            $newPaste->pasted_on = date('Y-m-d H:i:s');
                            $this->getPastesTable()->savePaste($newPaste);
                            KEventManager::trigger('PasteEdited', array(
                                'pasteid'   => $pasteid,
                                'userid'    => $this->authData->userid,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            return array('error' => false, 'PASTE_SAVED_SUCCESSFULLY');
                        }
                        else
                        {
                            return array('error' => true, 'result' => 'INVALID_DATA_PROVIDED', 
                                'pasteForm' => $form);
                        }
                    }
                    $form->bind($paste);
                    return array('pasteForm' => $form, 'pasteid' => $pasteid);
                }
            }
            //if the logged in user is not the owner of the paste
            else
            {
                return array('error' => true, 'result' => 'ACCESS_DENIED');
            }
        }
        //if the user is not logged in
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function earningsAction()
    {
        if($this->authData && $this->authData->userid && 'paster' == $this->authData->user_type)
        {
            $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
            return array(
                'checkouts'             => $this->getCheckoutsTable()->fetchCheckouts(array(
                                                'userid' => $this->authData->userid
                                            )),
                'unpaidForViews'        => $user->unpaid_for_views,
                'pricePerThousand'      => $this->settings['payment-per-thousand'],
                'minBalanceForCheckout' => $this->settings['min-balance-for-checkout'],
                'currency'              => $this->settings['currency'],
            );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function checkoutAction()
    {
        if($this->authData && $this->authData->userid && 'paster' == $this->authData->user_type)
        {
            $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
            $balance = $this->settings['payment-per-thousand'] * ($user->unpaid_for_views / 1000);
            $min = $this->settings["min-balance-for-checkout"];
            if($balance < $min)
            {
                return( array(
                    'error'         => true,
                    'result'        => 'NOT_ENOUGH_BALANCE_TO_CHECKOUT',
                    'balance'       => $balance,
                    'min'           => $min,
                    'currency'      => $this->settings['currency'],
                ) );
            }
            
            $request = $this->getRequest();
            if($request->isPost())
            {
                $confirmed = $request->getPost('confirm','NO');
                if('YES' != $confirmed)
                {
                    return $this->redirect()->toRoute('paster', array('action' => 'earnings'));
                }
                
                if( $this->authData->account_status != 'verified' )
                {
                    return( array( 
                        'error'     => true,
                        'result'    => 'ACCOUNT_NOT_VERIFIED',
                    ) );
                }
                
                $checkout = new Checkouts();
                $checkout->userid               = $this->authData->userid;
                $checkout->datetime_requested   = date('Y-m-d H:i:s');
                $checkout->amount               = $balance;
                $checkout->status               = 'pending';
                $checkout->transaction_tracking_code    = 
                $checkout->transaction_datetime         =
                $checkout->description                  = null;
                $this->getCheckoutsTable()->saveCheckout($checkout);
                
                $user->unpaid_for_views = 0;
                $this->getUsersTable()->saveUser($user);
                KEventManager::trigger('CheckoutRegistered', array(
                    'userid'    => $this->authData->userid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return array('error' => false, 'result' => 'CHECKOUT_REGISTERED');
            }
            
            return array('balance' => $balance, 'currency' => $this->settings['currency']);
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'pasteid'   => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function RemoveOldCharts()
    {
        $folder = ROOT_PATH . '/images/charts/';
        $filetypes = "*.png";
        $expiretime = 60;      //expire time in minutes, 1 hour = 60
        
        foreach (glob($folder . $filetypes) as $Filename) {
            // Read file creation time
            $FileCreationTime = filectime($Filename);
            // Calculate file age in seconds
            $FileAge = time() - $FileCreationTime;
            // Is the file older than the given time span?
            if ($FileAge > ($expiretime * 60)) {
                //delete files:
                unlink($Filename);
            }
        }
    }
    
    private function getPastesTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PastesTable');
    }
    
    private function getPasteViewsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PasteViewsTable');
    }
    private function getReportedPastesTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ReportedPastesTable');
    }
    
    private function getThumbsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ThumbsTable');
    }
    private function getCheckoutsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\CheckoutsTable');
    }
    
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
}

?>
