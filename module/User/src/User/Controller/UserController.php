<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Db\Sql\Where as SqlWhere;
use Zend\Session\Container as SessionContainer;
use User\Model\User;
use User\Model\PasswordChangeRequests;
use User\Form\LoginForm;
use User\Form\SignupForm;
use User\Form\VerifyEmailForm;
use User\Form\ChangePasswordPhase1Form;
use User\Form\ChangePasswordPhase2Form;
use User\Form\PasswordChangeForm;
use KpasteCore\Cipher\Cipher;
use KpasteCore\RandomSequenceGenerator;
use KpasteCore\EmailSender;
use KpasteCore\IpToCountry\IpToCountry;

class UserController extends AbstractActionController 
{
    public $usersTable;
    public $passwordChangeRequestsTable;
    public $permissionsTable;
    public $authData;
    public $acl;
    public $settings;
    
    public function __construct()
    {
        $auth = new AuthenticationService();
        $this->authData = unserialize( $auth->getStorage()->read() );
        $session = new SessionContainer( 'acl' );
        $this->acl = $session->acl;
        $settings = new SessionContainer('settings');
        $this->settings = $settings->settings;
    }
    
    
    public function indexAction() 
    {
        $this->redirect()->toRoute('user', array('action' => 'login'));
    }
    
