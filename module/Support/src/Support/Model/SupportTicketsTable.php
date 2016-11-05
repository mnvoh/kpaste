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
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class SupportTicketsTable {
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchTickets( $where = null, $paginated = false ) 
    {
        $select = new Select( 'support_tickets' );
        $select->where( $where );
        $select->join( 
            'users',
            'users.userid = support_tickets.userid',
            array( 'username', 'firstname', 'lastname')
        );
        $select->join( 'departments', 'support_tickets.departmentid = departments.departmentid', 
                array( 'department' ) );
        
        if( $paginated )
        {
            $resultSet = new ResultSet();
            $resultSet->setArrayObjectPrototype( new SupportTickets() );
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
    
    public function saveTicket(SupportTickets $st) 
    {
        $data = array(
            'userid'        => $st->userid,
            'title'         => $st->title,
            'opened_at'     => $st->opened_at,
            'status'        => $st->status,
            'importance'    => $st->importance,
            'departmentid'  => $st->departmentid,

        );
        
        $stid = (int)$st->ticketid;
        
        if(!$stid) 
        {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        } 
        else 
        {
            if($this->fetchTickets(array('ticketid', $stid))) 
            {
                $this->tableGateway->update($data, array('ticketid' => $stid));
                return $stid;
            } 
            else 
            {
                throw new \Exception('Invalid ticket id!');
            }
        }
    }
    
    public function getUnreadMessagesCount($userid, $admin = false, $returnResults = false)
    {
        //if admin, return the number of open tickets that are awaiting admins response
        if($admin)
        {
            $select = new Select('support_tickets');
            $where = new Where();
            $where->equalTo('support_tickets.status', 'pending_admin');
            $select->where($where);
            $results = $this->tableGateway->selectWith($select);
            return $results->count();
        }
        
        //if not admin, return the number of unread messages this user has
        $userid = (int)$userid;
        $select = new Select('support_tickets');
        $select->join('ticket_messages', 'support_tickets.ticketid=ticket_messages.ticketid');
        $where = new Where();
        $where->equalTo('support_tickets.userid', $userid);
        $where->equalTo('ticket_messages.status', 'unread');
        $where->notEqualTo('ticket_messages.userid', $userid);
        $select->where($where);
        $results = $this->tableGateway->selectWith($select);
        
        if( $returnResults )    return $results;
        
        return $results->count();
    }
    
    public function deleteTicket($where)
    {
        $this->tableGateway->delete($where);
    }
}