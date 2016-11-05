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

class PasswordChangeRequestsTable {
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchAll() {
        return $this->tableGateway->select();
    }
    
    public function fetchRequest($where) {
        $rowset = $this->tableGateway->select($where);
        $row = $rowset->current();
        return $row;
    }
    
    public function saveRequest(PasswordChangeRequests $data) {
        $newData = array(
            'userid'                => $data->userid,
            'request_time'          => $data->request_time,
            'request_confirmation_code'=> $data->request_confirmation_code,
            'status'                => $data->status,
        );
        
        $pcrid = $data->pcrid;
        
        if(!$pcrid) {
            $this->tableGateway->insert($newData);
        }
        else {
            if($this->fetchRequest(array('pcrid' => $pcrid))) {
                $this->tableGateway->update($newData, array('pcrid' => $pcrid));
            }
            else {
                throw new \Exception('Invalid password change request id.');
            }
        }
    }
}