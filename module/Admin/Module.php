<?php
namespace Admin;

use Zend\Mvc\MvcEvent;

class Module {
    
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
            )
        );
    }
}