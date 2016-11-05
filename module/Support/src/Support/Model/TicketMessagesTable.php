<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */
namespace Support\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class TicketMessagesTable 
{
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) 
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchTicketMessages( $where = null, $paginated = false ) 
    {
        $select = new Select( 'ticket_messages' );
        $select->where( $where );
        $select->join( 
            'users',
            'users.userid = ticket_messages.userid',
            array( 'firstname', 'lastname', 'user_type')
        );
        
        if( $paginated )
        {
            $resultSet = new ResultSet();
            $resultSet->setArrayObjectPrototype( new TicketMessages() );
            $paginatorAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            $paginator = new Paginator( $paginatorAdapter );
            return $paginator;
        }
        return $this->tableGateway->selectWith( $select );
    }
    
    public function saveTicketMessage(TicketMessages $st) {
        $data = array(
            'ticketid'  => $st->ticketid,
            'userid'    => $st->userid,
            'message'   => $st->message,
            'status'    => $st->status,
            'msgdate'   => $st->msgdate,
            'attachment'=> $st->attachment,
            'attachment_name'=>$st->attachment_name,
        );
        
        $stid = (int)$st->tmsgid;
        
        if( !$stid ) 
        {
            $this->tableGateway->insert($data);
        } 
        else 
        {
            if($this->fetchTickets(array('tmsgid', $stid))) 
            {
                $this->tableGateway->update($data, array('tmsgid' => $stid));
            } 
            else 
            {
                throw new \Exception('Invalid ticket message id!');
            }
        }
        return $this->tableGateway->lastInsertValue;
    }
    
    public function setAsRead($ticketid, $userid)
    {
        $sql = new Sql($this->tableGateway->getAdapter());
        $update = new Update('ticket_messages');
        $where = new Where();
        $where->equalTo('ticketid', $ticketid);
        $where->notEqualTo('userid', $userid);
        $update->where($where);
        $update->set(array('status' => 'read'));
        
        $query = $sql->getSqlStringForSqlObject($update);
        $this->tableGateway->getAdapter()->query($query, Adapter::QUERY_MODE_EXECUTE);
    }
    
    public function deleteTicket($where)
    {
        $this->tableGateway->delete($where);
    }
}