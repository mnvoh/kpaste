<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    AdsViewsTable.php
 * @createdat   Jul 11, 2013 2:27:14 PM
 */

namespace Advertiser\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

class AdsViewsTable 
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
    
    public function fetchAdsViews($where) 
    {
        return $this->tableGateway->select($where);        
    }
    
    public function saveAdsViews(AdsViews $adsViews) 
    {
        $data = array(
            'campaignid'        => $adsViews->campaignid,
            'viewed_at'         => $adsViews->viewed_at,
            'viewer_ip'         => $adsViews->viewer_ip,
            'user_agent'        => $adsViews->user_agent,
        );
        
        $adviewid = (int)$adsViews->adviewid;
        if(!$adviewid) 
        {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else 
        {
            
            if($this->fetchAdsViews(array('adviewid', $adviewid))) {
                $this->tableGateway->update($data, array('adviewid' => $adviewid));
                return $adviewid;
            }
            else {
                throw new \Exception('Paste not found!');
            }
        }
    }
    
    public function deleteAdview($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
