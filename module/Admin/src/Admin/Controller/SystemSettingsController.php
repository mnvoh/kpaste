<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    SystemSettingsController.php
 * @createdat    Jul 27, 2013 12:20:53 PM
 */

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

use Admin\Form\SettingsForm;
use KpasteCore\Form\NewsForm;
use KpasteCore\Model\News;

class SystemSettingsController extends AbstractActionController
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
        if($this->authData && $this->isAllowed('CHANGE_SYSTEM_SETTINGS'))
        {
            return(array(
                'error'     => false,
                'theme'     => $this->settings['theme']
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
    
    public function GeneralSettingsAction()
    {
        if($this->authData && $this->isAllowed('CHANGE_SYSTEM_SETTINGS'))
        {
            $translator = $this->getServiceLocator()->get('translator');
            $form = new SettingsForm();
            $request = $this->getRequest();
            
            if($request->isPost())
            {
                $form->setData($request->getPost());
                if($form->isValid())
                {
                    foreach($this->settings as $settingName => $settingValue)
                    {
                        try
                        {
                            $element = $form->get($settingName);
                            $this->settings[$settingName] = $element->getValue();
                        }
                        catch(\Exception $ex)
                        {
                            continue;
                        }
                    }
                    $confWriter = new \Zend\Config\Writer\Json();
                    $confWriter->toFile('config/settings.json', $this->settings);
                    KEventManager::trigger('SystemSettingsChanged', array(
                        'userid'    => $this->authData->userid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    return(array(
                        'error'     => false,
                    ));
                }
                else
                {
                    return(array(
                        'error'     => true,
                        'result'    => 'INVALID_DATA_PROVIDED',
                        'form'      => $form,
                    ));
                }
            }
            
            
            $form->get('theme')->setValueOptions($this->getAvailableThemes());
            $form->get('language')->setValueOptions($this->getAvailableLanguages());
            $form->get('direction')->setValueOptions(array(
                'ltr'   => $translator->translate('Left to Right'),
                'rtl'   => $translator->translate('Right to Left'),
            ));
            $form->get('currency')->setValueOptions(array(
                '$'     => 'USD',
                'ریال'   => 'ریال',
                'تومان'   => 'تومان',
            ));         
            $form->get('calendar')->setValueOptions(array(
                'G'   => $translator->translate('Gregorian'),
                'S'   => $translator->translate('Shamsi'),
            ));
            
            $form->get('site-name')->setValue($this->settings['site-name']);
            $form->get('site-url')->setValue($this->settings['site-url']);
            $form->get('admin-email')->setValue($this->settings['admin-email']);
            $form->get('theme')->setValue($this->settings['theme']);
            $form->get('language')->setValue($this->settings['language']);
            $form->get('direction')->setValue($this->settings['direction']);
            $form->get('locale')->setValue($this->settings['locale']);
            $form->get('payment-per-thousand')->setValue($this->settings['payment-per-thousand']);
            $form->get('price-sqbtn-lrt')->setValue($this->settings['price-sqbtn-lrt']);
            $form->get('price-sqbtn-b')->setValue($this->settings['price-sqbtn-b']);
            $form->get('price-verbnr')->setValue($this->settings['price-verbnr']);
            $form->get('price-leaderboard-t')->setValue($this->settings['price-leaderboard-t']);
            $form->get('price-leaderboard-b')->setValue($this->settings['price-leaderboard-b']);
            $form->get('price-iframe')->setValue($this->settings['price-iframe']);
            $form->get('local-price-factor')->setValue($this->settings['local-price-factor']);
            $form->get('discount-factor')->setValue($this->settings['discount-factor']);
            $form->get('min-balance-for-checkout')->setValue($this->settings['min-balance-for-checkout']);
            $form->get('min-recharge-amount')->setValue($this->settings['min-recharge-amount']);
            $form->get('currency')->setValue($this->settings['currency']);
            $form->get('calendar')->setValue($this->settings['calendar']);
            $form->get('max-banner-size')->setValue($this->settings['max-banner-size']);
            $form->get('max-attachment-size')->setValue($this->settings['max-attachment-size']);
            $form->get('max-activities-age')->setValue($this->settings['max-activities-age']);
            
            return(array(
                'form'      => $form,
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
    
    public function ChangeAdsLayoutAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            if($this->authData && $this->isAllowed('CHANGE_SYSTEM_SETTINGS'))
            {
                $this->settings['top-ads-layout'] = json_decode($request->getPost('top'));
                $this->settings['right-ads-layout'] = json_decode($request->getPost('right'));
                $this->settings['bottom-ads-layout'] = json_decode($request->getPost('bottom'));
                $this->settings['left-ads-layout'] = json_decode($request->getPost('left'));
                $this->settings['iframe'] = $request->getPost('iframe');
                $confWriter = new \Zend\Config\Writer\Json();
                $confWriter->toFile('config/settings.json', $this->settings);
                KEventManager::trigger('AdsLayoutChanged', array(
                    'userid'    => $this->authData->userid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                return($this->getResponse()->setContent('succeeded'));
            }
            else
            {
                return($this->getResponse()->setContent('Access Denied!'));
            }
        }
        else
        {
            if($this->authData && $this->isAllowed('CHANGE_SYSTEM_SETTINGS'))
            {

                return(array(
                    'top'   => $this->settings['top-ads-layout'],
                    'right' => $this->settings['right-ads-layout'],
                    'bottom'=> $this->settings['bottom-ads-layout'],
                    'left'  => $this->settings['left-ads-layout'],
                    'iframe'=> $this->settings['iframe'],
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
    }
    
    public function ChangeTermsAndPrivacyAction()
    {
        if($this->authData && $this->isAllowed('CHANGE_SYSTEM_SETTINGS'))
        {
            $request = $this->getRequest();
            if($request->isPost())
            {
                $terms = $request->getPost('terms');
                $privacy = $request->getPost('privacy');
                $qaa = $request->getPost('qaa');
                
                $terms      = preg_replace('/<script>|<\?php/', '<!--', $terms);
                $privacy    = preg_replace('/<script>|<\?php/', '<!--', $privacy);
                $qaa        = preg_replace('/<script>|<\?php/', '<!--', $qaa);
                $terms      = preg_replace('/<\/script>|\?>/', '-->', $terms);
                $privacy    = preg_replace('/<\/script>|\?>/', '-->', $privacy);
                $qaa        = preg_replace('/<\/script>|\?>/', '-->', $qaa);
                
                file_put_contents(ROOT_PATH . '/privacy', $privacy);
                file_put_contents(ROOT_PATH . '/terms', $terms);
                file_put_contents(ROOT_PATH . '/qaa', $qaa);
                KEventManager::trigger('TermsAndPrivacyChanged', array(
                    'userid'    => $this->authData->userid,
                    'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
            }
            
            $terms = file_get_contents(ROOT_PATH . '/terms');
            $privacy = file_get_contents(ROOT_PATH . '/privacy');
            $qaa = file_get_contents(ROOT_PATH . '/qaa');
            return(array(
                'terms'     => $terms,
                'privacy'   => $privacy,
                'qaa'       => $qaa,
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
    
    public function PublishAnnouncementAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed( 'PUBLISH_NEWS' ) )
        {
            $newsid = (int)$this->params()->fromRoute( 'param1' );
            $request = $this->getRequest();
            $form = new NewsForm();
            $news = $this->getNewsTable()->fetchNews( false, array( 'newsid' => $newsid ) )->current();
            
            if( $request->isPost() )
            {
                $form->setData( $request->getPost() );
                if( $form->isValid() )
                {
                    if( $news )
                    {
                        $news->exchangeArray( $form->getData() );
                        $news->newsid = $newsid;
                        $news->userid = $this->authData->userid;
                        $news->newsdate = date( 'Y-m-d H:i:s' );
                        $this->getNewsTable()->saveNews( $news );
                    }
                    else
                    {
                        $news = new News();
                        $news->exchangeArray( $form->getData() );
                        $news->userid = $this->authData->userid;
                        $news->newsdate = date( 'Y-m-d H:i:s' );
                        $this->getNewsTable()->saveNews( $news );
                    }
                    
                    KEventManager::trigger('AnnouncementPublished', array(
                        'userid'    => $this->authData->userid,
                        'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
                    ), $this->getServiceLocator());
                    return( array(
                        'error'     => false,
                        'result'    => 'NEWS_PUBLISHED',
                    ));
                }
                else
                {
                    return(array(
                        'error'     => true,
                        'result'    => 'INVALID_DATA_PROVIDED', 
                        'form'      => $form,
                        'newsid'    => $newsid,
                    )); 
                }
            }
            
            if( $news )
                $form->bind( $news );
            
            return( array(
                'form'      => $form,
                'newsid'    => $newsid,
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
    
    public function DeleteNewsAction()
    {
        if( $this->authData && $this->authData->userid && $this->isAllowed( 'PUBLISH_NEWS' ) )
        {
            $newsid = (int)$this->params()->fromRoute( 'param1' );
            $this->getNewsTable()->deleteNews( $newsid );
            KEventManager::trigger('AnnouncementDeleted', array(
                'userid'    => $this->authData->userid,
                'aid'       => $newsid,
                'ip'        => $this->getRequest()->getServer('REMOTE_ADDR'),
            ), $this->getServiceLocator());
            return $this->redirect()->toRoute( 'kpastecore', array( 
                'controller'    => 'Home',
                'action'        => 'Announcements',
            ));
        }
    }
    
    public function getAvailableThemes()
    {
        $dirpath = ROOT_NAME . '/themes';
        $dirh = opendir($dirpath);
        $themes = array();
        while( $dir = readdir($dirh) )
        {
            if($dir != '.' && $dir != '..')
                $themes[$dir] = $dir;
        }
        return $themes;
    }
    
    public function getAvailableLanguages()
    {
        $langsMeta = './module/KpasteCore/language/languages';
        $langsStr = file_get_contents($langsMeta);
        $langsArr = explode('-', $langsStr);
        $retval = array();
        foreach($langsArr as $langArr)
        {
            list($langCode, $langName) = explode('|', $langArr);
            $retval[$langCode] = $langName;
        }
        return $retval;
    }
    
    public function getCampaignsTable()
    {
        if(!$this->campaignsTable)
        {
            $sm = $this->getServiceLocator();
            $this->campaignsTable = $sm->get('Advertiser\Model\CampaignsTable');
        }
        return $this->campaignsTable;
    }
    
    public function getUsersTable() 
    {
        if(!$this->usersTable) 
        {
            $sm = $this->getServiceLocator();
            $this->usersTable = $sm->get('User\Model\UserTable');
        }
        return $this->usersTable;
    }
    
    public function getNewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\NewsTable');
    }
    
    private function getAdsViewsTable() 
    {
        if(!$this->adsViewsTable)
        {
            $sm = $this->getServiceLocator();
            $this->adsViewsTable = $sm->get('Advertiser\Model\AdsViewsTable');
        }
        return $this->adsViewsTable;
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
