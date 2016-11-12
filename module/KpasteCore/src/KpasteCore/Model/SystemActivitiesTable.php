<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    SystemActivitiesTable.php
 * @createdat    Jul 29, 2013 4:16:10 PM
 */

namespace KpasteCore\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;

class SystemActivitiesTable 
{
    private     $adapter;
    
    public function __construct( Adapter $adapter )
    {
        $this->adapter = $adapter;
    }
    
    public function fetchActivities( $where = null, $filter = null )
    {
        $select             = new Select( 'system_activities' );
        if( !( $where instanceof Where ) ) $where = new Where( $where );
        if( $filter )       $where->like ( 'activity', $filter );
        $select             ->where( $where );
        $select->order('activityid DESC');
        $resultSet          = new ResultSet();
        $resultSet          ->setArrayObjectPrototype( new SystemActivities() );
        $paginatorAdapter   = new DbSelect(
            $select,
            $this->adapter,
            $resultSet
        );
        $paginator          = new Paginator( $paginatorAdapter );
        return $paginator;
    }
    
    public function insertActivity( $activity, $userid, $userip, $datetime )
    {
        $sql        = new Sql( $this->adapter );
        $insert     = $sql->insert( 'system_activities' );
        $insert     ->values(array(
            NULL,
            $activity,
            $userid,
            $userip,
            $datetime,
        ));
        
        $query = $sql->getSqlStringForSqlObject( $insert );
        return $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
    
    public function deleteOldActivities($age)
    {
        $date = date('Y-m-d H:i:s', strtotime("-$age days"));
        $sql = new Sql( $this->adapter );
        $delete = $sql->delete('system_activities');
        $where = new Where();
        $where->lessThan('activity_datetime', $date);
        $delete->where($where);
        $query = $sql->getSqlStringForSqlObject($delete);
        $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
    }
}

?>
