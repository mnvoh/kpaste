<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    PastesTable.php
 * @createdat    Jul 11, 2013 2:27:14 PM
 */

namespace Paster\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\ResultSet\ResultSet;

class PastesTable {
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) 
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchAll() 
    {
        return $this->tableGateway->select();
    }
    
    public function fetchPaste($where) 
    {
        return $this->tableGateway->select($where)->current();        
    }
    
    public function fetchPastes($where, $paginated=false, $limit = false)
    {
        $select = new Select( 'pastes' );
        $select->where( $where );
        $select->join('paste_views', 'pastes.pasteid = paste_views.pasteid', 
            array('pasteviewid', 'viewed' => new Expression('COUNT(pasteviewid)')), Select::JOIN_LEFT);
        $select->join('users', 'pastes.userid=users.userid', array('username' => 'username'), Select::JOIN_LEFT);
        $select->order('pasteid DESC');
        $select->group(['pastes.pasteid']);
        if( $limit ) $select->limit ($limit);
        if( $paginated )
        {
            $resultSet = new ResultSet();
            $resultSet->setArrayObjectPrototype( new Pastes() );
            $paginatorAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            return new Paginator( $paginatorAdapter );
        }

        return $this->tableGateway->selectWith($select);
    }
    
    public function fetchPastersPastes($userid, $paginated = false)
    {
        $userid = (int)$userid;
        $sql = new Sql($this->tableGateway->getAdapter());
        $select = new Select();
        $select->from('pastes');
        $select->columns(array('pasteid', 'userid', 'title', 'password_test', 'exposure','syntax', 
            'status', 'pasted_on'));
        $select->join('paste_views', 'pastes.pasteid = paste_views.pasteid', 
                array('pasteviewid', 'viewed' => new Expression('COUNT(pasteviewid)')), Select::JOIN_LEFT);
        $select->group('pastes.pasteid');
        $select->where( array( 'userid' => $userid ) );
        
        if( $paginated )
        {
            $resultSet = new ResultSet();
            $resultSet->setArrayObjectPrototype( new Pastes() );
            $paginatorAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            return new Paginator( $paginatorAdapter );
        }
        
        $query = $sql->getSqlStringForSqlObject($select);
        $db = new Adapter($this->tableGateway->adapter->getDriver());
        return $db->query($query, Adapter::QUERY_MODE_EXECUTE);
    }
    
    public function savePaste(Pastes $paste) 
    {
        $data = array(
            'userid'        => $paste->userid,
            'title'         => $paste->title,
            'paste'         => $paste->paste,
            'password_test' => $paste->password_test,
            'exposure'      => $paste->exposure,
            'syntax'        => $paste->syntax,
            'status'        => $paste->status,
            'pasted_on'     => $paste->pasted_on,
        );
        
        $pasteid = (int)$paste->pasteid;
        if(!$pasteid) {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else {
            
            if($this->fetchPaste(array('pasteid', $pasteid))) {
                $this->tableGateway->update($data, array('pasteid' => $pasteid));
                return $pasteid;
            }
            else {
                throw new \Exception('Paste not found!');
            }
        }
    }
    
    public function deletePaste($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
