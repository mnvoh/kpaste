<?php
namespace User;

use User\Model\User;
use User\Model\UserTable;
use User\Model\PasswordChangeRequests;
use User\Model\PasswordChangeRequestsTable;
use User\Model\SuspendedUsersTable;

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
                'User\Model\UserTable' => function($sm) {
                    $tableGateway = $sm->get('UserTableGateway');
                    $table = new UserTable($tableGateway);
                    return $table;
                },
                'UserTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new User());
                    return new TableGateway('users', $adapter, null, $resultSetPrototype);
                },
                'User\Model\PasswordChangeRequestTable' => function($sm) {
                    $tableGateway = $sm->get('PasswordChangeRequestTableGateway');
                    $table = new PasswordChangeRequestsTable($tableGateway);
                    return $table;
                },
                'PasswordChangeRequestTableGateway' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new PasswordChangeRequests());
                    return new TableGateway('password_change_requests', $adapter, null, $resultSetPrototype);
                },
                'User\Model\SuspendedUsersTable' => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new SuspendedUsersTable($adapter);
                    return $table;
                },
            )
        );
    }
}