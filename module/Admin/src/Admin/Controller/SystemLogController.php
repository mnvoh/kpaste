<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    SystemLogController.php
 * @createdat    Jul 27, 2013 12:20:53 PM
 */

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;
use Zend\Db\Sql\Where;

use KpasteCore\IpToCountry\IpToCountry;

class SystemLogController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    protected $acl;
    
    public function __construct()
    {
        $auth           = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings       = new SessionContainer('settings');
        $this->settings = $settings->settings;
        $aclArray       = new SessionContainer( 'acl' );
        $this->acl      = $aclArray->acl;
    }
    
    private function isAllowed($permission)
    {
        if( !$this->acl || !is_array( $this->acl ) )
            return false;
        if( ($key = array_search( $permission, $this->acl)) === FALSE )
        {
            return false;
        }
        return true;
    }
    
    public function indexAction()
    {
        
    }
    
    public function ViewTransactionsAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_TRANSACTIONS') )
        {
            $request = $this->getRequest();
            $clear = $request->getPost('clear', null);
            if( $request->isPost() && !$clear )
            {
                $field      = $request->getPost( 'field' );
                $operator   = $request->getPost( 'operator' );
                $value      = $request->getPost( 'value' );
                
                $where      = new Where();
                switch( $field )
                {
                    case 'uid':
                        $field = 'userid';
                        break;
                    case 'mnt':
                        $field = 'amount';
                        break;
                    case 'sts':
                        $field = 'status';
                        break;
                    case 'rdt':
                        $field = 'requested_datetime';
                        break;
                    default:
                        $field = null;
                        break;
                }
                
                if( $field )
                {
                    switch( $operator )
                    {
                        case 'eq':
                            $where->equalTo( $field, $value );
                            break;
                        case 'gt':
                            $where->greaterThan( $field, $value );
                            break;
                        case 'lt':
                            $where->lessThan( $field, $value );
                            break;
                        default:
                            $where = null;
                            break;
                    }
                    if( $where )
                    {
                        $transactions = $this->getTransactionsTable()->fetchTransactions( $where, true );
                        $transactions->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
                        $transactions->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
                        return(array(
                            'transactions'  => $transactions,
                        ));
                    }
                }
            }
            $transactions = $this->getTransactionsTable()->fetchTransactions( null, true );
            $transactions->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $transactions->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            return(array(
                'transactions'  => $transactions,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewCheckoutsAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_CHECKOUT_REQUESTS') )
        {
            $request = $this->getRequest();
            $error_result = null;
            if( $request->isPost() )
            {
                $checkoutid = $request->getPost( 'checkoutid', false );
                $checkout = $this->getCheckoutsTable()->fetchCheckout( array(
                    'checkoutid'    => $checkoutid,
                ) );
                if( $checkout )
                {
                    if( 'paid' == $request->getPost( 'status' ) )
                    {
                        $checkout->status = 'paid';
                        $checkout->transaction_tracking_code = $request->getPost( 'tracking_code' );
                        $checkout->transaction_datetime = $request->getPost( 'datetime' );
                        $checkout->description = $request->getPost( 'description' );
                        $this->getCheckoutsTable()->saveCheckout( $checkout );
                        KEventManager::trigger('PayoutMade', array(
                            'userid'    => $this->authData->userid,
                            'checkoutid'=> $checkoutid,
                            'amount'    => $checkout->amount,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                    }
                    else
                    {
                        $checkout->status = 'denied';
                        $checkout->transaction_tracking_code = null;
                        $checkout->transaction_datetime = null;
                        $checkout->description = $request->getPost( 'description' );
                        $this->getCheckoutsTable()->saveCheckout( $checkout );
                        KEventManager::trigger('CheckoutDenied', array(
                            'userid'    => $this->authData->userid,
                            'checkoutid'=> $checkoutid,
                            'amount'    => $checkout->amount,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                    }
                }
            }
            
            $pendingCheckouts = $this->getCheckoutsTable()->fetchCheckouts( array(
                'status'    => 'pending'
            ), true );
            $pendingCheckouts->setCurrentPageNumber( (int)$this->params()->fromQuery('ppage', 1) );
            $pendingCheckouts->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            $notPendingWhere = new Where();
            $notPendingWhere->notEqualTo( 'status', 'pending' );
            $otherCheckouts = $this->getCheckoutsTable()->fetchCheckouts( $notPendingWhere, true );
            $otherCheckouts->setCurrentPageNumber( (int)$this->params()->fromQuery('opage', 1) );
            $otherCheckouts->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            if( $error_result )
            {
                return( array(
                    'pendingCheckouts'  => $pendingCheckouts,
                    'otherCheckouts'    => $otherCheckouts,
                    'error'             => true,
                    'result'            => $error_result,
                ) );
            }
            else
            {
                return( array(
                    'pendingCheckouts'  => $pendingCheckouts,
                    'otherCheckouts'    => $otherCheckouts,
                ) );
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewPastesAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_PASTES') )
        {
            $request = $this->getRequest();
            $error_result = null;
            if( $request->isPost() )
            {
                $action = $request->getPost( 'action' );
                $pasteids = $request->getPost( 'pasteid' );
                
                switch( $action )
                {
                    case 'close':
                        if( !$this->isAllowed( 'CLOSE_PASTE' ) )
                        {
                            $error_result = 'PERMISSION_DENIED_FOR_CLOSING_OR_OPENING_PASTE';
                            break;
                        }
                        foreach( $pasteids as $pasteid )
                        {
                            $paste = $this->getPastesTable()->fetchPaste( array( 
                                'pasteid'   => (int)$pasteid,
                            ));
                            $paste->status  = 'closed';
                            $this->getPastesTable()->savePaste( $paste );
                        }
                        KEventManager::trigger('PastesClosed', array(
                            'pasteids'       => $pasteids,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                        break;
                    case 'open':
                        if( !$this->isAllowed( 'CLOSE_PASTE' ) )
                        {
                            $error_result = 'PERMISSION_DENIED_FOR_CLOSING_OR_OPENING_PASTE';
                            break;
                        }
                        foreach( $pasteids as $pasteid )
                        {
                            $paste = $this->getPastesTable()->fetchPaste( array( 
                                'pasteid'   => (int)$pasteid,
                            ));
                            $paste->status  = 'active';
                            $this->getPastesTable()->savePaste( $paste );
                        }
                        KEventManager::trigger('PastesOpened', array(
                            'pasteids'       => $pasteids,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                        break;
                    case 'delete':
                        if( !$this->isAllowed( 'DELETE_PASTES' ) )
                        {
                            $error_result = 'PERMISSION_DENIED_FOR_DELETING_PASTE';
                            break;
                        }
                        foreach( $pasteids as $pasteid )
                        {
                            $this->getReportedPastesTable()->deleteReports( array( 
                                'pasteid'   => (int)$pasteid
                            ) );
                            $this->getThumbsTable()->deleteThumbs( array( 
                                'pasteid'   => (int)$pasteid
                            ) );
                            $this->getPasteViewsTable()->deletePasteViews( array( 
                                'pasteid'   => (int)$pasteid
                            ));
                            $this->getPastesTable()->deletePaste(array( 
                                'pasteid'   => (int)$pasteid
                            ));
                        }
                        KEventManager::trigger('PastesDeleted', array(
                            'pasteids'       => $pasteids,
                            'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                        ), $this->getServiceLocator());
                        break;
                }
            }
            
            $pastes = $this->getPastesTable()->fetchPastes( null, true );
            $pastes->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $pastes->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            if( $error_result )
            {
                return( array(
                    'pastes'            => $pastes,
                    'error'             => true,
                    'result'            => $error_result,
                ) );
            }
            else
            {
                return( array(
                    'pastes'            => $pastes,
                ) );
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewReportedPastesAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_REPORTED_PASTES') )
        {
            $request = $this->getRequest();
            $error_result = null;
            if( $request->isPost() )
            {
                $action = $request->getPost( 'action' );
                $pasteids = $request->getPost( 'pasteid' );
                if( is_array( $pasteids ) )
                {
                    switch( $action )
                    {
                        case 'close':
                            if( !$this->isAllowed( 'CLOSE_PASTE' ) )
                            {
                                $error_result = 'PERMISSION_DENIED_FOR_CLOSING_OR_OPENING_PASTE';
                                break;
                            }
                            foreach( $pasteids as $pasteid )
                            {
                                $paste = $this->getPastesTable()->fetchPaste( array( 
                                    'pasteid'   => (int)$pasteid,
                                ));
                                $paste->status  = 'closed';
                                $this->getPastesTable()->savePaste( $paste );
                            }
                            KEventManager::trigger('PastesClosed', array(
                                'pasteids'       => $pasteids,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            break;
                        case 'delete':
                            if( !$this->isAllowed( 'DELETE_PASTES' ) )
                            {
                                $error_result = 'PERMISSION_DENIED_FOR_DELETING_PASTE';
                                break;
                            }
                            foreach( $pasteids as $pasteid )
                            {
                                $this->getReportedPastesTable()->deleteReports( array( 
                                'pasteid'   => (int)$pasteid
                                ) );
                                $this->getThumbsTable()->deleteThumbs( array( 
                                    'pasteid'   => (int)$pasteid
                                ) );
                                $this->getPasteViewsTable()->deletePasteViews( array( 
                                    'pasteid'   => (int)$pasteid
                                ));
                                $this->getPastesTable()->deletePaste(array( 
                                    'pasteid'   => (int)$pasteid
                                ));
                            }
                            KEventManager::trigger('PastesDeleted', array(
                                'pasteids'       => $pasteids,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            break;
                    }
                }
            }    
            
            $pastes = $this->getReportedPastesTable()->fetchReportedPastes( null, true );
            $pastes->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $pastes->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            if( $error_result )
            {
                return( array(
                    'pastes'            => $pastes,
                    'error'             => true,
                    'result'            => $error_result,
                ) );
            }
            else
            {
                return( array(
                    'pastes'            => $pastes,
                ) );
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewSystemActivitiesAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_SYSTEM_ACTIVITIES') )
        {
            //delete old system activities
            $days = (int)$this->settings['max-activities-age'];
            $this->getSystemActivitiesTable()->deleteOldActivities($days);
            
            $request = $this->getRequest();
            $where = new Where();
            $filter = null;
            if( $request->isPost() )
            {
                if( $request->getPost( 'action', null ) != 'clear' )
                {
                    $userid         = $request->getPost( 'userid', '' );
                    $userip         = $request->getPost( 'userip', '' );
                    $datetimeMin    = $request->getPost( 'datetimeMin', '' );
                    $datetimeMax    = $request->getPost( 'datetimeMax', '' );
                    $filter         = $request->getPost( 'filter', null );

                    if( strlen( $userid ) )
                        $where->equalTo( 'userid', $userid );

                    if( strlen( $userip ) ) 
                        $where->equalTo( 'userip', $userip );

                    if( strlen( $datetimeMax ) && !strlen( $datetimeMin ) )
                        $where->between ( 'activity_datetime', '1000-01-01 00:00', $datetimeMax );
                    else if( !strlen( $datetimeMax ) && strlen( $datetimeMin ) )
                        $where->between ( 'activity_datetime', $datetimeMin, '1000-01-01 00:00' );
                    else if( strlen( $datetimeMax ) && strlen( $datetimeMin ) )
                        $where->between ( 'activity_datetime', $datetimeMin, $datetimeMax );
                }
            }
            
            $activities = $this->getSystemActivitiesTable()->fetchActivities( $where, $filter );
            $activities->setCurrentPageNumber( (int)$this->params()->fromQuery('page', 1) );
            $activities->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            $ipInfo = new IpToCountry( $this->getServiceLocator() );
             
            return( array( 
                'activities'    => $activities,
                'ipInfo'        => $ipInfo,
            ) );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewAdCampaignsAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_USERS_AD_CAMPAIGNS') )
        {
            $request    = $this->getRequest();
            $error      = false;
            if( $request->isPost() )
            {
                $action     = $request->getPost( 'action', null );
                $campids   = $request->getPost( 'campid', null );
                if( is_array( $campids ) )
                {
                    switch( $action )
                    {
                        case 'accept':
                            if( !$this->isAllowed('ACCEPT_AD_CAMPAIGN') )
                            {
                                $error      = 'ACCEPT_AD_CAMPAIGN_NOT_ALLOWED';
                                break;
                            }
                            foreach( $campids as $campid )
                            {
                                $camp = $this->getCampaignsTable()->fetchCampaign(array(
                                    'campaignid'    => $campid
                                ));
                                $camp->status       = 'active';
                                $camp->start_date   = date( 'Y-m-d H:i:s' );
                                $this->getCampaignsTable()->saveCampaign( $camp );
                            }
                            KEventManager::trigger('AdCampaignAccepted', array(
                                'campaignid'       => $campids,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            break;
                        case 'reject':
                            if( !$this->isAllowed('REJECT_AD_CAMPAIGN') )
                            {
                                $error      = 'REJECT_AD_CAMPAIGN_NOT_ALLOWED';
                                break;
                            }
                            foreach( $campids as $campid )
                            {
                                $camp = $this->getCampaignsTable()->fetchCampaign(array(
                                    'campaignid'    => $campid
                                ));
                                $camp->status = 'rejected';
                                $camp->rejection_reason = $request->getPost('rejectionReason');
                                $this->getCampaignsTable()->saveCampaign( $camp );
                            }
                            KEventManager::trigger('AdCampaignRejected', array(
                                'campaignid'       => $campids,
                                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                            ), $this->getServiceLocator());
                            break;
                    }
                }
            }
            
            $pendingCamps   = $this->getCampaignsTable()->fetchCampaigns( array(
                'status' => 'pending',
            ), true );
            $pendingCamps->setCurrentPageNumber( (int)$this->params()->fromQuery('ppage', 1) );
            $pendingCamps->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            $activeCamps    = $this->getCampaignsTable()->fetchCampaigns( array(
                'status' => 'active',
            ), true );
            $activeCamps->setCurrentPageNumber( (int)$this->params()->fromQuery('apage', 1) );
            $activeCamps->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            $where          = new Where();
            $where          ->notEqualTo( 'status', 'pending' );
            $where          ->notEqualTo( 'status', 'active' );
            $otherCamps     = $this->getCampaignsTable()->fetchCampaigns( $where, true );
            $otherCamps->setCurrentPageNumber( (int)$this->params()->fromQuery('opage', 1) );
            $otherCamps->setItemCountPerPage( (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
            
            if( $error )
            {
                return( array( 
                    'pendingCamps'  => $pendingCamps,
                    'activeCamps'   => $activeCamps,
                    'otherCamps'    => $otherCamps,
                    'error'         => true,
                    'result'        => $error,
                ) );
            }
            else
            {
                return( array( 
                    'pendingCamps'  => $pendingCamps,
                    'activeCamps'   => $activeCamps,
                    'otherCamps'    => $otherCamps,
                ) );
            }
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }
    
    public function ViewBannerAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed('VIEW_USERS_AD_CAMPAIGNS') )
        {
            $campaignid = $this->params()->fromRoute('param1');
            $campaign = $this->getCampaignsTable()->fetchCampaign(array('campaignid' => $campaignid));
            
            if(!$campaign)
            {
                return(array(
                    'error' => true,
                    'result'=> 'INVALID_CAMPAIGN_ID',
                ));
            }
            
            $banner = $campaign->campaign_banner;
            if(!file_exists('banners/' . $banner))
            {
                return(array(
                    'error' => true,
                    'result'=> 'BANNER_NOT_FOUND',
                ));
            }
            
            return( array (
                'campaignid'    => $campaignid,
                'banner'        => $campaign->campaign_banner,
                'bannerType'    => $campaign->campaign_type
            ) );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return(array(
                'error'     => true,
                'result'    => 'ACCESS_DENIED', 
            )); 
        }
    }

    public function getCampaignsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\CampaignsTable');
    }
    
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
    
    public function getTransactionsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\TransactionsTable');
    }
    
    public function getCheckoutsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get( 'Paster\Model\CheckoutsTable' );
    }
    
    public function getPastesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get( 'Paster\Model\PastesTable' );
    }
    
    public function getPasteViewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get( 'Paster\Model\PasteViewsTable' );
    }
    
    public function getSystemActivitiesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get( 'KpasteCore\Model\SystemActivitiesTable' );
    }
    
    private function getReportedPastesTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ReportedPastesTable');
    }
    
    private function getThumbsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ThumbsTable');
    }
}

?>
