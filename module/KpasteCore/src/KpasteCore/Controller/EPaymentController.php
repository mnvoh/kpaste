<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    EPaymentController.php
 * @createdat   Oct 19, 2013 12:20:53 PM
 */

namespace KpasteCore\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use Advertiser\Model\Transactions;

class EPaymentController extends AbstractActionController
{
    protected   $authData;
    protected   $settings;
    private     $api = array(
        'saman'     => 'gt7333g799',
        'melat'     => 'gt7334g227',
        'meli'      => 'gt7335g394',
        'parsian'   => 'gt7336g874',
        'jahanpay'  => 'gt7410g244',
    );
    
    public function __construct() 
    {
        $auth = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings = new SessionContainer('settings');
        $this->settings = $settings->settings;
    }
    
    public function indexAction() 
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $request = $this->getRequest();
            $lang = $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2));
            if(!$request->isPost())
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_DATA_SENT',
                ));
            }
            
            $amount = (int)$request->getPost('amount', 100000);
            $gateway = $request->getPost('payment_gateway', 'melat');
            
            if($amount < $this->settings['min-recharge-amount'])
            {
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_LOWER_THAN_MINIMUM',
                    'minAmount' => $this->settings['min-recharge-amount'],
                    'currency'  => $this->settings['currency'],
                ));
            }
            
            return(array(
                'amount'    => $amount,
                'currency'  => $this->settings['currency'],
                'gateway'   => $gateway,
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
    
    public function zarinpalAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
        $zarinpalStatusCodes = array(
            -1  => $translator->translate('The submitted data is incomplete!'),
            -2  => $translator->translate('IP or merchant ID is invalid!'),
            -3  => $translator->translate('Amount must be above 100t!'),
            -4  => $translator->translate('The reciever\'s clearance level is below silver!'),
            -11 => $translator->translate('The request was not found!'),
            -21 => $translator->translate('No financial operation is associated with this transaction!'),
            -22 => $translator->translate('Transaction Failed!'),
            -33 => $translator->translate('The transaction amount and the amount payed do not match!'),
            -54 => $translator->translate('The request has been archived!'),
            100 => $translator->translate('The operation was successful!'),
            101 => $translator->translate('The operation was successful but PaymentVerification has been done before!'),
        );
        
        $request = $this->getRequest();
        $merchantID = '52272ba8-063c-4150-b04b-541a5ee8a9d4';
        $au = $this->params()->fromQuery('Authority');
        //if callback
        if($au && strlen($au) == 36)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            $client = new \SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 

            $result = $client->PaymentVerification(array(
                    'MerchantID'    => $merchantID,
                    'Authority'     => $au,
                    'Amount'        => $transaction->amount,
            ));

            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }

            if( $result->Status == 100 )
            {
                $transaction->status                = "completed";
                $transaction->receipt               = $result->RefID;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'zarinpal',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $zarinpalStatusCodes[$result->Status],
                ));
            }
        }
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $email = $this->authData->email;
            $mobile = $this->authData->cell_number;
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'zarinpal',
            ), array('force_canonical'  => true));
            
            // URL also Can be https://ir.zarinpal.com/pg/services/WebGate/wsdl
            $client = new \SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 

            $result = $client->PaymentRequest(array(
                    'MerchantID' 	=> $merchantID,
                    'Amount'            => $amount,
                    'Description' 	=> $description,
                    'Email'             => $email,
                    'Mobile'            => $mobile,
                    'CallbackURL' 	=> $callback
            ));

            if($result->Status == 100 && strlen($result->Authority) == 36)
            {
                $au = $result->Authority;
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result->Authority;
                $transaction->receipt   = null;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'zarinpal',
                ), $this->getServiceLocator());
                
                $url = 'https://www.zarinpal.com/pg/StartPay/'.$result->Authority;
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $zarinpalStatusCodes[$result->Status],
                ));
            }
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function melatAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
##################################################
        ##                              ##
        ##              MELAT           ##
        ##                              ##
##################################################
        $jahanpayStatusCodes = array(
            -6  => $translator->translate('The payment gateway is down at the moment. Please try again or choose another gateway.'),
            -9  => $translator->translate('An unknown error has occured.'),
            -20 => $translator->translate('API is invalid!'),
            -21 => $translator->translate('IP is invalid!'),
            -22 => $translator->translate('Amount is lower than the minimum.'),
            -23 => $translator->translate('Amount is higher than the maximum.'),
            -24 => $translator->translate('Invalid amount.'),
            -26 => $translator->translate('The gateway is inactive.'),
            -27 => $translator->translate('Your IP address has been banned!'),
            -29 => $translator->translate('Callback is empty!'),
            -30 => $translator->translate('No such transaction.'),
            -31 => $translator->translate('Transaction was not completed!'),
            -32 => $translator->translate('The transaction amount and the amount payed do not match!'),
            1   => $translator->translate('Transaction was successful.'),
        );
        
        $request = $this->getRequest();
        
