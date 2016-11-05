<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    PermissionsTable.php
 * @createdat    Jul 29, 2013 4:16:10 PM
 */

namespace KpasteCore\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class PermissionsTable 
{
    public      $tableGateway;
    private     $permissions;
    private     $adapter;
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }
    
    public function fetchAdminsPermissions( $userid )
    {
        $sql            = new Sql( $this->adapter );
        $select         = $sql->select();
        $select         ->from( 'users_has_permissions' );
        $select         ->where( array( 'users_userid' => $userid ) );
        $select         ->join( 'permissions', 'users_has_permissions.permissions_permissionid' 
                                                                    . ' = permissions.permissionid' );
        $query          = $sql->getSqlStringForSqlObject( $select );
        $results        = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        $adminPermissions = array();
        foreach($results as $result)
        {
            $adminPermissions[] = $result['permission_name'];
        }
        return( $adminPermissions );
    }
    
    public function setAdminsPermissions( $userid, $permissions = null )
    {
        $userid         = ( int )$userid;
        $sql            = new Sql( $this->adapter );
        $select         = $sql->select( 'users' );
        $select         ->where( array( 'userid' => $userid ) );
        $query          = $sql->getSqlStringForSqlObject( $select );
        $user           = $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE )->current();
        if( !$user || ($user->user_type != 'admin' && $user->user_type != 'masteradmin') )
        {
            throw new \Exception( 'Invliad user ID provided!' );
        }
        
        //delete all existing permissions associated with this user
        $delete         = $sql->delete( 'users_has_permissions' );
        $delete         ->where( array( 'users_userid' => $userid ) );
        $query          = $sql->getSqlStringForSqlObject( $delete );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
        
        //strip the user of all permissions
        if( !$permissions )
            return;
        
        $allPermissions = $this->fetchAllPermissions();
        //grant all permissions
        if( $permissions == 'ALL' )
        {
            foreach( $allPermissions as $key => $value )
            {
                $insert         = $sql->insert( 'users_has_permissions' );
                $insert         ->columns( array( 'users_userid', 'permissions_permissionid' ) );
                $insert         ->values( array(
                    'users_userid'              => $userid,
                    'permissions_permissionid'  => $key,
                ) );

                $query          = $sql->getSqlStringForSqlObject( $insert );
                $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
            } 
            return;
        }
        
        //now add the new permissions
        foreach( $permissions as $permission )
        {
            $index      = array_search($permission, $allPermissions);
            if($index)
            {
                $insert         = $sql->insert( 'users_has_permissions' );
                $insert         ->columns( array( 'users_userid', 'permissions_permissionid' ) );
                $insert         ->values( array(
                    'users_userid'              => $userid,
                    'permissions_permissionid'  => $index,
                ) );

                $query          = $sql->getSqlStringForSqlObject( $insert );
                $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
            }
        }        
    }
    
    public function fetchAllPermissions()
    {
        if(!$this->permissions)
        {
            $sql                = new Sql( $this->adapter );
            $select             = $sql->select( 'permissions' );
            $select             ->order( 'permissionid' );
            $query              = $sql->getSqlStringForSqlObject( $select );
            $results            = $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
            $this->permissions  = array();
            foreach( $results as $result )
            {
                $i = ( int )$result['permissionid'];
                $this->permissions[$i] = $result['permission_name'];
            }
        }
        return $this->permissions;
    }
}

?>
