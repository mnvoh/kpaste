<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace KpasteCore\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use KpasteCore\RandomSequenceGenerator;
use KpasteCore\KChart\KPoint;
use KpasteCore\KChart\LineChart;

class AjaxController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    
    public function __construct() 
    {
        $auth           = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings       = new SessionContainer( 'settings' );
        $this->settings = $settings->settings;
    }
    
    public function GetRandomSequenceAction()
    {
        $request = $this->getRequest();
        if( $request->isPost() )
        {
            $length = $request->getPost('length', 16);
            $charset = $request->getPost('charset', null);
            $random = new RandomSequenceGenerator();
            
            return( $this->getResponse()->setContent($random->generateRandomSequence($length, $charset)));
        }
    }
    
    public function ReportPasteAction()
    {
        $pasteid = (int)$this->params()->fromRoute( 'param1' );
        $paste = $this->getPastesTable()->fetchPaste( array( 'pasteid' => $pasteid ) );
        $translator = $this->getServiceLocator()->get('translator');
        if( !$paste )
        {
            $result = $translator->translate('Paste ID is invalid!');
            return( $this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $result,
                'button'    => $translator->translate('OK'),
            ))));
        }
        
        try
        {
            $this->getReportedPastesTable()->insertReport( $pasteid, 
                    $this->getRequest()->getServer( 'REMOTE_ADDR' ) );
        }
        catch( \Exception $e )
        {
            $result = $translator->translate('You have already reported this paste!');
            return( $this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $result,
                'button'    => $translator->translate('OK'),
            ))));
        }
        $result = $translator->translate('Your report was successfully registered. Thanks for your cooperation.');
        
        $userid = isset($this->authData->userid) ? $this->authData->userid : null;
        KEventManager::trigger('PasteReported', array(
                'userid'    => $userid,
                'pasteid'   => $pasteid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
        
        return( $this->getResponse()->setContent(json_encode(array(
            'error'     => false,
            'result'    => $result,
            'button'    => $translator->translate('OK'),
        ))));  
    }
    
    public function ThumbsUpPasteAction()
    {
        $pasteid = (int)$this->params()->fromRoute( 'param1' );
        $paste = $this->getPastesTable()->fetchPaste( array( 'pasteid' => $pasteid ) );
        $translator = $this->getServiceLocator()->get('translator');
        
        if( !$paste )
        {
            return( $this->getResponse()->setContent( json_encode( array(
                'error'     => true,
                'result'    => 'INVALID_PASTE_ID',
            ) ) ) );
        }
        
        try
        {
            $this->getThumbsTable()->insertThumb( $pasteid, 
                    $this->getRequest()->getServer( 'REMOTE_ADDR' ), 'up' );
        }
        catch( \Exception $e )
        {
            return( $this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $translator->translate('You have already voted for this paste!'),
            ))));
        }
        $userid = isset($this->authData->userid) ? $this->authData->userid : null;
        KEventManager::trigger('PasteUpVoted', array(
                'userid'    => $userid,
                'pasteid'   => $pasteid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
        
        $thumbsUpCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'up');
        $thumbsDownCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'down');
                         
        $upsp = (int)(($thumbsUpCount / ($thumbsDownCount + $thumbsUpCount)) * 100) . "%";
        
        return( $this->getResponse()->setContent(json_encode(array(
            'error'     => false,
            'result'    => $translator->translate('Thank you! Your vote has been registered.'),
            'upsp'      => $upsp,
            'ups'       => $thumbsUpCount,
        ))));
    }
    
    public function ThumbsDownPasteAction()
    {
        $pasteid = (int)$this->params()->fromRoute( 'param1' );
        $paste = $this->getPastesTable()->fetchPaste( array( 'pasteid' => $pasteid ) );
        $translator = $this->getServiceLocator()->get('translator');
        
        if( !$paste )
        {
            return( $this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => 'INVALID_PASTE_ID',
            ))));
        }
        
        try
        {
            $this->getThumbsTable()->insertThumb( $pasteid, 
                    $this->getRequest()->getServer( 'REMOTE_ADDR' ), 'down' );
        }
        catch( \Exception $e )
        {
            return( $this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $translator->translate('You have already voted for this paste!'),
            ))));
        }
        $userid = isset($this->authData->userid) ? $this->authData->userid : null;
        KEventManager::trigger('PasteDownVoted', array(
                'userid'    => $userid,
                'pasteid'   => $pasteid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
        
        $thumbsUpCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'up');
        $thumbsDownCount = $this->getThumbsTable()->thumbsCount($paste->pasteid, 'down');
        
        $upsp = (int)(($thumbsUpCount / ($thumbsDownCount + $thumbsUpCount)) * 100) . "%";
        
        return( $this->getResponse()->setContent(json_encode(array(
            'error'     => false,
            'result'    => $translator->translate('Thank you! Your vote has been registered.'),
            'upsp'      => $upsp,
            'downs'     => $thumbsDownCount,
        ))) );
    }
    
    public function GetUnreadMessagesCountAction()
    {
        if( !isset($this->authData->userid) || !$this->authData->userid )
        {
            return($this->getResponse()->setContent(json_encode(array(
                'error' => true,
                'result'=> 'ACCESS_DENIED',
            ))));
        }
        
        $userid = (int)$this->params()->fromRoute('param1');;
        
        $count = 0;
        if($this->authData->user_type == 'admin' || $this->authData->user_type == 'masteradmin')  
            $count = $this->getSupportTicketsTable()->getUnreadMessagesCount($userid, true);
        else
            $count = $this->getSupportTicketsTable()->getUnreadMessagesCount($userid);
        
        return($this->getResponse()->setContent(json_encode(array(
            'error' => false,
            'result'=> $count,
        ))));
    }
    
    public function getCampaignChartAction()
    {
        $campaignid = (int)$this->params()->fromRoute('param1');
        
        $this->RemoveOldCharts();
        
        $path = "/images/charts/campaign$campaignid.png";
        
        if(file_exists(ROOT_PATH . $path))
        {
            return($this->getResponse()->setContent(json_encode(array(
                'chart'     => $path,
                'cached'    => true,
            ))));
        }
                
        $data = $this->getAdsViewsTable()->fetchCampaignChartData($campaignid);
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
        
        $width = (int)$this->params()->fromQuery('width', 900);
        
        $lineChart = new LineChart($width, 300, $points, 2);
        $lineChart->drawChart(ROOT_PATH . $path);

        return($this->getResponse()->setContent(json_encode(array(
            'chart'     => $path,
            'cached'    => false,
        ))));
    }
    
    public function RemoveOldCharts()
    {
        $folder = ROOT_PATH . '/images/charts/';
        $filetypes = "*.png";
        $expiretime = 60;      //expire time in minutes, 1 hour = 60
        
        foreach (glob($folder . $filetypes) as $Filename) {
            // Read file creation time
            $FileCreationTime = filectime($Filename);
            // Calculate file age in seconds
            $FileAge = time() - $FileCreationTime;
            // Is the file older than the given time span?
            if ($FileAge > ($expiretime * 60)) {
                //delete files:
                unlink($Filename);
            }
        }
    }
    
    public function fetchAdsAction()
    {
        $adTypes = $this->params()->fromQuery('adtypes');
        $pasteid = $this->params()->fromQuery('pasteid');
        
        if(!$adTypes || !$pasteid)
        {
            return($this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => 'MISSING_DATA',
            ))));
        }
        
        $adTypesExploded = explode(',', $adTypes);
        $ads = array();
        
        $sm = $this->getServiceLocator();
        $ip = $this->getRequest()->getServer('REMOTE_ADDR');
        $userAgent = $this->getRequest()->getServer('HTTP_USER_AGENT');
        $foundActiveAd = false;

        foreach($adTypesExploded as $adType)
        {
            list($adTypeName, $count) = explode(':', $adType);
            $ads[$adTypeName] = array();
            
            for($i = 0; $i < $count; $i++)
            {
                $retrievedAd = $this->getCampaignsTable()->fetchNextAd($adTypeName, $ip, $userAgent, $sm);
                if($retrievedAd && $retrievedAd['campaign_banner'])
                {
                    $bannerPath = $this->url()->fromRoute('kpastecore', array(
                        'lang'      => substr($this->layout()->language, 0, 2),
                        'controller'=> 'ViewPaste',
                        'action'    => 'banner',
                        'param1'    => $retrievedAd['campaignid'],
                    ));
                    $url = $retrievedAd['campaign_url'];
                    $title = $retrievedAd['campaign_title'];
                    $extension = end(explode('.', $retrievedAd['campaign_banner']));
                    $size = \KpasteCore\Utilities::getBannerSize($adTypeName);
                    if($extension == 'swf'):
                        $ads[$adTypeName][] = \KpasteCore\Utilities::getFlashMarkup($bannerPath, $size);   
                    else:
                        $ads[$adTypeName][] = <<<BANNER
<a href="$url" title="$title">
    <img src='$bannerPath' width='{$size['width']}' height='{$size['height']}' />
 </a>
BANNER;
                    endif;
                    
                    
                    $foundActiveAd = true;
                }
                else
                {
                    $ads[$adTypeName][] = file_get_contents(ROOT_NAME . "/" . $adTypeName . ".html");
                }
            }
        }
        
        if($this->settings['iframe'])
        {
            $iframeAd = $this->getCampaignsTable()->fetchNextAd('iframe', $ip, $userAgent, $sm);
            if($iframeAd)
            {
                $foundActiveAd = true;
                $ads['iframe'] = $iframeAd['campaign_url'];
            }
            else
            {
                $ads['iframe'] = 'NO';
            }
        }
        else
        {
            $ads['iframe'] = false;
        }
        
        if($foundActiveAd)
        {
            if($this->getPasteViewsTable()->isPasteElligibleForViewAddition($pasteid, $ip))
            {
                $pasteView = new \Paster\Model\PasteViews;
                $pasteView->pasteid = $pasteid;
                $pasteView->user_agent = $this->getRequest()->getServer('USER_AGENT');
                $pasteView->view_datetime = date('Y-m-d H:i:s');
                $pasteView->viewer_ip = $ip;
                $pasteView->user_agent = $this->getRequest()->getServer('HTTP_USER_AGENT');
                $this->getPasteViewsTable()->savePasteView($pasteView);
            }
        }
        
        return($this->getResponse()->setContent(json_encode($ads)));
    }
    
    public function getCouponAction()
    {
        $translator = $this->serviceLocator->get('translator');
        if($this->authData && $this->authData->userid)
        {
            $couponid = $this->params()->fromRoute('param1');
            
            if($couponid)
            {
                $couponid = preg_replace("/[^a-zA-Z0-9]+/", "", $couponid);
                $coupon = $this->getCouponsTable()->fetchCoupons(array(
                    'couponid'  => $couponid,
                ))->current();
                if($coupon)
                {
                    return($this->getResponse()->setContent(json_encode(array(
                        'error'     => false,
                        'result'    => sprintf($translator->translate(
                                    'Coupon Discount: %s%%, Coupons Left: %s'
                                ), $coupon->discount, $coupon->count),
                    ))));
                }
                else
                {
                    return($this->getResponse()->setContent(json_encode(array(
                        'error'     => true,
                        'result'    => $translator->translate('Coupon doesn\'t exist.'),
                    ))));
                }
            }
            return($this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $translator->translate('Coupon ID is missing.'),
            ))));
        }
        else
        {
            return($this->getResponse()->setContent(json_encode(array(
                'error'     => true,
                'result'    => $translator->translate('Access Denied!'),
            ))));
        }
    }
    
    public function getCouponsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\CouponsTable');
    }
    
    public function getPastesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PastesTable');
    }
    public function getPasteViewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PasteViewsTable');
    }
    public function getAdsViewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\AdsViewsTable');
    }
    
    public function getReportedPastesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ReportedPastesTable');
    }
    
    public function getThumbsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\ThumbsTable');
    }
    
    public function getSupportTicketsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Support\Model\SupportTicketsTable');
    }
    
    public function getCampaignsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\CampaignsTable');
    }
}
