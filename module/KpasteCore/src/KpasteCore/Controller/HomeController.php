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

use Paster\Form\NewPasteForm;
use Paster\Model\Pastes;

use KpasteCore\Cipher\Cipher;

class HomeController extends AbstractActionController
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
        if( ($key = array_search( $permission, $this->acl )) === FALSE )
        {
            return false;
        }
        return true;
    }
    
    public function indexAction()
    {
        $lang = $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2));
        
        if(isset($this->authData->userid) && $this->authData->userid)
        {
            if($this->authData->user_type == 'paster')
            {
                return $this->redirect()->toRoute('paster', array(
                    'lang'      => $lang,
                    'action'    => 'index',
                ));                        
            }
            else if($this->authData->user_type == 'advertiser')
            {
                return $this->redirect()->toRoute('advertiser', array(
                    'lang'      => $lang,
                    'action'    => 'index',
                ));                        
            }
        }
        
        $theme = $this->settings['theme'];
        $form = new NewPasteForm();
        $request = $this->getRequest();
        $pastes = $this->getPastesTable()->fetchPastes(array('exposure' => 'public'), false, 11);
        if($request->isPost())
        {
            $form->setData($request->getPost());
            if($form->isValid())
            {
                $newPaste = new Pastes();
                $newPaste->exchangeArray($request->getPost());
                
                //Encrypt the paste if password is set
                if($request->getPost('password') && strlen($request->getPost('password')))
                {
                    $cipher = new Cipher($request->getPost('password'));
                    $newPaste->password_test = $cipher->encrypt('PASSWORD_IS_OK');
                    $newPaste->paste = $cipher->encrypt($newPaste->paste);
                }
                
                $newPaste->status = 'active';
                $newPaste->pasted_on = date('Y-m-d H:i:s');
                
                $newPasteId = $this->getPastesTable()->savePaste($newPaste);
                
                KEventManager::trigger('NewPasteSubmitted', array(
                    'pasteid'       => $newPasteId,
                    'ip'            => $this->getRequest()->getServer('REMOTE_ADDR'),
                ), $this->getServiceLocator());
                
                return $this->redirect()->toRoute('kpastecore', array(
                    'lang'          => $lang,
                    'controller'    => 'ViewPaste',
                    'action'        => 'view',
                    'param1'        => $newPasteId,
                ));
                
                //redirect to the new paste
                
                $pastes = $this->getPastesTable()->fetchPastes(array('exposure' => 'public'), false, 33);
                /*
                 * 
                 * 
                $form = new NewPasteForm();
                return(array(
                    'error'     => false, 
                    'result'    => 'PASTE_SAVED_SUCCESSFULLY',
                    'newPasteForm'=> $form,
                    'pastes'    => $pastes,
                    'theme'     => $theme,
                ));
                 * 
                 */
            }
            else
            {
                return(array(
                    'error' => true, 
                    'result' => 'INVALID_DATA_PROVIDED',
                    'newPasteForm'=> $form,
                    'pastes'    => $pastes,
                    'theme'     => $theme,
                ));
            }
        }
        
        return array(
            'newPasteForm'      => $form, 
            'pastes'            => $pastes, 
            'theme'             => $theme,
        );
    }
    
    public function AnnouncementsAction()
    {
        $news = $this->getNewsTable()->fetchNews( false, false, true );
        $news->setCurrentPageNumber( $this->params()->fromQuery('page', 1) );
        $news->setItemCountPerPage( $this->params()->fromQuery('count', DEFAULT_ROWS_PER_PAGE) );
        
        if( !$news->count() )      $news = null;
        
        if( $this->isAllowed( 'PUBLISH_NEWS' ) )
            $allowEdit = true;
        else
            $allowEdit = false;

        return( array(
            'news'      => $news,
            'allowEdit' => $allowEdit,
        ) );
    }
    
    public function termsAction()
    {
        $terms = file_get_contents(ROOT_PATH . '/terms');
        return(array('terms'=>$terms));
    }
    
    public function privacyAction()
    {
        $privacy = file_get_contents(ROOT_PATH . '/privacy');
        return(array('privacy'=>$privacy));
    }
    
    public function QuestionsAndAnswersAction()
    {
        $lang = $this->params()->fromRoute('lang');
        
        $qaa = file_get_contents(ROOT_PATH . '/qaa');
        
        $qaa = str_replace('%LEFT_TOP_RIGHT_SQUARE_BUTTON%', 
                $this->settings['price-sqbtn-lrt'] . ' ' . $this->settings['currency'], $qaa);
        $qaa = str_replace('%BOTTOM_SQUARE_BUTTON%', 
                $this->settings['price-sqbtn-b'] . ' ' . $this->settings['currency'], $qaa);
        $qaa = str_replace('%VERTICAL_BANNER%', 
                $this->settings['price-verbnr'] . ' ' . $this->settings['currency'], $qaa);
        $qaa = str_replace('%TOP_LEADERBOARD%', 
                $this->settings['price-leaderboard-t'] . ' ' . $this->settings['currency'], $qaa);
        $qaa = str_replace('%BOTTOM_LEADERBOARD%', 
                $this->settings['price-leaderboard-b'] . ' ' . $this->settings['currency'], $qaa);
        
        $translator = $this->serviceLocator->get('translator');

        $adsLegendUrl = $this->url()->fromRoute('kpastecore', array(
            'lang'      => $lang,
            'controller'=> 'Home',
            'action'    => 'ads',
        ));
        $adsLegend = "<a href='$adsLegendUrl' target='_blank'>" . 
                'راهنمای مکان تبلیغات</a>';
        $qaa = str_replace('%ADS_LEGEND%', $adsLegend, $qaa);
        
        $qaa = str_replace('%PAYMENT%', 
                $this->settings['payment-per-thousand'] . ' ' . $this->settings['currency'], $qaa);
        
        $qaa = str_replace('%MONTHLY_PAYMENT_EXAMPLE%', 
                $this->settings['payment-per-thousand'] * 30 . ' ' . $this->settings['currency'], $qaa);
        
        $qaa = str_replace('%MIN_CHECKOUT%', 
                $this->settings['min-balance-for-checkout'] . ' ' . $this->settings['currency'], $qaa);

        return(array('qaa'=>$qaa));
    }
    
    public function ContactUsAction()
    {
        if($this->authData && $this->authData->userid)
            $loggedin = true;
        else
            $loggedin = false;
        return(array(
            'loggedin'  => $loggedin,
        ));
    }
    
    public function adsAction() 
    {
        return(array());
    }
    
    public function getNewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\NewsTable');
    }
    
    private function getPastesTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PastesTable');
    }
}
