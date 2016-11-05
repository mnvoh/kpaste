<?php
namespace Support;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;

use Support\Model\SupportTickets;
use Support\Model\SupportTicketsTable;
use Support\Model\TicketMessagesTable;
use Support\Model\TicketMessages;
use Support\Model\DepartmentsTable;
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
                'Support\Model\SupportTicketsTable' => function($sm) {
                    $tableGateway = $sm->get('SupportTicketsTableGateway');
                    $table = new SupportTicketsTable( $tableGateway );
                    return $table;
                },
                'SupportTicketsTableGateway' => function($sm) {
                    $adapter = $sm->get( 'Zend\Db\Adapter\Adapter' );
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new SupportTickets());
                    return new TableGateway('support_tickets', $adapter, null, $resultSetPrototype);
                },
                'Support\Model\TicketMessagesTable' => function($sm) {
                    $tableGateway = $sm->get('TicketMessagesTableGateway');
                    $table = new TicketMessagesTable( $tableGateway );
                    return $table;
                },
                'TicketMessagesTableGateway' => function($sm) {
                    $adapter = $sm->get( 'Zend\Db\Adapter\Adapter' );
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new TicketMessages());
                    return new TableGateway('ticket_messages', $adapter, null, $resultSetPrototype);
                },
                'Support\Model\DepartmentsTable' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new DepartmentsTable( $adapter );
                    return $table;
                },
            )
        );
    }
}