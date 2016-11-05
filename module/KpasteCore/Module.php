<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace KpasteCore;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use KpasteCore\Model\Ip2Country;
use KpasteCore\Model\Ip2CountryTable;
use KpasteCore\Model\PermissionsTable;
use KpasteCore\Model\SystemActivitiesTable;
use KpasteCore\Model\News;
use KpasteCore\Model\NewsTable;
use KpasteCore\Model\Coupons;
use KpasteCore\Model\CouponsTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager           = $e->getApplication()->getEventManager();
        $moduleRouteListener    = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $routeCallback = function ($e) {
            $viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
            $availableLanguages = array(
                'fa' => 'fa_IR', 
                'en' => 'en_US'
            );
            $direction = array(
                'fa_IR'     => 'rtl',
                'en_US'     => 'ltr',
            );
            $settings = new \Zend\Session\Container('settings');
            $defaultLanguage = $settings->settings['language'];
            $language = $defaultLanguage;
            //see if language could be find in url
            if($e->getRouteMatch()->getParam('lang')) {
                if(isset($availableLanguages[$e->getRouteMatch()->getParam('lang')]))
                {
                    $language = $availableLanguages[$e->getRouteMatch()->getParam('lang')];
                }
                else
                {
                    $response=$e->getResponse();
                    $response->getHeaders()->addHeaderLine('Location', '/' . 
                            substr($defaultLanguage, 0, 2) . '/NOTFOUND');
                    $response->setStatusCode(302);
                    $response->sendHeaders();
                    //  When an MvcEvent Listener returns a Response object,
                    // It automatically short-circuit the Application running
                    return $response;
                }
            }
            $e->getApplication()->getServiceManager()->get('translator')->setLocale($language);
            $viewModel->language = $language;
            $viewModel->direction = $direction[$language];
            $viewModel->routename = $e->getRouteMatch()->getMatchedRouteName();
            $viewModel->currentAction = $e->getRouteMatch()->getParam('action');
            $viewModel->currentController = $e->getRouteMatch()->getParam('controller');
            $viewModel->currentController = substr($viewModel->currentController, 
                strrpos($viewModel->currentController, '\\') + 1);
            $viewModel->currentParam1 = $e->getRouteMatch()->getParam('param1');
            
            $translator = new \Zend\Mvc\I18n\Translator();
            $translator->setLocale($language);
            $translator->addTranslationFile(
                'phpArray', 
                'vendor/zendframework/zendframework/resources/languages/fa_IR/Zend_Validate.php',
                'default',
                'fa_IR'
            );
            \Zend\Validator\AbstractValidator::setDefaultTranslator($translator);
        };
        
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, $routeCallback);
        
        $viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
        $news = $e->getApplication()->getServiceManager()->get('KpasteCore\Model\NewsTable');
        $latestNews = $news->fetchNews(1)->current();
        $viewModel->latestNews = $latestNews;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    
    public function getServiceConfig() 
    {
        return array(
            'factories' => array(
                'KpasteCore\Model\Ip2CountryTable' => function($sm) {
                    $tableGateway = $sm->get('Ip2CountryTableGateway');
                    $table = new Ip2CountryTable($tableGateway);
                    return $table;
                },
                'Ip2CountryTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Ip2Country());
                    return new TableGateway('ip2country', $adapter, null, $resultSetPrototype);
                },
                'KpasteCore\Model\NewsTable' => function($sm) {
                    $tableGateway = $sm->get('NewsTableGateway');
                    $table = new NewsTable($tableGateway);
                    return $table;
                },
                'NewsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new News());
                    return new TableGateway('news', $adapter, null, $resultSetPrototype);
                },
                'KpasteCore\Model\PermissionsTable' => function($sm) {
                    $adapter    = $sm->get('Zend\Db\Adapter\Adapter');
                    $table      = new PermissionsTable($adapter);
                    return $table;
                },
                'KpasteCore\Model\SystemActivitiesTable' => function($sm) {
                    $adapter    = $sm->get('Zend\Db\Adapter\Adapter');
                    $table      = new SystemActivitiesTable($adapter);
                    return $table;
                },
                        
                'KpasteCore\Model\CouponsTable' => function($sm) {
                    $tableGateway = $sm->get('CouponsTableGateway');
                    $table = new CouponsTable($tableGateway);
                    return $table;
                },
                'CouponsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Coupons());
                    return new TableGateway('coupons', $adapter, null, $resultSetPrototype);
                },
            )
        );
    }
}
