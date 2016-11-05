<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    AdvertiserController.php
 * @createdat    Jul 11, 2013 12:20:53 PM
 */

namespace Advertiser\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use Advertiser\Model\Transactions;
use Advertiser\Model\Campaigns;
use Advertiser\Form\NewCampaignForm;
use Advertiser\Form\BannerUploadForm;
use KpasteCore\IpToCountry\IpToCountry;

class AdvertiserController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    
    public function __construct() 
    {
        $auth = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings = new SessionContainer('settings');
        $this->settings = $settings->settings;
    }
    
    public function indexAction() 
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            return(array(
                'theme'     => $this->settings['theme'],
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    /**
     * This action either creates a new campaign, or edits an existing one if campaign is provided
     * and valid
     * 
     * @return type viewModel array
     */
    public function NewCampaignAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {           
            $form = new NewCampaignForm();
            $request = $this->getRequest();
            
            //determine available campaign types
            $availableCampaigns = array();
            foreach($this->settings['top-ads-layout'] as $ad)
            {
                $availableCampaigns[$ad] = true;
            }
            foreach($this->settings['bottom-ads-layout'] as $ad)
            {
                $availableCampaigns[$ad] = true;
            }
            foreach($this->settings['left-ads-layout'] as $ad)
            {
                $availableCampaigns[$ad] = true;
            }
            foreach($this->settings['right-ads-layout'] as $ad)
            {
                $availableCampaigns[$ad] = true;
            }
            
            $translator = $this->getServiceLocator()->get('translator');
            $translations = array(
                'square_button_ltr'   => $translator->translate('Left, Top or Right Square Button'),
                'square_button_b'     => $translator->translate('Bottom Square Button'),
                'vertical_banner'     => $translator->translate('Vertical Banner'),
                'leaderboard_t'       => $translator->translate('Top Leaderboard'),
                'leaderboard_b'       => $translator->translate('Bottom Leaderboard'),
                'iframe'              => $translator->translate('Inline Frame Ads'),
            );
            
            $valueOptions = array();
            foreach($availableCampaigns as $key => $val)
            {
                $valueOptions[$key] = $translations[$key];
            }
            if($this->settings['iframe'])
                $valueOptions['iframe'] = $translations['iframe'];
            $form->get('campaign_type')->setValueOptions($valueOptions)
                            ->setAttribute('id', 'campaignType');
            $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
            
            if($request->isPost())
            {
                $form->setData($request->getPost());

                if($form->isValid())
                {
                    if((int)$request->getPost('daily_credits') > (int)$request->getPost('total_credits'))
                    {
                        return(array(
                            'error'             => true,
                            'result'            => 'INVALID_DATA_PROVIDED',
                            'account_balance'   => $user->account_balance,
                            'currency'          => $this->settings['currency'],
                            'form'              => $form,
                            'localPriceFactor'  => $this->settings['local-price-factor'],
                            'price_sqbtnlrt'    => $this->settings['price-sqbtn-lrt'],
                            'price_sqbtnb'      => $this->settings['price-sqbtn-b'],
                            'price_verbnr'      => $this->settings['price-verbnr'],
                            'price_leaderboardt'=> $this->settings['price-leaderboard-t'],
                            'price_leaderboardb'=> $this->settings['price-leaderboard-b'],
                            'price_iframe'      => $this->settings['price-iframe'],
                            'discountFactor'    => $this->settings['discount-factor'],
                            'currency'          => $this->settings['currency'],
                            'locale'            => $this->settings['locale'],
                            'iframeEnabled'     => $this->settings['iframe'],
                        ));
                    }
                    $price = $this->calculatePrice($request->getPost('total_credits'), 
                            (($request->getPost('campaign_scope') == 'local') ? true : false),
                            $request->getPost('campaign_type'));
                    
                    //check discount coupons
                    $couponid = $request->getPost('couponid');
                    if($couponid)   $couponid = preg_replace("/[^a-zA-Z0-9]+/", "", $couponid);
                    $coupon = $this->getCouponsTable()->fetchCoupons(array('couponid' => $couponid))
                                ->current();
                    $discount = false;
                    $originalPrice = false;
                    if($coupon && $this->getCouponsTable()->couponUsed($couponid, $this->authData->userid))
                    {
                        $discount = $coupon->discount;
                        $originalPrice = $price;
                        $price -= (int)($price * ($discount / 100));
                    }

                    if( $user->account_balance < $price )
                    {
                        return array(
                            'error'             => true,
                            'result'            => 'NOT_ENOUGH_BALANCE',
                            'account_balance'   => $user->account_balance,
                            'price'             => $price,
                            'currency'          => $this->settings['currency'],
                            'form'              => $form,
                            'localPriceFactor'  => $this->settings['local-price-factor'],
                            'price_sqbtnlrt'    => $this->settings['price-sqbtn-lrt'],
                            'price_sqbtnb'      => $this->settings['price-sqbtn-b'],
                            'price_verbnr'      => $this->settings['price-verbnr'],
                            'price_leaderboardt'=> $this->settings['price-leaderboard-t'],
                            'price_leaderboardb'=> $this->settings['price-leaderboard-b'],
                            'price_iframe'      => $this->settings['price-iframe'],
                            'discountFactor'    => $this->settings['discount-factor'],
                            'currency'          => $this->settings['currency'],
                            'locale'            => $this->settings['locale'],
                            'iframeEnabled'     => $this->settings['iframe'],
                        );
                    }
                    $newCampaign = new Campaigns();
                    $newCampaign->exchangeArray($form->getData());
                    $newCampaign->remaining_credits = $newCampaign->total_credits;
                    $newCampaign->status = 'pending';
                    $newCampaign->userid = $user->userid;
                    if(substr($newCampaign->campaign_url, 0, 4) != 'http')     
                        $newCampaign->campaign_url = 'http://' . $newCampaign->campaign_url;

                    $newCampaignId = $this->getCampaignsTable()->saveCampaign($newCampaign);
                                        
                    $user->account_balance = $user->account_balance - $price;
                    $this->getUsersTable()->saveUser($user);
                    
                    KEventManager::trigger('NewCampaignCreated', array(
                        'userid'    => $user->userid,
                        'campaignid'=> $newCampaignId,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    
                    if($newCampaign->campaign_type == 'iframe')
                        return array(
                            'error'         => false, 
                            'result'        => 'CAMPAIGN_CREATED_SUCCESSFULLY', 
                            'discount'      => $discount,
                            'originalPrice' => $originalPrice,
                            'price'         => $price,
                        );
                    else
                        return array(
                            'error'         => false, 
                            'result'        => 'CAMPAIGN_CREATED_SUCCESSFULLY', 
                            'discount'      => $discount,
                            'campaignid'    => $newCampaignId,
                            'originalPrice' => $originalPrice,
                            'price'         => $price,
                        );
                }
                else
                {
                    return array(
                        'form'              => $form, 
                        'error'             => true, 
                        'account_balance'   => $user->account_balance,
                        'result'            => 'INVALID_DATA_PROVIDED',
                        'localPriceFactor'  => $this->settings['local-price-factor'],
                        'price_sqbtnlrt'    => $this->settings['price-sqbtn-lrt'],
                        'price_sqbtnb'      => $this->settings['price-sqbtn-b'],
                        'price_verbnr'      => $this->settings['price-verbnr'],
                        'price_leaderboardt'=> $this->settings['price-leaderboard-t'],
                        'price_leaderboardb'=> $this->settings['price-leaderboard-b'],
                        'price_iframe'      => $this->settings['price-iframe'],
                        'discountFactor'    => $this->settings['discount-factor'],
                        'currency'          => $this->settings['currency'],
                        'locale'            => $this->settings['locale'],
                        'iframeEnabled'     => $this->settings['iframe'],
                    );
                }
            }

            return array(
                'form'              => $form,
                'account_balance'   => $user->account_balance,
                'localPriceFactor'  => $this->settings['local-price-factor'],
                'price_sqbtnlrt'    => $this->settings['price-sqbtn-lrt'],
                'price_sqbtnb'      => $this->settings['price-sqbtn-b'],
                'price_verbnr'      => $this->settings['price-verbnr'],
                'price_leaderboardt'=> $this->settings['price-leaderboard-t'],
                'price_leaderboardb'=> $this->settings['price-leaderboard-b'],
                'price_iframe'      => $this->settings['price-iframe'],
                'discountFactor'    => $this->settings['discount-factor'],
                'currency'          => $this->settings['currency'],
                'locale'            => $this->settings['locale'],
                'iframeEnabled'     => $this->settings['iframe'],
            );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function UploadBannerAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignId = (int)$this->params()->fromRoute('param1');
            $campaign = $this->getCampaignsTable()->fetchCampaign(array('campaignid' => $campaignId));
            if(!$campaign)
            {
                return array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_ID_MISSING_OR_INVALID',
                );
            }
            
            if($campaign->userid != $this->authData->userid)
            {
                return array(
                    'error'     => true,
                    'result'    => 'ILLEGAL_CAMPAIGN_ACCESS',
                );
            }
            
            if($campaign->status != 'pending' && $campaign->status != 'active' && $campaign->status != 'paused')
            {
                return array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_NOT_MODIFIABLE',
                    'campaignid'=> $campaign->campaignid,
                    'bannerDims'=> \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                    'banner'    => $campaign->campaign_banner,
                    'bannerType'=> $campaign->campaign_type,
                );
            }
            
            if($campaign->campaign_type == 'iframe')
            {
                return array(
                    'error'     => true,
                    'result'    => 'NO_BANNER_REQUIRED',
                );
            }
            
            $form = new BannerUploadForm();
            $request = $this->getRequest();
            
            if($request->isPost())
            {
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );
                
                $form->setData($post);
                if($form->isValid())
                {
                    $datat = $form->getData();
                    $data = $datat['banner-file'];
                    if($data['error'])
                    {
                        return array(
                            'error'         => true,
                            'result'        => 'UPLOAD_ERROR',  
                            'form'          => $form,
                            'campaignid'    => $campaignId,
                            'maxBannerSize' => $this->settings['max-banner-size'],
                            'banner'        => $campaign->campaign_banner,
                            'bannerDims'    => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                        );
                    }
                    
                    if((int)$data['size'] > (int)$this->settings['max-banner-size'])
                    {
                        return array(
                            'error'         => true,
                            'result'        => 'UPLOADED_FILE_IS_TOO_LARGE',  
                            'form'          => $form,
                            'campaignid'    => $campaignId,
                            'maxBannerSize' => $this->settings['max-banner-size'],
                            'banner'        => $campaign->campaign_banner,
                            'bannerDims'    => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                        ); 
                    }
                    
                    
                    if(!in_array($data['type'], $this->settings['allowed-banner-types']))
                    {
                        return array(
                            'error'         => true,
                            'result'        => 'INVALID_BANNER_TYPE',  
                            'form'          => $form,
                            'campaignid'    => $campaignId,
                            'maxBannerSize' => $this->settings['max-banner-size'],
                            'banner'        => $campaign->campaign_banner,
                            'bannerDims'    => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                        ); 
                    }
                    
                    if($campaign->campaign_banner)
                    {
                        @unlink(__DIR__ . "banners/" . $campaign->campaign_banner);
                    }
                    
                    $extension = end(explode('.', $data['name']));
                    $newName = md5($data['name'] . $data['size'] . $this->authData->userid . microtime())
                              . ".$extension";
                    move_uploaded_file($data['tmp_name'], "banners/$newName");

                    $campaign->campaign_banner = $newName;
                    $campaign->status = 'pending';
                    $this->getCampaignsTable()->saveCampaign($campaign);
                              
                    KEventManager::trigger('NewCampaignBannerUploaded', array(
                        'userid'    => $this->authData->userid,
                        'campaignid'=> $campaign->campaignid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    
                    return array(
                        'error'             => false, 
                        'result'            => 'UPLOAD_SUCCESSFUL',
                        'banner'            => $newName,
                        'bannerDims'        => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                        'campaignid'        => $campaignId,
                        'bannerType'        => $campaign->campaign_type,
                    );
                }
                else
                {
                    return array(
                        'error'         => true,
                        'result'        => 'INVALID_DATA_PROVIDED',
                        'form'          => $form, 
                        'campaignid'    => $campaignId,
                        'maxBannerSize' => $this->settings['max-banner-size'],
                        'banner'        => $campaign->campaign_banner,
                        'bannerDims'    => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
                    );
                }
            }
           
            return array(
                'form'          => $form, 
                'campaignid'    => $campaignId,
                'maxBannerSize' => $this->settings['max-banner-size'],
                'banner'        => $campaign->campaign_banner,
                'bannerDims'    => \KpasteCore\Utilities::getBannerSize($campaign->campaign_type),
            );
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function CampaignsAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaigns = $this->getCampaignsTable()->
                    fetchCampaigns(array('userid' => $this->authData->userid), true);
            $campaigns->setCurrentPageNumber(
                    (int)$this->params()->fromQuery('page', 1));
            $campaigns->setItemCountPerPage(
                    (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE));
            return array('myCampaigns' => $campaigns);
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function EditCampaignAction() 
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignid = (int)$this->params()->fromRoute('param1');
            $campaign = $this->getCampaignsTable()->fetchCampaign(array(
                'campaignid'    => $campaignid,
            ));

            if(!$campaign)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGN_ID',
                    'campaignid'=> $campaignid,
                ));
            }

            $form = new NewCampaignForm();
            $form->bind($campaign);
            $request = $this->getRequest();

            if($request->isPost())
            {
                $form->setData($request->getPost());

                $campaign->campaign_title = $form->get('campaign_title')->getValue();
                $campaign->daily_credits = (int)$form->get('daily_credits')->getValue();
                $campaign->campaign_url = $form->get('campaign_url')->getValue();
                if(substr($campaign->campaign_url, 0, 4) != 'http')
                    $campaign->campaign_url = 'http://' . $campaign->campaign_url;  
                $this->getCampaignsTable()->saveCampaign($campaign);
                return(array(
                    'error'     => false,
                    'campaignid'=> $campaignid,
                ));
            }

            return(array(
                'form'      => $form,
                'campaignid'=> $campaignid,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function CampaignStatsAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignid = (int)$this->params()->fromRoute('param1');
            if(!$campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            $campaign = $this->getCampaignsTable()->fetchCampaign(array(
                'campaignid'    => $campaignid,
            ));
            
            if(!$campaign || !$campaign->campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            if($campaign->userid != $this->authData->userid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_IS_NOT_YOURS',
                ));
            }
            
            $ads_views = $this->getAdsViewsTable()->fetchAdsViews(array(
                'campaignid'    => $campaignid
            ));
            
            $adsViews = array();
            $iptocountry = new IpToCountry($this->getServiceLocator());
            foreach($ads_views as $ad_view)
            {
                $iptocountry->setIp($ad_view->viewer_ip);
                $ad_view->countryShortName = $iptocountry->getShortName();
                $ad_view->countryLongName = $iptocountry->getLongName();
                $adsViews[] = $ad_view;
            }
            
            return(array(
                'campaign'      => $campaign,
                'adsViews'      => $adsViews,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function ExtendCampaignAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignid = (int)$this->params()->fromRoute('param1');
            if(!$campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            $campaign = $this->getCampaignsTable()->fetchCampaign(array(
                'campaignid'    => $campaignid,
            ));
            
            if(!$campaign || !$campaign->campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            if($campaign->userid != $this->authData->userid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_IS_NOT_YOURS',
                ));
            }
            
            if($campaign->status != 'active' && $campaign->status != 'finished')
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_STATUS',
                ));
            }
            
            $currency           = $this->settings['currency'];
            $localPriceFactor   = $this->settings['local-price-factor'];
            $discountFactor     = $this->settings['discount-factor'];
            $campaignType       = $campaign->campaign_type;
            $campaignPrice      = 10000000;         //I set this high, so in case of errors
                                                    //in price retrieval, user won't get freebies >:)
                                                    //and ofcourse since it's high they can't buy anything

            switch($campaignType)
            {
                case 'square_button_ltr':
                    $campaignPrice = (int)$this->settings['price-sqbtn-lrt'];
                    break;
                case 'square_button_b':
                    $campaignPrice = (int)$this->settings['price-sqbtn-b'];
                    break;
                case 'vertical_banner':
                    $campaignPrice = (int)$this->settings['price-verbnr'];
                    break;
                case 'leaderboard_t':
                    $campaignPrice = (int)$this->settings['price-leaderboard-t'];
                    break;
                case 'leaderboard_b':
                    $campaignPrice = (int)$this->settings['price-leaderboard-b'];
                    break;
                case 'iframe':
                    $campaignPrice = (int)$this->settings['price-iframe'];
                    break;
            }
            
            $request = $this->getRequest();
            if($request->isPost())
            {
                $extraCredits = (int)$request->getPost('extraCredits');
                
                if($extraCredits < 1000 || $extraCredits > 50000)
                {
                    return(array(
                        'error'                 => true,
                        'result'                => 'INVALID_EXTRA_CREDITS',
                        'campaignid'            => $campaignid,
                        'currency'              => $currency,
                        'localPriceFactor'      => $localPriceFactor,
                        'campaignPrice'         => $campaignPrice,
                        'campaignScope'         => $campaign->campaign_scope,
                        'discountFactor'        => $discountFactor,
                        'account_balance'       => $user->account_balance,
                        'price'                 => $price,
                    ));
                }
                
                $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
                $price = $this->calculatePrice(
                        $extraCredits, 
                        ($campaign->campaign_scope == 'local') ? true : false, 
                        $campaign->campaign_type
                );
                if((int)$price > (int)$user->account_balance)
                {
                    return(array(
                        'error'                 => true,
                        'result'                => 'NOT_ENOUGH_BALANCE',
                        'campaignid'            => $campaignid,
                        'currency'              => $currency,
                        'localPriceFactor'      => $localPriceFactor,
                        'campaignPrice'         => $campaignPrice,
                        'campaignScope'         => $campaign->campaign_scope,
                        'discountFactor'        => $discountFactor,
                        'account_balance'       => $user->account_balance,
                        'price'                 => $price,
                    ));
                }
                
                $campaign->total_credits += $extraCredits;
                $campaign->remaining_credits += $extraCredits;
                $campaign->extension_date = date('Y-m-d H:i:s');
                $campaign->status = 'active';
                $this->getCampaignsTable()->saveCampaign($campaign);
                $user->account_balance -= $price;
                $this->getUsersTable()->saveUser($user);
                KEventManager::trigger('CampaignExtended', array(
                    'userid'    => $this->authData->userid,
                    'campaignid'=> $campaign->campaignid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return(array('error' => false, 'result' => 'EXTENSION_SUCCESSFUL'));
            }
            
            return(array(
                'campaignid'            => $campaignid,
                'currency'              => $currency,
                'localPriceFactor'      => $localPriceFactor,
                'campaignPrice'         => $campaignPrice,
                'campaignScope'         => $campaign->campaign_scope,
                'discountFactor'        => $discountFactor,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function PauseCampaignAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignid = (int)$this->params()->fromRoute('param1');
            if(!$campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            $campaign = $this->getCampaignsTable()->fetchCampaign(array(
                'campaignid'    => $campaignid,
            ));
            
            if(!$campaign || !$campaign->campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            if($campaign->userid != $this->authData->userid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_IS_NOT_YOURS',
                ));
            }
            
            if($campaign->status != 'active')
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_NOT_ACTIVE',
                ));
            }
            
            $request = $this->getRequest();
            if($request->isPost())
            {
                $confirm = $request->getPost('confirm', 'NO');
                if($confirm == 'YES')
                {
                    $campaign->status = 'paused';
                    $this->getCampaignsTable()->saveCampaign($campaign);
                    
                    KEventManager::trigger('CampaignPaused', array(
                        'userid'    => $this->authData->userid,
                        'campaignid'=> $campaign->campaignid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    
                    $this->redirect()->toRoute('advertiser', array('action' => 'Campaigns'));
                }
                else
                {
                    $this->redirect()->toRoute('advertiser', array('action' => 'Campaigns'));
                }
            }
            
            return(array('campaignTitle' => $campaign->campaign_title, 'campaignid' => $campaignid));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }    
    
    public function ResumeCampaignAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $campaignid = (int)$this->params()->fromRoute('param1');
            if(!$campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            $campaign = $this->getCampaignsTable()->fetchCampaign(array(
                'campaignid'    => $campaignid,
            ));
            
            if(!$campaign || !$campaign->campaignid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'INVALID_CAMPAIGNID',
                ));
            }
            
            if($campaign->userid != $this->authData->userid)
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_IS_NOT_YOURS',
                ));
            }
            
            if($campaign->status != 'paused')
            {
                return(array(
                    'error'     => true,
                    'result'    => 'CAMPAIGN_NOT_PAUSED',
                ));
            }
            
            $request = $this->getRequest();
            if($request->isPost())
            {
                $confirm = $request->getPost('confirm', 'NO');
                if($confirm == 'YES')
                {
                    $campaign->status = 'active';
                    $this->getCampaignsTable()->saveCampaign($campaign);
                    KEventManager::trigger('CampaignResumed', array(
                        'userid'    => $this->authData->userid,
                        'campaignid'=> $campaign->campaignid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    $this->redirect()->toRoute('advertiser', array('action' => 'Campaigns'));
                }
                else
                {
                    $this->redirect()->toRoute('advertiser', array('action' => 'Campaigns'));
                }
            }
            
            return(array('campaignTitle' => $campaign->campaign_title, 'campaignid' => $campaignid));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }  
    
    public function AccountBalanceAction() 
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            if($this->authData->account_status != 'verified')
            {
                return( array( 
                    'error'     => true,
                    'result'    => 'ACCOUNT_NOT_VERIFIED',
                ));
            }              
            
            $user = $this->getUsersTable()->fetchUser('userid', $this->authData->userid);
            $balance = $user->account_balance;
            $currency = $this->settings['currency'];

            return(array(
                'step1'     => true,
                'balance'   => $balance,
                'currency'  => $currency,
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
        }
    }
    
    public function transactionsAction()
    {
        if($this->authData && $this->authData->userid && $this->authData->user_type == 'advertiser')
        {
            $transactions = $this->getTransactionsTable()->fetchTransactions(array(
                'userid'        => $this->authData->userid,
            ), true, 'transactionid DESC');
            $transactions->setCurrentPageNumber(
                    (int)$this->params()->fromQuery('page', 1));
            $transactions->setItemCountPerPage(
                    (int)$this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE));
            return(array(
                'transactions'      => $transactions,
                'currency'          => $this->settings['currency'],
            ));
        }
        else
        {
            KEventManager::trigger('UnauthorizedAccess', array(
                'url'       => $this->getRequest()->getRequestUri(),
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return array('error' => true, 'result' => 'ACCESS_DENIED');
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
    
    private function getAdsViewsTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\AdsViewsTable');
    }
    
    private function getTransactionsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Advertiser\Model\TransactionsTable');
    }
    
    public function getCouponsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\CouponsTable');
    }
    
    private function calculatePrice($totalViews, $isLocal, $adType)
    {
        $adPrice = 0;
        switch($adType) 
        {
            case 'square_button_ltr':
                $adPrice = (int)$this->settings['price-sqbtn-lrt'];
                break;
            case 'square_button_b':
                $adPrice = (int)$this->settings['price-sqbtn-b'];
                break;
            case 'vertical_banner':
                $adPrice = (int)$this->settings['price-verbnr'];
                break;
            case 'leaderboard_t':
                $adPrice = (int)$this->settings['price-leaderboard-t'];
                break;
            case 'leaderboard_b':
                $adPrice = (int)$this->settings['price-leaderboard-b'];
                break;
            case 'iframe':
                $adPrice = (int)$this->settings['price-iframe'];
                break;
        }
        
        
        $localFactor = 1 + (($isLocal) ? (float)$this->settings['local-price-factor'] : 0);
        $discountFactor = (float)$this->settings['discount-factor'];
        
        $price = (int)( ( $totalViews / 1000 ) * $adPrice * $localFactor );
        $discountTrigonometricFactor = ( $totalViews / 55000 ) * ( M_PI / 3.7 );  //50000 is the max campaign credits
        $discount = $price * ($totalViews / 1000 - 1) * $discountFactor;
    
        $discount = floor( $discount - $discount * sin( $discountTrigonometricFactor ) );

        return( $price - $discount );
    }
}

?>
