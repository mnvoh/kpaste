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
use Zend\Db\Sql\Where;
use KpasteCore\KChart\KPoint;

class PasteViewsTable {
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway) 
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchAll() 
    {
        return $this->tableGateway->select();
    }
    
    public function fetchPasteChartData($pasteid)
    {
        $pasteid = (int)$pasteid;
        $select = new Select('paste_views');
        $select->columns(array(
            'pasteid'            => 'pasteid',
            'date_viewed'        => new Expression('DATE(view_datetime)'),
            'view_count'         => new Expression('COUNT(*)'),
            ));
        $select->group('date_viewed');
        $select->where(array('pasteid' => $pasteid));
        $sql = new Sql($this->tableGateway->getAdapter());
        $query = $sql->getSqlStringForSqlObject($select);

        $data = $this->tableGateway->getAdapter()->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        $points = array();
        
        if($data->count() < 33)
        {
            foreach($data as $datum)
            {
                $date = \KpasteCore\KDateTime\KDateTime::PrefDate($datum['date_viewed'], 'm-d');
                $points[] = new KPoint($date, $datum['view_count']);
            }
        }
        else
        {
            $months = array();
            foreach($data as $datum)
            {
                $date = \KpasteCore\KDateTime\KDateTime::PrefDate($datum['date_viewed'], 'Y-m');
                if(isset($months[$date]))
                    $months[$date] += $datum['view_count'];
                else
                    $months[$date] = $datum['view_count'];
            }
            foreach($months as $key => $val)
            {
                $points[] = new KPoint($key, $val);
            }
        }
        
        return $points;
    }
    
    public function isPasteElligibleForViewAddition($pasteid, $ip)
    {
        $where = new Where();
        $where->equalTo('pasteid', $pasteid);
        $where->equalTo('viewer_ip', $ip);
        $where->greaterThan('view_datetime', date('Y-m-d H:i:s', strtotime('-1 days')));
        $result = $this->tableGateway->select($where);
        if($result && $result->count() > 0)
            return false;
        return true;
    }
    
    public function fetchPasteViews($where) 
    {
        return $this->tableGateway->select($where);        
    }
    
    public function savePasteView(PasteViews $pasteView)
    {
        $data = array(
            'pasteid'           => $pasteView->pasteid,
            'view_datetime'     => $pasteView->view_datetime,
            'viewer_ip'         => $pasteView->viewer_ip,
            'user_agent'        => $pasteView->user_agent,
        );
        
        $pasteviewid = (int)$pasteView->pasteviewid;
        if(!$pasteviewid) {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else {
            if($this->fetchPasteViews(array('pasteviewid', $pasteviewid))) {
                $this->tableGateway->update($data, array('pasteviewid' => $pasteviewid));
                return $pasteviewid;
            }
            else {
                throw new \Exception('Paste View not found!');
            }
        }
    }
    
    public function deletePasteViews($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
