<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */
namespace User\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class SuspendedUsersTable 
{
    public $adapter;
    
    public function __construct(Adapter $a) 
    {
        $this->adapter = $a;
    }
    
    public function fetch($where) 
    {
        $sql = new Sql( $this->adapter );
        $select = $sql->select( 'suspended_users' );
        $select->where( $where );
        $query = $sql->getSqlStringForSqlObject( $select );
        $result = $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
        return $result->current();
    }
    
    public function save( $userid, $datetime, $reason ) 
    {
        $newData = array(
            'userid'                => $userid,
            'suspended_at'          => $datetime,
            'reason'                => $reason,
        );
        
        $exists = $this->fetch( array( 'userid' => $userid ) );
        if(!$exists) 
        {
            $sql = new Sql( $this->adapter );
            $insert = $sql->insert( 'suspended_users' );
            $insert->columns( array( 'userid', 'suspended_at', 'reason' ) );
            $insert->values( $newData );
            $query = $sql->getSqlStringForSqlObject( $insert );
            $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
        }        
    }
    
    public function delete( $userid )
    {
        $sql = new Sql( $this->adapter );
        $delete = $sql->delete( 'suspended_users' );
        $delete->where( array( 'userid' => $userid ) );
        $query = $sql->getSqlStringForSqlObject( $delete );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
}