##################################################
        ##                              ##
        ##              MELAT           ##
        ##                              ##
##################################################
        
        $au = $this->params()->fromQuery('au');
        //if callback
        if($au)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            if(!$transaction)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_SUCH_TRANSACTION',
                ));
            }
            
            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 

##################################################
        ##                              ##
        ##              MELAT           ##
        ##                              ##
##################################################
            $result = $client->verification($this->api['melat'], (int)$transaction->amount, $au);
            
            if( !empty($result) and $result == 1 )
            {
                $transaction->status                = "completed";
                $transaction->receipt   = $transaction->transactionid;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'melat_jahanpay',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
        }
##################################################
        ##                              ##
        ##              MELAT           ##
        ##                              ##
##################################################
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'melat',
            ), array('force_canonical'  => true));
            $orderid = $this->getTransactionsTable()->getNextTid();
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 
            
            $result = $client->requestpayment($this->api['melat'],$amount, $callback, $orderid, $description);

            if( !( $result < 0 ) )
            {
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result;
                $transaction->receipt   = $orderid;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'melat_jahanpay',
                ), $this->getServiceLocator());
                
                $url = "http://www.jahanpay.com/pay_invoice/{$result}";
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
##################################################
        ##                              ##
        ##              MELAT           ##
        ##                              ##
##################################################
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function samanAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
        $jahanpayStatusCodes = array(
            -6  => $translator->translate('The payment gateway is down at the moment. Please try again or choose another gateway.'),
            -9  => $translator->translate('An unknown error has occured.'),
            -20 => $translator->translate('API is invalid!'),
            -21 => $translator->translate('IP is invalid!'),
            -22 => $translator->translate('Amount is lower than the minimum.'),
            -23 => $translator->translate('Amount is higher than the maximum.'),
            -24 => $translator->translate('Invalid amount.'),
            -26 => $translator->translate('The gateway is inactive.'),
            -27 => $translator->translate('Your IP address has been banned!'),
            -29 => $translator->translate('Callback is empty!'),
            -30 => $translator->translate('No such transaction.'),
            -31 => $translator->translate('Transaction was not completed!'),
            -32 => $translator->translate('The transaction amount and the amount payed do not match!'),
            1   => $translator->translate('Transaction was successful.'),
        );
        
        $request = $this->getRequest();

##################################################
        ##                              ##
        ##              SAMAN           ##
        ##                              ##
##################################################
        
        $au = $this->params()->fromQuery('au');
        //if callback
        if($au)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            if(!$transaction)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_SUCH_TRANSACTION',
                ));
            }
            
            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 

            $result = $client->verification($this->api['saman'], (int)$transaction->amount, $au);
            
##################################################
        ##                              ##
        ##              SAMAN           ##
        ##                              ##
##################################################
            
            if( !empty($result) and $result == 1 )
            {
                $transaction->status                = "completed";
                $transaction->receipt               = $transaction->transactionid;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'saman_jahanpay',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
        }

##################################################
        ##                              ##
        ##              SAMAN           ##
        ##                              ##
##################################################
#        
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'saman',
            ), array('force_canonical'  => true));
            $orderid = $this->getTransactionsTable()->getNextTid();
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 
            
            $result = $client->requestpayment($this->api['saman'],$amount, $callback, $orderid, $description);

            if( !( $result < 0 ) )
            {
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result;
                $transaction->receipt   = $orderid;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'saman_jahanpay',
                ), $this->getServiceLocator());
                
                
                $url = "http://www.jahanpay.com/pay_invoice/{$result}";
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
##################################################
        ##                              ##
        ##              SAMAN           ##
        ##                              ##