    public function loginAction()
    {
        $MAX_WRONG_LOGIN_ATTEMPTS   = 5;
        $LOGIN_DELAY                = 5;    //minutes
        //check to see if there's login cookies, and no session data
        $auth = new AuthenticationService();
        $authStorage = $auth->getStorage();
        $authData = unserialize($authStorage->read());
        
        $wrongLoginAttempts = new SessionContainer('wrongLoginAttempts');
        
        if(!$authData)
        {
            $cipher = new Cipher();

            $__a = isset($this->getRequest()->getHeaders()->get('Cookie')->__a) ?
                    $this->getRequest()->getHeaders()->get('Cookie')->__a : null;
            $__b = isset($this->getRequest()->getHeaders()->get('Cookie')->__b) ?
                    $this->getRequest()->getHeaders()->get('Cookie')->__b : null;
            $__c = isset($this->getRequest()->getHeaders()->get('Cookie')->__c) ?
                    $this->getRequest()->getHeaders()->get('Cookie')->__c : null;
            if($__c && trim($cipher->decrypt($__c)) == $this->getRequest()->getServer('REMOTE_ADDR'))
            {
                $username = $cipher->decrypt($__a);
                $password = $cipher->decrypt($__b);
                $user = $this->getUsersTable()->fetchUserEx(array(
                    'username'  => $username,
                    'password'  => $password,
                ));
                $authStorage->write(serialize($user));
                
                //if it's an admin, generate the acl
                if($user->user_type == 'admin' || $user->user_type == 'masteradmin')
                {
                    $permissions = $this->getPermissionsTable()->fetchAdminsPermissions($user->userid);
                    $aclSession = new SessionContainer('acl');
                    $aclSession->acl = $permissions;
                }
                
                if(strlen($this->params()->fromRoute('continue')))
                    $this->redirect()->toUrl(urldecode($this->params()->fromRoute('continue')));
                else 
                    $this->redirect ()->toRoute('kpastecore');
            }
        }
        
        //check for wrong password attempts and reset if necessary
        if($wrongLoginAttempts->count && $wrongLoginAttempts->count >= $MAX_WRONG_LOGIN_ATTEMPTS)
        {
            $remainingDelay = $LOGIN_DELAY * 60;    //set the delay to 5 minutes
            $remainingDelay -= time() - $wrongLoginAttempts->loginDelayStartTime;
            if( $remainingDelay > 0 )
            {
                $minutes = floor($remainingDelay / 60);
                $minutes = ($minutes < 10) ? '0' . $minutes : $minutes;
                $seconds = $remainingDelay - $minutes * 60;
                $seconds = ($seconds < 10) ? '0' . $seconds : $seconds;
                
                return( array(
                    'error'             => true,
                    'result'            => 'TOO_MANY_WRONG_LOGIN_ATTEMPTS',
                    'remainingDelay'    => $minutes . ':' . $seconds,
                    'maxAttempts'       => $MAX_WRONG_LOGIN_ATTEMPTS,
                ));
            }
            else
            {
                $wrongLoginAttempts->count = 0;
                $wrongLoginAttempts->loginDelayStartTime = null;
            }
        }
        
        $form = new LoginForm();
        
        $request = $this->getRequest();
        if($request->isPost()) 
        {
            $user = new User();
            $form->setData($request->getPost());
            
            if($form->isValid()) 
            {
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                $authAdapter = new AuthAdapter($dbAdapter
                                               ,'users'
                                               ,'username'
                                               ,'password'
                                               ,'SHA1(CONCAT(SHA1(CONCAT(SHA1(?), salt)), username))');
                $authAdapter->setIdentity($request->getPost('username'));
                $authAdapter->setCredential($request->getPost('password'));
                $auth = new AuthenticationService();
                $result = $auth->authenticate($authAdapter);
                if($result->isValid())
                {
                    $user = $authAdapter->getResultRowObject();
                    $storage = $auth->getStorage();
                    
                    if($user->account_status != 'suspended')
                    {
                        if( $user->user_type == 'admin' && $user->account_status == 'pending' )
                        {
                            return( array( 
                                'error'     => true,
                                'result'    => 'ACCOUNT_NOT_VERIFIED',
                            ) );
                        }
                        if($request->getPost('keepMeSignedIn'))
                        {
                            $cipher = new Cipher();
                            $usernameC = $cipher->encrypt($user->username);
                            $passwordC = $cipher->encrypt($user->password);
                            $ipC = $cipher->encrypt($request->getServer('REMOTE_ADDR'));
                            $dummy1 = $cipher->encrypt('dummy data');
                            $dummy2 = $cipher->encrypt('yet another dummy data');
                            $cookieExpireTime = 60 * 60 * 24 * 7;   //one week
                            setcookie('__a', $usernameC, time() + $cookieExpireTime, '/');
                            setcookie('__b', $passwordC, time() + $cookieExpireTime, '/');
                            setcookie('__c', $ipC, time() + $cookieExpireTime, '/');
                            setcookie('__d', $dummy1, time() + $cookieExpireTime, '/');
                            setcookie('__e', $dummy2, time() + $cookieExpireTime, '/');
                        }
                        //we have to serialize the data now, and then, before using
                        //it, unserialize it. Otherwise it would throw an exception
                        $userCompleteInfo = $this->getUsersTable()->fetchUser( 'userid', $user->userid );
                        $storage->write( serialize( $userCompleteInfo ) );
                        $userCompleteInfo->last_login_time = date('Y-m-d H:i:s');
                        $userCompleteInfo->last_ip_addr = $request->getServer('REMOTE_ADDR');
                        $this->getUsersTable()->saveUser($userCompleteInfo);
                        
                        //if it's an admin, generate the acl
                        if($user->user_type == 'admin' || $user->user_type == 'masteradmin')
                        {
                            $permissions = $this->getPermissionsTable()->fetchAdminsPermissions($user->userid);
                            $aclSession = new SessionContainer('acl');
                            $aclSession->acl = $permissions;
                        }
                    }
                    else 
                    {
                        $storage->clear();
                        return array('error' => true, 'result' => 'ACCOUNT_SUSPENDED');
                    }
                    KEventManager::trigger('UserLoggedIn', array(
                        'userid'    => $user->userid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    if(strlen($this->params()->fromRoute('continue')))
                    {
                        $this->redirect()->toUrl(urldecode($this->params()->fromRoute('continue')));
                    }
                    else
                    {
                        switch($user->user_type)
                        {
                            case 'paster':
                                return $this->redirect()->toRoute('paster');
                            case 'advertiser':
                                return $this->redirect()->toRoute('advertiser');
                            case 'admin':
                            case 'masteradmin':
                                return $this->redirect()->toRoute('admin');
                        }
                    }
                }
                else
                {
                    KEventManager::trigger('WrongLoginAttempt', array(
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    if(isset($wrongLoginAttempts->count))
                    {
                        $wrongLoginAttempts->count++;

                        if( $wrongLoginAttempts->count >= $MAX_WRONG_LOGIN_ATTEMPTS )
                        {
                            $wrongLoginAttempts->loginDelayStartTime = time();
                            return( array(
                                'error'             => true,
                                'result'            => 'TOO_MANY_WRONG_LOGIN_ATTEMPTS',
                                'remainingDelay'    => $LOGIN_DELAY . ':00',
                                'maxAttempts'       => $MAX_WRONG_LOGIN_ATTEMPTS,
                            ));
                        }
                    }
                    else
                        $wrongLoginAttempts->count = 1;
                    
                    switch($result->getCode()) 
                    {
                        case Result::FAILURE_CREDENTIAL_INVALID:
                            return array(
                                'error'             => true,
                                'result'            => 'INVALID_PASSWORD',
                                'loginForm'         => $form,
                                'returnurl'         => urldecode($this->params()->fromRoute('returnurl')),
                                'remainingAttempts' => $MAX_WRONG_LOGIN_ATTEMPTS - $wrongLoginAttempts->count,
                            );
                        case Result::FAILURE_IDENTITY_NOT_FOUND:
                            return array(
                                'error'             => true,
                                'result'            => 'INVALID_USERNAME',
                                'loginForm'         => $form,
                                'returnurl'         => urldecode($this->params()->fromRoute('returnurl')),
                                'remainingAttempts' => $MAX_WRONG_LOGIN_ATTEMPTS - $wrongLoginAttempts->count,
                            );
                        default:
                            return array(
                                'error'             => true,
                                'result'            => 'UNKNOWN_LOGIN_ERROR',
                                'loginForm'         => $form,
                                'returnurl'         => urldecode($this->params()->fromRoute('returnurl')),
                            );
                    }
                }
            }
            
        }
        
        return array('loginForm' => $form,
                     'returnurl' => urldecode($this->params()->fromRoute('returnurl')));
    }
    
    public function logoutAction() 
    {
        $auth = new AuthenticationService();
        $storage = $auth->getStorage();
        if(isset($this->authData->userid) && $this->authData->userid)
        {
            KEventManager::trigger('WrongLoginAttempt', array(
                'userid'    => $this->authData->userid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
        }
        $storage->clear();
        $settingsSession = new SessionContainer('acl');
        $settingsSession->acl = null;
        setcookie('__a', null, time() - 1, '/');
        setcookie('__b', null, time() - 1, '/');
        setcookie('__c', null, time() - 1, '/');
        $this->redirect()->toRoute();
    }
    
    public function signupAction() 
    {
        if($this->authData && $this->authData->userid)
        {
            return(array(
                'error'     => true,
                'result'    => 'ALREADY_A_USER',
            ));
        }
        
        $form = new SignupForm();
        $request = $this->getRequest();
        $advertiser = $this->params()->fromQuery('advertiser', false);
        $lang = $this->params()->fromRoute('lang');
        if( !$lang )    $lang = substr($this->settings['language'], 0, 2);
        if($request->isPost()) 
        {
            $user = new User();
            $form->setData($request->getPost());
            
            if($form->isValid()) 
            {
                $data = $form->getData();

                //check for username existance
                $existingUser = $this->getUsersTable()->fetchUser('username',$data['username']);
                if($existingUser) {
                    return array(
                        'error'     => TRUE,
                        'result'    => 'USER_NAME_ALREADY_EXISTS',
                        'form'      => $form,
                        'advertiser'=> $advertiser,
                    );
                }
                
                //check for email existence
                $existingUser = null;
                $existingUser = $this->getUsersTable()->fetchUser('email',$data['email']);
                if($existingUser) {
                    return array(
                        'error'     => TRUE,
                        'result'    => 'EMAIL_ALREADY_EXISTS',
                        'form'      => $form,
                        'advertiser'=> $advertiser,
                    );
                }
                
                $user->exchangeArray($data);
                $user->reg_date = date('Y-m-d H:i:s');
                $user->salt = RandomSequenceGenerator::generateRandomSequence(16);
                $password = $data['password'];
                $user->password = sha1(sha1(sha1($password) . $user->salt) . $user->username);
                $user->email_verification_code = 
                    sha1(RandomSequenceGenerator::generateRandomSequence(40) . microtime());
                $user->account_status = 'pending';
                $user->user_type = 'paster';
                $user->total_pastes = 0;
                $user->total_views = 0;
                $userid = $this->getUsersTable()->saveUser( $user );
                $user->userid = $userid;
                
                //after successful signup , sign the user in
                $auth = new AuthenticationService();
                $authStorage = $auth->getStorage();
                $authStorage->write( serialize( $user ) );               
                
                $translator = $this->getServiceLocator()->get( 'translator' );
                $verifyLink = $this->url()->fromRoute('user', array(
                    'lang'                      => $lang,
                    'action'                    => 'verifyEmail',
                    'request_confirmation_code' => $user->email_verification_code,
                ));
                $emailSender = new EmailSender( $request->getServer( 'HTTP_HOST' ), $translator, $lang );
                $emailSender->sendEmailVerificationEmail(
                        $user->email,
                        $verifyLink, 
                        $user->firstname
                );
                
                KEventManager::trigger('NewUserSignedUp', array(
                    'userid'    => $user->userid,
                    'cellnumber'=> $user->cell_number,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                
                return array(
                    'error'     => FALSE,
                    'result'    => 'REGISTRATION_SUCCESSFUL',
                    'usertype'  => $user->user_type,
                );
            } else {
                return array(
                    'error'     => true,
                    'result'    => 'INVALID_VALUE_PROVIDED',
                    'form'      => $form,
                    'advertiser'=> $advertiser,
                );
            }
        }
        
        return array('form' => $form, 'advertiser'=> $advertiser);
    }
    
    public function verifyEmailAction() 
    {
        $form = new VerifyEmailForm();
        $request = $this->getRequest();
        $code = $this->params()->fromRoute('request_confirmation_code');
        if($request->isPost()) {
            $form->setData($request->getPost());
            if($form->isValid()) {
                $user = $this->getUsersTable()->fetchUser('email_verification_code',$request->getPost()->email_verification_code);
                
                if(!$user) {
                    KEventManager::trigger('WrongEmailVerification', array(
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    return array(
                        'error'     => true,
                        'result'    => 'UNKNOWN_EMAIL_VERIFICATION_CODE',
                    );
                }
                
                $user->account_status = 'verified';
                $user->email_verification_code = '';
                $this->getUsersTable()->saveUser($user);
                
                $auth = new AuthenticationService();
                $storage = $auth->getStorage();
                //logout. Force the user to login again
                $storage->clear();
                KEventManager::trigger('EmailVerified', array(
                    'userid'    => $user->userid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return array('error' => false, 'result' => 'EMAIL_VERIFIED_SUCCESSFULLY');
            }
        }
        else if($code)
        {
            $user = $this->getUsersTable()->fetchUser('email_verification_code',$code);
                
            if(!$user) {
                KEventManager::trigger('WrongEmailVerification', array(
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return array(
                    'error'     => true,
                    'result'    => 'UNKNOWN_EMAIL_VERIFICATION_CODE',
                );
            }

            $user->account_status = 'verified';
            $user->email_verification_code = '';
            $this->getUsersTable()->saveUser($user);

            $auth = new AuthenticationService();
            $storage = $auth->getStorage();
            //logout. Force the user to login again
            $storage->clear();
            KEventManager::trigger('EmailVerified', array(
                'userid'    => $user->userid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => false, 'result' => 'EMAIL_VERIFIED_SUCCESSFULLY');
        }
        
        return array('form' => $form);
    }
    
    public function ResendVerificationEmailAction()
    {
        $auth = new AuthenticationService();
        $authStorage = $auth->getStorage();
        $authData = unserialize($authStorage->read());
        $lang = $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2));
        if($authData && $authData->userid)
        {
            if( $authData->account_status != 'pending' )
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'ACCOUNT_NOT_PENDING',
                ) );
            }
            $hostname = $this->getRequest()->getServer('HTTP_HOST');
            $translator = $this->getServiceLocator()->get('translator');
            $emailSender = new EmailSender( $hostname, $translator, $lang );
            $verifyLink = $this->url()->fromRoute( 'user', array(
                'lang'                      => $lang,
                'action'                    => 'verifyEmail',
                'request_confirmation_code' => $authData->email_verification_code,
            ) );
            $emailSender->sendEmailVerificationEmail( $authData->email, $verifyLink, $authData->firstname );
            
            KEventManager::trigger( 'NewVerificationEmailSent', array(
                'userid'    => $this->authData->userid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator() );
            
            return( array( 
                'error'     => false
            ));
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
    
    public function changePasswordAction() 
    {
        //first of all, check if the requestor is logged in
        $auth = new AuthenticationService();
        $authStorage = $auth->getStorage();
        $authData = unserialize($authStorage->read());
        if($authData && $authData->userid) 
        {
            $form = new PasswordChangeForm();
            $request = $this->getRequest();
            if($request->isPost()) 
            {
                $form->setData($request->getPost());
                if($form->isValid()) 
                {
                    $currentPassHashed = sha1(sha1(sha1($request->getPost('current_password')
                            ) . $authData->salt) . $authData->username);
                    if($authData->password == $currentPassHashed) 
                    {
                        if($request->getPost('new_password') == $request->getPost('new_password_repeat')) 
                        {
                            $newPassHashed = sha1(sha1(sha1($request->getPost('new_password')) 
                                    . $authData->salt) . $authData->username);
                            $authData->password = $newPassHashed;
                            $this->getUsersTable()->saveUser($authData);
                            $auth->getStorage()->clear();
                            KEventManager::trigger( 'PasswordChanged', array(
                                'userid'    => $this->authData->userid,
                                'cellnumber'=> $this->authData->cell_number,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator() );
                            return array('error' => false, 'result' => 'PASSWORD_CHANGED_SUCCESSFULLY');
                        }
                        else 
                        {
                            return array('error' => true, 'result' => 'PASSWORDS_DO_NOT_MATCH', 
                                'form' => $form);
                        }
                    }
                    else 
                    {
                        KEventManager::trigger( 'PasswordChangeFailed', array(
                            'userid'    => $this->authData->userid,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator() );
                        return array('error' => true, 'result' => 'CURRENT_PASSWORD_IS_WRONG',
                                'form' => $form);
                    }
                }
                else 
                {
                    return array('error' => true, 'result' => 'INVALID_DATA_PROVIDED',
                                'form' => $form);
                }
            }   //if($request->isPost())
            
            return array('form' => $form);
        }   //if($authData)
        else 
        {
            $confirmation = $this->params()->fromRoute('request_confirmation_code');
            //if confirmation code hasn't been sent, or its format is invalid
            //take it to the first step
            if( !$confirmation || strlen( $confirmation ) != 40 ) 
            {
                $form = new ChangePasswordPhase1Form();
                $request = $this->getRequest();
                $lang = $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2));
                if($request->isPost()) 
                {
                    $form->setData($request->getPost());
                    if($form->isValid()) 
                    {
                        $email = $request->getPost('email');
                        $user = $this->getUsersTable()->fetchUser('email', $email);
                        if(!$user) 
                        {
                            KEventManager::trigger( 'InvalidEmailForPasswordChange', array(
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator() );
                            return array('error' => true, 'result' => 'INVALID_EMAIL_PROVIDED');
                        }

                        //check to see if the user has requested in the past week.
                        //if so, dispatch an error
                        $where = new SqlWhere();
                        $where->greaterThan('request_time', date('Y-m-d H:i:s',time() - (60 * 60 * 24)))
                                ->AND->equalTo('userid', $user->userid);
                        $pcrsInLastWeek = $this->getPasswordChangeRequestsTable()
                                ->fetchRequest($where);

                        if($pcrsInLastWeek) 
                        {
                            KEventManager::trigger( 'TooManyPasswordChangeRequests', array(
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator() );
                            return array(
                                'error'         => true,
                                'result'        => 'TOO_MANY_PASSWORD_CHANGE_REQUESTS',
                                'last_request'  => $pcrsInLastWeek->request_time,
                                );
                        }

                        //generate a random confirmation code
                        $request_confirmation_code = 
                                sha1(RandomSequenceGenerator::generateRandomSequence(41) 
                                        . microtime());

                        $pcrData = array(
                                        'userid'                    => $user->userid,
                                        'request_time'              => date('Y-m-d H:i:s'),
                                        'request_confirmation_code' => $request_confirmation_code,
                                        'status'                    => 'pending',
                                        );
                        $pcr = new PasswordChangeRequests();
                        $pcr->exchangeArray($pcrData);

                        $this->getPasswordChangeRequestsTable()->saveRequest($pcr);

                        $translator = $this->getServiceLocator()->get('translator');
                        $url = $this->url()->fromRoute('user', array(
                            'lang'                      => $lang, 
                            'action'                    => 'changePassword', 
                            'request_confirmation_code' => $request_confirmation_code
                        ));

                        $emailSender = new EmailSender( $request->getServer( 'HTTP_HOST' ), $translator, $lang);
                        $emailSender->sendPasswordChangeEmail(
                            $user->email, 
                            $url,
                            $user->firstname
                        );
                        
                        KEventManager::trigger( 'PasswordChangeRequestRegistered', array(
                            'userid'    => $user->userid,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator() );
                        
                        return array('error' => false, 'result' => 'PASSWORD_CHANGE_REQUEST_SUCCESSFULLY_REGISTERED');
                    }
                    else 
                    {
                        return array('error' => true, 'result' => 'INVALID_DATA_PROVIDED', 'phase1form' => $form);
                    }
                }

                return array('phase1form' => $form);
            }   //if( !$confirmation || strlen( $confirmation ) != 40 )
            else 
            {
                $passwordChangeRequest = 
                    $this->getPasswordChangeRequestsTable()
                    ->fetchRequest(array(
                        'request_confirmation_code' => $confirmation,
                        'status'                    => 'pending',
                    ));
                if($passwordChangeRequest) 
                {
                    $form = new ChangePasswordPhase2Form();
                    $request = $this->getRequest();
                    $user = $this->getUsersTable()->fetchUser('userid', $passwordChangeRequest->userid);

                    if($request->isPost())
                    {
                        $form->setData($request->getPost());
                        if($form->isValid())
                        {
                            if($request->getPost('security_question_answer') == 
                                    $user->security_question_answer)
                            {
                                $newPassword = 
                                RandomSequenceGenerator::generateRandomSequence(32, 
                                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=');

                                //check to see if the request hasn't expired
                                //The expiration period is 2 hours
                                if(strtotime($passwordChangeRequest->request_time) < strtotime('-2 hours')) 
                                {
                                    return array(
                                        'error'     => true,
                                        'result'    => 'PASSWORD_CHANGE_REQUEST_HAS_EXPIRED',
                                    );
                                }

                                $user->password = sha1(sha1(sha1($newPassword) . $user->salt) . $user->username);
                                $this->getUsersTable()->saveUser($user);

                                $passwordChangeRequest->status = 'completed';

                                $this->getPasswordChangeRequestsTable()->saveRequest($passwordChangeRequest);

                                $authStorage->write(serialize($user));
                                $form = new PasswordChangeForm();
                                $form->get('current_password')->setValue($newPassword);

                                KEventManager::trigger( 'PasswordChanged', array(
                                    'userid'    => $this->authData->userid,
                                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                                ), $this->getServiceLocator() );
                                return array(
                                    'error'         => false,
                                    'result'        => 'PASSWORD_CHANGED_SUCCESSFULLY',
                                    'newPassword'   => $newPassword,
                                    'form'          => $form,
                                );
                            }
                            else
                            {
                                return array(
                                            'error'         => true, 
                                            'result'        => 'INCORRECT_SECURITY_QUESTION_ANSWER',
                                            'phase2form'    => $form,
                                            'confirmation' => $confirmation,
                                            'question'      => $user->security_question,
                                        );
                            }
                        }
                        else
                        {
                            return array('error' => true, 'result' => 'INVALID_DATA_PROVIDED', 
                                    'phase2form' => $form, 'confirmation' => $confirmation
                                    ,'question'      => $user->security_question,);
                        }
                    }
                    return array('phase2form' => $form, 'confirmation' => $confirmation,
                        'question'      => $user->security_question,);
                    
                }   //if($passwordChangeRequest)
                else 
                {
                    return array(
                        'error'     => true,
                        'result'    => 'INVALID_PASSWORD_CHANGE_CONFIRMATION_CODE',
                    );
                }
            }   //else ->if( !$confirmation || strlen( $confirmation ) != 40 )

        }   // else ->if($authData)
    }
    
    public function MyAccountAction()
    {
        if( $this->authData && $this->authData->userid )
        {
            $request = $this->getRequest();
            
            if( $request->isPost() )
            {
                $cell_phone             = $request->getPost( 'cell_phone' );
                $credit_card_number     = $request->getPost( 'credit_card_number' );
                $zarinpalid             = $request->getPost( 'zarinpalid' );
                
                $user = $this->getUsersTable()->fetchUser( 'userid', $this->authData->userid );
                if( $cell_phone         ) $user->cell_number        = $cell_phone;
                if( $credit_card_number ) $user->credit_card_no     = $credit_card_number;
                if( $zarinpalid         ) $user->zarinpalid         = $zarinpalid;
                $this->getUsersTable()->saveUser( $user );
                $auth = new AuthenticationService();
                $auth->getStorage()->clear();
                $auth->getStorage()->write( serialize( $user ) );
                KEventManager::trigger( 'AccountInformationChanged', array(
                    'email'     => $user->email,
                    'cellnumber'=> $user->cell_number,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator() );
            }
            
            $retval = array();
            $retval['user'] = $this->getUsersTable()->fetchUser( 'userid', $this->authData->userid );
            
            if( $this->authData->user_type == 'admin' )
            {
                $retval['acl'] = $this->acl;
            }
            
            $ipInfo = new IpToCountry( $this->getServiceLocator() );
            $retval['ipInfo'] = $ipInfo;
            
            return($retval);
        }
        KEventManager::trigger('UnauthorizedAccess', array(
            'url'       => $this->getRequest()->getUri(),
            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
        ), $this->getServiceLocator());
        return(array(
            'error'     => true,
            'result'    => 'ACCESS_DENIED',
        ));
    }
    
    public function CheckUserExistanceAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $fieldName = $request->getPost('fieldname');
            $fieldValue = $request->getPost('fieldvalue');
            
            $user = $this->getUsersTable()->fetchUser($fieldName, $fieldValue);
            $exists = ($user) ? 'true' : 'false';
            
            return($this->getResponse()->setContent($exists));
        }
        return($this->getResponse()->setContent('false'));
    }
    
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
    
    private function getPermissionsTable()
    {
            return $this->getServiceLocator()->get('KpasteCore\Model\PermissionsTable');
    }
    
    public function getPasswordChangeRequestsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\PasswordChangeRequestTable');
    }
}