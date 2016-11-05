<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    CheckoutsTable.php
 * @createdat    Jul 11, 2013 2:27:14 PM
 */

namespace Paster\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;

class CheckoutsTable 
{
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) 
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchAll() 
    {
        return $this->tableGateway->select();
    }
    
    public function fetchCheckout($where) 
    {
        return $this->tableGateway->select($where)->current();        
    }
    
    public function fetchCheckouts($where, $paginated = false) 
    {
        if( $paginated )
        {
            $select = new Select( 'checkouts' );
            $select->where( $where );
            $resultSet = new ResultSet();
            $resultSet->setArrayObjectPrototype( new Checkouts() );
            $paginatorAdapter = new DbSelect(
                        $select,
                        $this->tableGateway->getAdapter(),
                        $resultSet
                    );
            return new Paginator( $paginatorAdapter );
        }
        return $this->tableGateway->select($where);        
    }
    
    public function fetchEarnings($userid)
    {
        $sql = new Sql($this->tableGateway->adapter);
        $select = $sql->select();
        $select->from('checkouts');
        $select->columns(array(
            'totalEarnings' => new Expression('SUM(amount)'),
        ));
        $select->where(array('userid' => $userid, 'status' => 'paid'));
        
        $query = $sql->getSqlStringForSqlObject($select);
        $dbAdapter = new Adapter($this->tableGateway->adapter->getDriver());
        
        return $dbAdapter->query($query, Adapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function saveCheckout(Checkouts $checkout) 
    {
        $data = array(
            'userid'                => $checkout->userid,
            'datetime_requested'    => $checkout->datetime_requested,
            'amount'                => $checkout->amount,
            'status'                => $checkout->status,
            'transaction_tracking_code'=> $checkout->transaction_tracking_code,
            'transaction_datetime'  => $checkout->transaction_datetime,
            'description'           => $checkout->description,
        );
        
        $checkoutid = (int)$checkout->checkoutid;
        if(!$checkoutid) {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else {
            
            if($this->fetchCheckout(array('checkoutid', $checkoutid))) {
                $this->tableGateway->update($data, array('checkoutid' => $checkoutid));
                return $checkoutid;
            }
            else {
                throw new \Exception('Checkout not found!');
            }
        }
    }
    
    public function deleteCheckouts($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
