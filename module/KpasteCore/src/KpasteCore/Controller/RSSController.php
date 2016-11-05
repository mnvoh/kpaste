<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    RSSController.php
 * @createdat   Oct 7, 2013 12:20:53 PM
 */

namespace KpasteCore\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container as SessionContainer;

class RSSController extends AbstractActionController
{
    protected $authData;
    protected $settings;
    protected $rssHeader;
    protected $items;
    
    public function __construct()
    {
        $auth           = new AuthenticationService();
        $this->authData = @unserialize($auth->getStorage()->read());
        $settings       = new SessionContainer( 'settings' );
        $this->settings = $settings->settings;
        $lang = str_replace('_', '-', $this->settings['language']);
        $this->rssHeader = <<<RSSHEADER
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

    <channel>
        <title>%TITLE%</title>
        <link>%LINK%</link>
        <description>%DESCRIPTION%</description>
        <language>$lang</language>
RSSHEADER;
        
        $this->items = "";
    }

    private function initialize($title, $link, $description)
    {
        $this->rssHeader = str_replace('%TITLE%', $title, $this->rssHeader);
        $this->rssHeader = str_replace('%LINK%', $link, $this->rssHeader);
        $this->rssHeader = str_replace('%DESCRIPTION%', $description, $this->rssHeader);
    }
    
    private function addItem($title, $link, $description)
    {
        $newItem = <<<NEWITEM
        <item>
            <title>%TITLE%</title>
            <link>%LINK%</link>
            <description>%DESCRIPTION%</description>
        </item>

NEWITEM;
        $newItem = str_replace('%TITLE%', $title, $newItem);
        $newItem = str_replace('%LINK%', $link, $newItem);
        $newItem = str_replace('%DESCRIPTION%', $description, $newItem);
        
        $this->items .= "\n";
        $this->items .= $newItem;
    }
    
    private function finalize()
    {
        $tail = "</channel>\n</rss>";
        $response = $this->getResponse();
        
        $headers = new \Zend\Http\Headers();
        $headers->addHeaderLine('Content-Type', "application/rss+xml; charset=UTF-8");

        $response->setHeaders($headers);
        
        $response->setContent($this->rssHeader . $this->items . $tail);
        
        return $response;
    }
    
    public function AnnouncementsAction()
    {
        $link = $this->url()->fromRoute('kpastecore', array(
            'lang'      => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
            'controller'=> 'Home',
            'action'    => 'Announcement',
        ), array('force_canonical' => true));
        $translator = $this->getServiceLocator()->get('translator');
        
        $this->initialize($translator->translate("kPaste's Announcements"), 
                $link, $translator->translate("kPaste's Announcements"));
        
        $news = $this->getNewsTable()->fetchNews(50);
        
        foreach($news as $_news)
        {
            $this->addItem($_news->title, $link, $_news->news);
        }
        
        return($this->finalize());
    }
    
    public function LatestPublicPastesAction()
    {
        $userid = (int)$this->params()->fromRoute('param1');
        
        if($userid)
        {
            $pastes = $this->getPastesTable()->fetchPastes(array(
                'exposure'      => 'public',
                'users.userid'  => $userid,
            ), false, 50);
            
            $user = $this->getUsersTable()->fetchUser('userid', $userid);
            
            $link = $this->url()->fromRoute('kpastecore', array(
                'lang'      => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
            ), array('force_canonical' => true));
            $translator = $this->getServiceLocator()->get('translator');

           
            $title = sprintf($translator->translate("%s's Latest Public Pastes"), $user->username);
            $this->initialize($title, $link, $title);
        }
        else
        {
            $pastes = $this->getPastesTable()->fetchPastes(array('exposure' => 'public'), false, 50);
            $link = $this->url()->fromRoute('kpastecore', array(
                'lang'      => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
            ), array('force_canonical' => true));
            $translator = $this->getServiceLocator()->get('translator');

            $this->initialize($translator->translate("Latest Public Pastes"), 
                    $link, $translator->translate("Latest Public Pastes"));
        }
                
        foreach($pastes as $paste)
        {
            $link = $this->url()->fromRoute('kpastecore', array(
                'lang'      => $this->params()->fromRoute('lang', substr($this->settings['language'], 0, 2)),
                'controller'=> 'ViewPaste',
                'action'    => 'view',
                'param1'    => $paste->pasteid,
            ), array('force_canonical' => true));
            if($paste->password_test && strlen($paste->password_test))
            {
                $pasteDigest = '(((PASSWORD PROTECTED)))';
            }
            else
            {
                $pasteDigest = mb_substr($paste->paste, 0, 256);
                if(strlen($paste->paste) > 255) $pasteDigest .= '...';
            }
            $pasteDigest = str_replace('<', '&lt;', $pasteDigest);
            $this->addItem($paste->title, $link, $pasteDigest);
        }
        
        return($this->finalize());
    }
    
    public function getPastesTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('Paster\Model\PastesTable');
    }
    public function getNewsTable()
    {
        $sm = $this->getServiceLocator();
        return $sm->get('KpasteCore\Model\NewsTable');
    }
    public function getUsersTable() 
    {
        $sm = $this->getServiceLocator();
        return $sm->get('User\Model\UserTable');
    }
}