##################################################            
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function meliAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
        $jahanpayStatusCodes = array(
            -6  => $translator->translate('The payment gateway is down at the moment. Please try again or choose another gateway.'),
            -9  => $translator->translate('An unknown error has occured.'),
            -20 => $translator->translate('API is invalid!'),
            -21 => $translator->translate('IP is invalid!'),
            -22 => $translator->translate('Amount is lower than the minimum.'),
            -23 => $translator->translate('Amount is higher than the maximum.'),
            -24 => $translator->translate('Invalid amount.'),
            -26 => $translator->translate('The gateway is inactive.'),
            -27 => $translator->translate('Your IP address has been banned!'),
            -29 => $translator->translate('Callback is empty!'),
            -30 => $translator->translate('No such transaction.'),
            -31 => $translator->translate('Transaction was not completed!'),
            -32 => $translator->translate('The transaction amount and the amount payed do not match!'),
            1   => $translator->translate('Transaction was successful.'),
        );
        
        $request = $this->getRequest();

##################################################
        ##                              ##
        ##              MELI            ##
        ##                              ##
##################################################
        
        $au = $this->params()->fromQuery('au');
        //if callback
        if($au)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            if(!$transaction)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_SUCH_TRANSACTION',
                ));
            }
            
            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 

            $result = $client->verification($this->api['meli'], (int)$transaction->amount, $au);
            
##################################################
        ##                              ##
        ##              MELI            ##
        ##                              ##
##################################################
            
            if( !empty($result) and $result == 1 )
            {
                $transaction->status                = "completed";
                $transaction->receipt               = $transaction->transactionid;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'meli_jahanpay',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
        }

##################################################
        ##                              ##
        ##              MELI            ##
        ##                              ##
##################################################
#        
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'meli',
            ), array('force_canonical'  => true));
            $orderid = $this->getTransactionsTable()->getNextTid();
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 
            
            $result = $client->requestpayment($this->api['meli'],$amount, $callback, $orderid, $description);

            if( !( $result < 0 ) )
            {
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result;
                $transaction->receipt   = $orderid;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'meli_jahanpay',
                ), $this->getServiceLocator());
                
                $url = "http://www.jahanpay.com/pay_invoice/{$result}";
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
##################################################
        ##                              ##
        ##              MELI            ##
        ##                              ##
##################################################            
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function parsianAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
        $jahanpayStatusCodes = array(
            -6  => $translator->translate('The payment gateway is down at the moment. Please try again or choose another gateway.'),
            -9  => $translator->translate('An unknown error has occured.'),
            -20 => $translator->translate('API is invalid!'),
            -21 => $translator->translate('IP is invalid!'),
            -22 => $translator->translate('Amount is lower than the minimum.'),
            -23 => $translator->translate('Amount is higher than the maximum.'),
            -24 => $translator->translate('Invalid amount.'),
            -26 => $translator->translate('The gateway is inactive.'),
            -27 => $translator->translate('Your IP address has been banned!'),
            -29 => $translator->translate('Callback is empty!'),
            -30 => $translator->translate('No such transaction.'),
            -31 => $translator->translate('Transaction was not completed!'),
            -32 => $translator->translate('The transaction amount and the amount payed do not match!'),
            1   => $translator->translate('Transaction was successful.'),
        );
        
        $request = $this->getRequest();

##################################################
        ##                              ##
        ##           PARSIAN            ##
        ##                              ##
##################################################
        
        $au = $this->params()->fromQuery('au');
        //if callback
        if($au)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            if(!$transaction)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_SUCH_TRANSACTION',
                ));
            }
            
            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 

            $result = $client->verification($this->api['parsian'], (int)$transaction->amount, $au);
            
##################################################
        ##                              ##
        ##           PARSIAN            ##
        ##                              ##
##################################################
            
            if( !empty($result) and $result == 1 )
            {
                $transaction->status                = "completed";
                $transaction->receipt               = $transaction->transactionid;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'parsian_jahanpay',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
        }

##################################################
        ##                              ##
        ##           PARSIAN            ##
        ##                              ##
##################################################
#        
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'parsian',
            ), array('force_canonical'  => true));
            $orderid = $this->getTransactionsTable()->getNextTid();
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 
            
            $result = $client->requestpayment($this->api['parsian'],$amount, $callback, $orderid, $description);

            if( !( $result < 0 ) )
            {
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result;
                $transaction->receipt   = $orderid;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'parsian_jahanpay',
                ), $this->getServiceLocator());
                
                $url = "http://www.jahanpay.com/pay_invoice/{$result}";
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
##################################################
        ##                              ##
        ##           PARSIAN            ##
        ##                              ##
