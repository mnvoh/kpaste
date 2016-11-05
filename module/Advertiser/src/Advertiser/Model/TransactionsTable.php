<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    TransactionsTable.php
 * @createdat    Jul 11, 2013 2:27:14 PM
 */

namespace Advertiser\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;


class TransactionsTable 
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
    
    public function fetchTransaction($where) 
    {
        return $this->tableGateway->select($where)->current();        
    }
    
    public function fetchTransactions($where, $paginated = false, $order = false) 
    {
        if( $paginated )
        {
            $select             = new Select( 'transactions' );
            $select             ->where( $where );
            if($order)
                $select->order ($order);
            $resultSet          = new ResultSet();
            $resultSet          ->setArrayObjectPrototype( new Transactions() );
            $paginatorAdapter   = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            $paginator          = new Paginator( $paginatorAdapter );
            return $paginator;
        }
        return $this->tableGateway->select($where);        
    }
    
    public function saveTransaction(Transactions $transaction) 
    {
        $data = array(
            'userid'                => $transaction->userid,
            'amount'                => $transaction->amount,
            'status'                => $transaction->status,
            'au'                    => $transaction->au,
            'receipt'               => $transaction->receipt,
            'requested_datetime'    => $transaction->requested_datetime,
            'completed_datetime'    => $transaction->completed_datetime,
        );
        
        $transactionid = (int)$transaction->transactionid;
        if(!$transactionid) {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else {
            
            if($this->fetchTransaction(array('transactionid', $transactionid))) {
                $this->tableGateway->update($data, array('transactionid' => $transactionid));
                return $transactionid;
            }
            else {
                throw new \Exception('Transaction not found!');
            }
        }
    }
    
    public function getNextTid()
    {
        $select = new Select('transactions');
        $select->limit(1);
        $select->order('transactionid DESC');
        $result = $this->tableGateway->selectWith($select);
        $result = $result->current();
        return((int)$result->transactionid + 1);
    }
    
    public function deleteTransactions($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
