<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */
namespace User\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class UserTable {
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchAll($where = null, $paginated = false, $order = null, $paster = false)
    {
        if( $paginated )
        {
            $select             = new Select( 'users' );
            $select             ->where( $where );
            if($order)          $select->order($order);
            $resultSet          = new ResultSet();
            $resultSet          ->setArrayObjectPrototype( new User() );
            $paginatorAdapter   = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            $paginator          = new Paginator( $paginatorAdapter );
            return $paginator;
        }
        if($paster)
        {
            $select             = new Select( 'users' );
            $select             ->where( $where );
            if($order)          $select->order($order);
            return $this->tableGateway->selectWith($select);
        }
        return $this->tableGateway->select();
    }
    
    /*
     * fetchUser: fetch a user by the reference column parameter.
     * 
     * @param $referenceColumn: the column to put in the where clause
     * @param $value: the value of the referenceColumn to put in the where
     */
    public function fetchUser($referenceColumn, $value) {
        $rowset = $this->tableGateway->select(array($referenceColumn => $value));
        $row = $rowset->current();
        
        if(!$row) {
            return NULL;
        }

        $select = $this->tableGateway->getSql()->select()
                ->where(array("users.$referenceColumn" => $value));
        $resultSet = $this->tableGateway->selectWith($select);

        return $resultSet->current();
    }
    
    public function fetchUserEx($where, $order = false) 
    {
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        
        if(!$row) {
            return NULL;
        }

        $select = $this->tableGateway->getSql()->select()
                ->where(array("users.userid" => $row->userid));
        if($order) $select->order ($order);
        $resultSet = $this->tableGateway->selectWith($select);

        return $resultSet->current();
    }
    
    public function saveUser(User $user) {
        $data = array(
            'username'                  => $user->username,
            'email'                     => $user->email,
            'password'                  => $user->password,
            'salt'                      => $user->salt,
            'email_verification_code'   => $user->email_verification_code,
            'reg_date'                  => $user->reg_date,
            'account_status'            => $user->account_status,
            'user_type'                 => $user->user_type,
            'last_login_time'           => $user->last_login_time,
            'last_ip_addr'              => $user->last_ip_addr,
            'total_pastes'              => $user->total_pastes,
            'total_views'               => $user->total_pastes,
        );
        
        $userid = $user->userid;
        
        if(!$userid) 
        {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        } 
        else 
        {
            if($this->fetchUser('userid', $userid)) 
            {
                $this->tableGateway->update($data, array('userid' => $userid));
            } 
            else 
            {
                throw new \Exception('Invalid user id!');
            }
        }
    }
}