<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

$settings = new Zend\Session\Container('settings');
$lang = substr($settings->settings['language'], 0, 2);

return array(
    'router' => array(
        'routes' => array(
            'kpastecore'=> array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/:lang[/:controller[/:action[/:param1]]]',
                    'constraints' => array(
                        'lang'          => '[a-z]*',
                        'controller'    => '[a-zA-Z0-9_-]*',
                        'action'        => '[a-zA-Z0-9_-]*',
                        'param1'        => '[a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'KpasteCore\Controller',
                        'lang'          => $lang,
                        'controller'    => 'Home',
                        'action'        => 'index',
                    ),
                ),                
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => $settings->settings['language'],
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'KpasteCore\Controller\Home'            => 'KpasteCore\Controller\HomeController',
            'KpasteCore\Controller\ViewPaste'       => 'KpasteCore\Controller\ViewPasteController',
            'KpasteCore\Controller\Ajax'            => 'KpasteCore\Controller\AjaxController',
            'KpasteCore\Controller\RSS'             => 'KpasteCore\Controller\RSSController',
            'KpasteCore\Controller\EPayment'        => 'KpasteCore\Controller\EPaymentController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason'  => true,
        'display_exceptions'        => true,
        'doctype'                   => 'HTML5',
        'not_found_template'        => 'error/404',
        'exception_template'        => 'error/index',
        'template_map'              => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'kpaste-core/index/index' => __DIR__ . '/../view/kpaste-core/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view/',
        ),
    ),
);
