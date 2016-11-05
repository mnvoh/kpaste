<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    UserManagementController.php
 * @createdat    Jul 27, 2013 12:20:53 PM
 */

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;
use Zend\Db\Sql\Where;
use KpasteCore\IpToCountry\IpToCountry;
use KpasteCore\RandomSequenceGenerator;

class UserManagementController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    protected $acl;
    
    public function __construct() 
    {
        $auth           = new AuthenticationService();
        $this->authData = @unserialize( $auth->getStorage()->read() );
        $settings       = new SessionContainer( 'settings' );
        $this->settings = $settings->settings;
        $aclArray       = new SessionContainer( 'acl' );
        $this->acl      = $aclArray->acl;
    }
    
    private function isAllowed($permission)
    {
        if( !$this->acl || !is_array( $this->acl ) )
            return false;
        if( ($key = array_search( $permission, $this->acl)) === FALSE )
        {
            return false;
        }
        return true;
    }
    
    public function indexAction() 
    {

    }
    
    public function ViewUsersAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed( 'VIEW_USERS' ) )
        {
            $userid     = $this->params()->fromRoute( 'param1' );
            
            if( $userid != null )
            {
                $user       = $this->getUsersTable()->fetchUser( 'userid', $userid );
                if( $user->user_type != 'paster' && $user->user_type != 'advertiser' )
                    $user = null;
                if( !$user )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_USER_ID',
                    ) );
                }
                
                $ipInfo = new IpToCountry( $this->getServiceLocator() );
                
                return( array(
                    'user'      => $user,
                    'ipInfo'    => $ipInfo,
                ));
            }
            
            $where = new Where();
            $where->notEqualTo('user_type', 'admin');
            $where->notEqualTo('user_type', 'masteradmin');
            $users = $this->getUsersTable()->fetchAll( $where, true );
            $users->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $users->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            return( array( 
                'users'     => $users,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ChangePasswordAction() 
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed( 'CHANGE_USERS_PASSWORDS' ) )
        {
            $request = $this->getRequest();
            
            if( $request->isPost() )
            {
                $userid = $request->getPost('userid');
                $user = $this->getUsersTable()->fetchUser( 'userid', $userid );
                if( !$user )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_USER_ID', 
                    ) ); 
                }
                
                $password = $request->getPost( 'password' );
                
                if( $password != $request->getPost( 'repassword' ) )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'PASSWORDS_DO_NOT_MATCH',
                        'userid'    => $userid,
                    ) ); 
                }
                
                if( strlen( $password ) < 7 || strlen( $password ) > 255 )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'WRONG_PASSWORD_LENGTH',
                        'userid'    => $userid,
                    ) ); 
                }
                
                $user->salt = RandomSequenceGenerator::generateRandomSequence(16);
                $user->password = sha1( sha1( sha1( $request->getPost( 'password' ) ) 
                        . $user->salt ) . $user->username );
                $this->getUsersTable()->saveUser( $user );
                KEventManager::trigger('AdminChangedUserPassword', array(
                    'adminid'       => $this->authData->userid,
                    'userid'        => $userid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return( array( 
                    'error'     => false,
                    'userid'    => $userid,
                ));
            }
            return( array(
                        'error'     => true,
                        'result'    => 'INVALID_USER_ID', 
                    ) ); 
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ChangeStatusAction()
    {
        if( $this->authData && $this->authData->userid )
        {
            $request = $this->getRequest();
            if( $request->isPost() )
            {
                $userid = $request->getPost('userid');
                $user = $this->getUsersTable()->fetchUser( 'userid', $userid );
                if( !$user )
                {
                    return( array(
                        'error'     => true,
                        'result'    => 'INVALID_USER_ID', 
                    ) ); 
                }
                switch( $request->getPost('status') )
                {
                    case 'suspended':
                        if( !$this->isAllowed( 'SUSPEND_USERS' ) )
                        {
                            return(array(
                                'error'     => true,
                                'result'    => 'ACCESS_DENIED', 
                                'userid'    => $userid,
                            )); 
                        }
                        $user->account_status = 'suspended';
                        $this->getUsersTable()->saveUser( $user );
                        
                        $userid = $user->userid;
                        $at = date( 'Y-m-d H:i:s' );
                        $reason = $request->getPost('reason');
                        $this->getSuspendedUsersTable()->save( $userid, $at, $reason );
                        KEventManager::trigger('UserSuspended', array(
                            'adminid'   => $this->authData->userid,
                            'userid'    => $userid,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                        break;
                    case 'verified':
                        if( !$this->isAllowed( 'REMOVE_USER_SUSPENSION' ) )
                        {
                            return(array(
                                'error'     => true,
                                'result'    => 'ACCESS_DENIED', 
                                'userid'    => $userid,
                            )); 
                        }
                        $user->account_status = 'verified';
                        $this->getUsersTable()->saveUser( $user );
                        
                        $this->getSuspendedUsersTable()->delete( $user->userid );
                        
                        KEventManager::trigger('UserSuspensionRemoved', array(
                            'adminid'   => $this->authData->userid,
                            'userid'    => $user->userid,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                        
                        break;
                }
                
            }
            return $this->redirect()->toRoute( 'admin', array(
                'controller'    => 'UserManagement',
                'action'        => 'ViewUsers',
                'param1'        => $userid
            ) );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
                'userid'    => $userid,
            )); 
        }
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
    
    public function getSuspendedUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\SuspendedUsersTable');
    }
}

?>
