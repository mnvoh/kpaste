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

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use Admin\Form\NewAdminForm;
use User\Model\User;
use KpasteCore\RandomSequenceGenerator;
use KpasteCore\EmailSender;
use KpasteCore\IpToCountry\IpToCountry;
use KpasteCore\Model\Coupons;

class MasterAdminController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    
    protected $permissionsTable;
    protected $usersTable;
    
    public function __construct() 
    {
        $auth           = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings       = new SessionContainer( 'settings' );
        $this->settings = $settings->settings;
    }
    
    public function indexAction()
    {
        
    }
    
    public function CreateNewAdminAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $form = new NewAdminForm();
            $request = $this->getRequest();
            $lang = $this->params()->fromRoute('lang');
            if(!$lang)  $lang = substr($this->settings['language'], 0, 2);
            if( $request->isPost() )
            {
                $form->setData( $request->getPost() );
                if( $form->isValid() )
                {
                    $data = $form->getData();
                    
                    //check for username existance
                    $existingUser = $this->getUsersTable()->fetchUser('username',$data['username']);
                    if($existingUser) {
                        return array(
                            'error'     => TRUE,
                            'result'    => 'USER_NAME_ALREADY_EXISTS',
                            'newAdminForm'      => $form,
                        );
                    }

                    //check for email existance
                    $existingUser = null;
                    $existingUser = $this->getUsersTable()->fetchUser('email',$data['email']);
                    if($existingUser) {
                        return array(
                            'error'     => TRUE,
                            'result'    => 'EMAIL_ALREADY_EXISTS',
                            'newAdminForm'=> $form,
                        );
                    }
                    
                    $admin = new User();
                    $admin->exchangeArray( $data );
                    $admin->salt = RandomSequenceGenerator::generateRandomSequence( 16 );
                    $admin->password = sha1( sha1( sha1( $data['password'] ) . 
                            $admin->salt ) . $data['username'] );
                    $admin->email_verification_code = sha1( 
                        RandomSequenceGenerator::generateRandomSequence(40) . microtime()
                    );  
                    $admin->reg_date = date( 'Y-m-d H:i:s' );
                    $admin->account_status = 'pending';
                    $admin->user_type = 'admin';
                    $this->getUsersTable()->saveUser($admin);
                    
                    $translator = $this->getServiceLocator()->get( 'translator' );
                    $verifyLink = $this->url()->fromRoute('user', array(
                        'lang'                      => $lang,
                        'action'                    => 'verifyEmail',
                        'request_confirmation_code' => $admin->email_verification_code,
                    ));
                    $emailSender = new EmailSender( $request->getServer( 'HTTP_HOST' ), $translator, $lang );
                    $emailSender->sendEmailVerificationEmail(
                            $admin->email,
                            $verifyLink, 
                            $admin->firstname
                    );
                    KEventManager::trigger('NewAdminCreated', array(
                        'adminName' => $admin->username,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    return( array( 'error' => false, 'result' => 'ADMIN_CREATED_SUCCESSFULLY' ) );
                }
                else
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_DATA_PROVIDED',
                        'newAdminForm'=> $form,
                    ) );
                }
            }
            
            return(array(
                'newAdminForm'      => $form,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function ViewAdminsAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $admins = $this->getUsersTable()->fetchAll( array( 'user_type' => 'admin' ), true );
            $admins->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $admins->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            return(array(
                'admins'            => $admins,
                'ipInfo'            => new IpToCountry( $this->getServiceLocator() ),
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function EditPermissionsAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $adminid = (int)$this->params()->fromRoute('param1');
            if( !$adminid )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $admin = $this->getUsersTable()->fetchUser('userid', $adminid);
            
            if( !$admin || $admin->user_type != 'admin' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $permissions = $this->getPermissionsTable()->fetchAllPermissions();
            
            $request = $this->getRequest();
            if( $request->isPost() )
            {
                $this->getPermissionsTable()->setAdminsPermissions( 
                        $adminid, $request->getPost( 'selected_permissions', null ) );
                KEventManager::trigger('NewPermissionsSet', array(
                    'adminid'       => $adminid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return(array(
                    'error'     => false,
                    'result'    => 'PERMISSIONS_SET',
                ));
            }
            
            $currentPermissions = $this->getPermissionsTable()->fetchAdminsPermissions($adminid);
            return(array(
                'permissions'   => $permissions,
                'adminid'       => $adminid,
                'currentPermissions'=>$currentPermissions,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function SuspendAdminAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $adminid = (int)$this->params()->fromRoute('param1');
            if( !$adminid )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $admin = $this->getUsersTable()->fetchUser('userid', $adminid);
            
            if( !$admin || $admin->user_type != 'admin' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            if( $admin->account_status != 'verified' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'USER_NOT_VERIFIED',
                ));
            }
            
            $admin->account_status = 'suspended';
            $this->getUsersTable()->saveUser( $admin );
            
            KEventManager::trigger('AdminSuspended', array(
                'adminid'       => $adminid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            
            $this->redirect()->toRoute('admin', array(
                'controller'    => 'MasterAdmin',
                'action'        => 'ViewAdmins',
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function RemoveSuspensionAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $adminid = (int)$this->params()->fromRoute( 'param1' );
            if( !$adminid )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $admin = $this->getUsersTable()->fetchUser('userid', $adminid);
            
            if( !$admin || $admin->user_type != 'admin' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            if( $admin->account_status != 'suspended' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'USER_NOT_SUSPENDED',
                ));
            }
            /*
            $request = $this->getRequest();
            if( $request->isPost() )
            {
                $confirm = $request->getPost( 'confirm', 'NO' );
                if( 'YES' == $confirm )
                {*/
            $admin->account_status = 'verified';
            $this->getUsersTable()->saveUser( $admin );
            
            KEventManager::trigger('AdminSuspensionRemoved', array(
                'adminid'       => $adminid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            
            //}
            $this->redirect()->toRoute('admin', array(
                'controller'    => 'MasterAdmin',
                'action'        => 'ViewAdmins',
            ));
            /*}
            
            return( array(
                'adminid'   => $adminid,
                'firstname' => $admin->firstname,
                'lastname'  => $admin->lastname,
            ) );*/
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function DeleteAdminAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $adminid = (int)$this->params()->fromRoute( 'param1' );
            if( !$adminid )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $admin = $this->getUsersTable()->fetchUser('userid', $adminid);
            
            if( !$admin || $admin->user_type != 'admin' )
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_ADMIN_ID',
                ));
            }
            
            $request = $this->getRequest();
            if( $request->isPost() )
            {
                $confirm = $request->getPost( 'confirm', 'NO' );
                if( 'YES' == $confirm )
                {
                    $admin->account_status = 'deleted';
                    $this->getUsersTable()->saveUser( $admin );
                    KEventManager::trigger('AdminDeleted', array(
                        'adminid'       => $adminid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                }
                $this->redirect()->toRoute('admin', array(
                    'controller'    => 'MasterAdmin',
                    'action'        => 'ViewAdmins',
                ));
            }
            
            return( array(
                'adminid'   => $adminid,
                'firstname' => $admin->firstname,
                'lastname'  => $admin->lastname,
            ) );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function couponsAction()
    {
        if( $this->authData && $this->authData->userid && $this->authData->user_type == 'masteradmin' )
        {
            $request = $this->getRequest();
            if($request->isPost())
            {
                $coupon = new Coupons();
                $coupon->exchangeArray($request->getPost());
                $this->getCouponsTable()->saveCoupon($coupon);
            }

            $coupons = $this->getCouponsTable()->fetchCoupons(false, true);
            $coupons->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $coupons->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            return(array(
                'coupons'   => $coupons,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return( array ( 
                'error'     => true,
                'result'    => 'ACCESS_DENIED',
            ) );
        }
    }
    
    public function getCouponsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\CouponsTable');
    }

    public function getCampaignsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\CampaignsTable');
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
}

?>
