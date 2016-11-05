<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */
namespace Support\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class DepartmentsTable {
    public $adapter;
    
    public function __construct(Adapter $adapter) 
    {
        $this->adapter = $adapter;
    }
    
    public function fetchDepartments() 
    {
        $sql = new Sql( $this->adapter );
        $select = $sql->select( 'departments' );
        $query = $sql->getSqlStringForSqlObject( $select );
        $departments = $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
        $dep_a = array();
        foreach( $departments as $department )
        {
            $dep_a[$department['departmentid']] = $department['department'];
        }
        return $dep_a;
    }
    
    public function saveDepartment($departments) 
    {
        $sql = new Sql( $this->adapter );
        $insert = $sql->insert( 'departments' );
        $insert->columns(array('departmentid', 'department'));
        if(is_array($departments))
        {
            foreach( $departments as $department )
            {
                $insert->values(array($department));
                $query = $sql->getSqlStringForSqlObject($insert);
                $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
            }
            return;
        }
        $insert->values(array($departments));
        $query = $sql->getSqlStringForSqlObject($insert);
        $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
    }
    
    public function deleteDepartments($where = null)
    {
        $this->tableGateway->delete($where);
    }
}