##################################################            
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function jahanpayAction()
    {
        if(!($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser'))
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
        $translator = $this->getServiceLocator()->get('translator');
        
        $jahanpayStatusCodes = array(
            -6  => $translator->translate('The payment gateway is down at the moment. Please try again or choose another gateway.'),
            -9  => $translator->translate('An unknown error has occured.'),
            -20 => $translator->translate('API is invalid!'),
            -21 => $translator->translate('IP is invalid!'),
            -22 => $translator->translate('Amount is lower than the minimum.'),
            -23 => $translator->translate('Amount is higher than the maximum.'),
            -24 => $translator->translate('Invalid amount.'),
            -26 => $translator->translate('The gateway is inactive.'),
            -27 => $translator->translate('Your IP address has been banned!'),
            -29 => $translator->translate('Callback is empty!'),
            -30 => $translator->translate('No such transaction.'),
            -31 => $translator->translate('Transaction was not completed!'),
            -32 => $translator->translate('The transaction amount and the amount payed do not match!'),
            1   => $translator->translate('Transaction was successful.'),
        );
        
        $request = $this->getRequest();

##################################################
        ##                              ##
        ##           JAHANPAY            ##
        ##                              ##
##################################################
        
        $au = $this->params()->fromQuery('au');
        //if callback
        if($au)
        {
            $transaction = $this->getTransactionsTable()->fetchTransactions(array(
                'au'    => $au,
            ))->current();

            if(!$transaction)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'NO_SUCH_TRANSACTION',
                ));
            }
            
            if( $transaction->status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'TRANSACTION_ALREADY_COMPLETED',
                ));
            }
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 

            $result = $client->verification($this->api['jahanpay'], (int)$transaction->amount, $au);
            
##################################################
        ##                              ##
        ##           JAHANPAY            ##
        ##                              ##
##################################################
            
            if( !empty($result) and $result == 1 )
            {
                $transaction->status                = "completed";
                $transaction->receipt               = $transaction->transactionid;
                $transaction->completed_datetime    = date('Y-m-d H:i:s');
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);

                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $user->account_balance += $transaction->amount;
                $this->getUsersTable()->saveUser( $user );

                KEventManager::trigger('InvoicePaid', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'jahanpay',
                ), $this->getServiceLocator());

                return(array(
                    'error'     => false,
                ));
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
        }

##################################################
        ##                              ##
        ##           JAHANPAY           ##
        ##                              ##
##################################################
#        
        //else if the user has opted an amount
        else if($request->isPost())
        {
            $amount = $request->getPost('amount', 0);
            if($amount <= 100)
                return(array(
                    'error'     => true,
                    'result'    => 'AMOUNT_TOO_LOW',
                ));

            $description = $request->getPost('description');
            $callback = $this->url()->fromRoute('kpastecore', array(
                'lang'      => substr($this->params()->fromRoute('lang', $this->settings['language']), 0, 2),
                'controller'=> 'EPayment',
                'action'    => 'jahanpay',
            ), array('force_canonical'  => true));
            $orderid = $this->getTransactionsTable()->getNextTid();
            
            $client = new \SoapClient('http://www.jahanpay.com/webservice?wsdl'); 
            
            $result = $client->requestpayment($this->api['jahanpay'],$amount, $callback, $orderid, $description);

            if( !( $result < 0 ) )
            {
                $transaction = new Transactions();
                $transaction->userid    = $this->authData->userid;
                $transaction->amount    = $amount;
                $transaction->status    = 'pending';
                $transaction->au        = $result;
                $transaction->receipt   = $orderid;
                $transaction->requested_datetime = date('Y-m-d H:i:s');
                $transaction->completed_datetime = null;
                $transactionid = $this->getTransactionsTable()->saveTransaction($transaction);
                
                KEventManager::trigger('InvoiceCreated', array(
                    'lang'          => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                    'amount'        => $amount .' '. $this->settings['currency'],
                    'date'          => $transaction->requested_datetime,
                    'description'   => $description,
                    'to'            => $this->authData->email,
                    'gateway'       => 'jahanpay',
                ), $this->getServiceLocator());
                
                $url = "http://www.jahanpay.com/pay_invoice/{$result}";
                $this->redirect()->toUrl($url);
            }
            else
            {
                return(array(
                    'error'     => true,
                    'result'    => 'GATEWAY_ERROR',
                    'errorCode' => $jahanpayStatusCodes[$result],
                ));
            }
##################################################
        ##                              ##
        ##           JAHANPAY           ##
        ##                              ##
##################################################            
        }
        else
        {
            return(array(
                'error'     => true,
                'result'    => 'NO_DATA_SENT',
            ));
        }
    }
    
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }

    private function getTransactionsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\TransactionsTable');
    }
}

?>
