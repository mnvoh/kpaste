<?php
namespace Advertiser;

use Advertiser\Model\Campaigns;
use Advertiser\Model\CampaignsTable;
use Advertiser\Model\AdsViews;
use Advertiser\Model\AdsViewsTable;
use Advertiser\Model\Transactions;
use Advertiser\Model\TransactionsTable;

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
                'Advertiser\Model\CampaignsTable' => function($sm) {
                    $tableGateway = $sm->get('CampaignsTableGateway');
                    $table = new CampaignsTable($tableGateway);
                    return $table;
                },
                'CampaignsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Campaigns());
                    return new TableGateway('campaigns', $adapter, null, $resultSetPrototype);
                },
                'Advertiser\Model\AdsViewsTable' => function($sm) {
                    $tableGateway = $sm->get('AdsViewsTableGateway');
                    $table = new AdsViewsTable($tableGateway);
                    return $table;
                },
                'AdsViewsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new AdsViews());
                    return new TableGateway('ads_views', $adapter, null, $resultSetPrototype);
                },
                'Advertiser\Model\TransactionsTable' => function($sm) {
                    $tableGateway = $sm->get('TransactionsTableGateway');
                    $table = new TransactionsTable($tableGateway);
                    return $table;
                },
                'TransactionsTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Transactions());
                    return new TableGateway('transactions', $adapter, null, $resultSetPrototype);
                },       
            )
        );
    }
}