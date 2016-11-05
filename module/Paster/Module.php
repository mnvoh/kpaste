<?php
namespace Paster;

use Paster\Model\Pastes;
use Paster\Model\PastesTable;
use Paster\Model\PasteViews;
use Paster\Model\PasteViewsTable;
use Paster\Model\Checkouts;
use Paster\Model\CheckoutsTable;
use Paster\Model\ReportedPastesTable;
use Paster\Model\ThumbsTable;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;

class Module 
{
//    public function onBootstrap(MvcEvent $e)
//    {
//        $eventManager           = $e->getApplication()->getEventManager();
//        
//        $routeCallback = function ($e) {
//            $availableLanguages = array(
//                'fa' => 'fa_IR', 
//                'en' => 'en_US'
//            );
//            $settings = new \Zend\Session\Container('settings');
//            $defaultLanguage = $settings->settings['language'];
//            $language = $defaultLanguage;
//            //see if language could be find in url
//            if ($e->getRouteMatch()->getParam('lang')) {
//                $language = $availableLanguages[$e->getRouteMatch()->getParam('lang')];
//            }
//            $e->getApplication()->getServiceManager()->get('translator')->setLocale($language);
//        };
//
//        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, $routeCallback);
//    }
    
    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . "/src/" . __NAMESPACE__,
                ),
            ),
        );
    }
    
    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function getServiceConfig() {
        return array(
            'factories' => array(
                'Paster\Model\PastesTable' => function($sm) {
                    $tableGateway = $sm->get('PastesTableGateway');
                    $table = new PastesTable($tableGateway);
                    return $table;
                },
                'PastesTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Pastes());
                    return new TableGateway('pastes', $adapter, null, $resultSetPrototype);
                },
                'Paster\Model\PasteViewsTable' => function($sm) {
                    $tableGateway = $sm->get('PasteViewsTableGateway');
                    $table = new PasteViewsTable($tableGateway);
                    return $table;
                },
                'PasteViewsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new PasteViews());
                    return new TableGateway('paste_views', $adapter, null, $resultSetPrototype);
                },
                'Paster\Model\CheckoutsTable' => function($sm) {
                    $tableGateway = $sm->get('CheckoutsTableGateway');
                    $table = new CheckoutsTable($tableGateway);
                    return $table;
                },
                'CheckoutsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Checkouts());
                    return new TableGateway('checkouts', $adapter, null, $resultSetPrototype);
                },
                'Paster\Model\ReportedPastesTable' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ReportedPastesTable($adapter);
                    return $table;
                },
                'Paster\Model\ThumbsTable' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ThumbsTable($adapter);
                    return $table;
                },
            )
        );
    }
}