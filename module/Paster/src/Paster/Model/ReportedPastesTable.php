<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    ReportedPastesTable.php
 * @createdat    Aug 3, 2013 2:27:14 PM
 */

namespace Paster\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;

class ReportedPastesTable 
{
    public $adapter;
    
    public function __construct(Adapter $adapter) 
    {
        $this->adapter = $adapter;
    }
    
    public function fetchReportedPastes($where = null, $paginated = false)  
    {
        $select         = new Select( 'reported_pastes' );
        $select         ->columns(array('pasteid', 'reports_count' => new Expression('COUNT("pasteid")')));
        $where          = ($where instanceof Where) ? $where : new Where($where);
        $where          ->equalTo('pastes.status', 'active');
        $select         ->where( $where );
        $select         ->join( 'pastes', 'reported_pastes.pasteid = pastes.pasteid', array(
            'userid', 'title', 'password_test', 'exposure', 
            'syntax', 'status', 'pasted_on'
        ), Select::JOIN_LEFT );
        $select         ->group( 'reported_pastes.pasteid' );
        $select         ->order( 'reports_count DESC' );
        if( $paginated )
        {
            $paginaterAdapter = new DbSelect(
                $select,
                $this->adapter,
                new ResultSet()
            );
            $sql = new Sql( $this->adapter );
            return new Paginator( $paginaterAdapter );
        }
        $sql = new Sql( $this->adapter );
        $query = $sql->getSqlStringForSqlObject( $select );
        return $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
    
    public function insertReport($pasteid, $ip)
    {
        $pasteid = (int)$pasteid;
        $sql = new Sql( $this->adapter );
        $insert = $sql->insert( 'reported_pastes' );
        $insert->columns( array( 'pasteid', 'reporterip' ) );
        $insert->values( array( $pasteid, $ip ) );
        $query = $sql->getSqlStringForSqlObject( $insert );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
    
    public function deleteReports($where)
    {
        $sql = new Sql( $this->adapter );
        $delete = $sql->delete( 'reported_pastes' );
        $delete->where( $where );
        $query = $sql->getSqlStringForSqlObject( $delete );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
}

?>
