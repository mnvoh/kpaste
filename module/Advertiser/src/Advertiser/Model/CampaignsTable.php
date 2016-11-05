<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    CampaignsTable.php
 * @createdat   Jul 11, 2013 2:27:14 PM
 */

namespace Advertiser\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use KpasteCore\IpToCountry\IpToCountry;
use Zend\Session\Container as SessionContainer;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class CampaignsTable
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
    
    public function fetchCampaign($where) 
    {
        return $this->tableGateway->select($where)->current();        
    }
    
    public function fetchCampaigns($where, $paginated = false)
    {
        if( $paginated )
        {
            $select             = new Select( 'campaigns' );
            $select             ->where( $where );
            $resultSet          = new ResultSet();
            $resultSet          ->setArrayObjectPrototype( new Campaigns() );
            $paginatorAdapter   = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultSet
            );
            $paginator          = new Paginator( $paginatorAdapter );
            return $paginator;
        }
        return $this->tableGateway->select( $where );
    }

    /**
     * 
     * @param type $adType      The type of the campaign to fetch
     * @param type $viewerIp    The ip of the viewer
     * @return type DbRow from campaigns_priority view
     * 
     * First we retrieve all active campaigns in order of priority. The priority 
     * ensures that all active ads will be shown in turn in a cycle. The view
     * Also ensures that the campaign is active, has remaining credits and that its daily credit
     * has not reached its limit.
     * 
     * Then we check if the ad is global. If not, we have to check the viewer ip to see if she's
     * local or not.
     * 
     * Now we need to check each nominated campaign to insure that it hasn't been viewed by this
     * IP address in the paste 24 hours.
     * 
     * After the nominated campaign has been chosen from the available ads, three things need to 
     * happen. First, an ads_views row need to be inserted. Second, the remaining_credits column
     * value needs to be decremented. And third, if the remaining_credits is zero, then its status
     * needs to be set to finished, and its finished_date be set to now.
     */
    public function fetchNextAd($adType, $viewerIp, $userAgent, $serviceLocator)
    {
        $sessionContainer = new SessionContainer('settings');
        $settings = $sessionContainer->settings;
        
        $select = new Select('campaigns');
        $select->join('ads_views', 'campaigns.campaignid = ads_views.campaignid',
                array('last_view' => new Expression("MAX(ads_views.viewed_at)")), Select::JOIN_LEFT);
        
        $where = new Where();
        $where->greaterThan('campaigns.remaining_credits', 0);
        $where->equalTo('campaigns.status', 'active');
        $where->equalTo('campaign_type', $adType);
        $where->greaterThan('campaigns.daily_credits', 
                new Expression('(SELECT COUNT(*) `todays_visits` FROM `ads_views`' . 
                                'WHERE `campaigns`.`campaignid` = `ads_views`.`campaignid`' .
                                'AND DATE(`ads_views`.`viewed_at`) = CURDATE())'));
        
        $select->where($where);
        $select->group('campaignid');
        $select->order('last_view');
        
        $sql = new Sql($this->tableGateway->adapter);
        $query = $sql->getSqlStringForSqlObject($select);
        $priorityAds = $this->tableGateway->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        /*
        $sql = new Sql($this->tableGateway->adapter);
        $select = $sql->select('campaigns_priority');
        $select->where(array('campaign_type' => $adType));
        $query = $sql->getSqlStringForSqlObject($select);
        $adapter = new Adapter($this->tableGateway->adapter->getDriver());
        
        $priorityAds = $adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
         * 
         */
        //now we have all available campaigns. Time to check if they've been viewed by this ip before 
        
        $chosenAd = null;
        
        foreach($priorityAds as $pa)
        {
            if($pa->campaign_scope == 'local')
            {
                $ipInfo = new IpToCountry($serviceLocator, $viewerIp);
                if($ipInfo->getShortName() != $settings['locale'])
                    continue;
            }
            
            $ipselect = $sql->select('ads_views');
            $ipselectwhere = new Where();
            $ipselectwhere->equalTo('viewer_ip', $viewerIp);
            $ipselectwhere->equalTo('campaignid', $pa->campaignid);
            $ipselectwhere->expression('DATE(`viewed_at`) = CURDATE()', null);
            $ipselect->where($ipselectwhere);
            $ipquery = $sql->getSqlStringForSqlObject($ipselect);
            $ip = $this->tableGateway->adapter->query($ipquery, Adapter::QUERY_MODE_EXECUTE);
            if(!$ip || !$ip->count())
            {
                $chosenAd = $pa;
                break;                
            }
        }
        
        if($chosenAd)
        {
            //first insert a row in ads_views
            $adviewInsert = $sql->insert('ads_views');
            $adviewInsert->columns(array('adviewid', 'campaignid', 'viewed_at', 'viewer_ip', 'user_agent'));
            $adviewInsert->values(array(NULL, $chosenAd->campaignid, date('Y-m-d H:i:s'), $viewerIp, $userAgent));
            $adviewInsertQuery = $sql->getSqlStringForSqlObject($adviewInsert);

            $this->tableGateway->adapter->query($adviewInsertQuery, Adapter::QUERY_MODE_EXECUTE);
            
            $campaignInsert = new Campaigns();
            $campaignInsert->exchangeArray($chosenAd);
            $campaignInsert->remaining_credits--;
            if(!$campaignInsert->remaining_credits)
            {
                $campaignInsert->status = 'finished';
                $campaignInsert->finished_date = date('Y-m-d H:i:s');
            }
            $this->saveCampaign($campaignInsert);
        }
        
        return $chosenAd;
    }
    
    public function saveCampaign(Campaigns $campaign) 
    {
        $data = array(
            'userid'                => $campaign->userid,
            'campaign_title'        => $campaign->campaign_title,
            'campaign_type'         => $campaign->campaign_type,
            'campaign_scope'        => $campaign->campaign_scope,
            'total_credits'         => $campaign->total_credits,
            'daily_credits'         => $campaign->daily_credits,
            'remaining_credits'     => $campaign->remaining_credits,
            'campaign_url'          => $campaign->campaign_url,
            'campaign_banner'       => $campaign->campaign_banner,
            'status'                => $campaign->status,
            'start_date'            => $campaign->start_date,
            'extension_date'        => $campaign->extension_date,
            'finished_date'         => $campaign->finished_date,
            'rejection_reason'      => $campaign->rejection_reason,
        );
        
        $campaignid = (int)$campaign->campaignid;
        if(!$campaignid) 
        {
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        else 
        {
            
            if($this->fetchCampaign(array('campaignid', $campaignid))) {
                $this->tableGateway->update($data, array('campaignid' => $campaignid));
                return $campaignid;
            }
            else {
                throw new \Exception('Campaign not found!');
            }
        }
    }
    
    public function deleteCampaign($where)
    {
        $this->tableGateway->delete($where);
    }
}

?>
