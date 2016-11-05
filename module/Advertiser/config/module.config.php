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
    'controllers' => array(
        'invokables' => array(
            'Advertiser\Controller\Advertiser' => 'Advertiser\Controller\AdvertiserController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'advertiser' => array(
                'type' => 'segment', 
                'options' => array(
                    'route' => '/:lang/advertiser[/:action][/:param1]',
                    'constraints' => array(
                        'lang'          => '[a-z]{2}',
                        'action'        => '[a-zA-Z][a-zA-Z0-9-_]*',
                        'param1'        => '[a-zA-Z0-9-_]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Advertiser\Controller',
                        'lang'          => $lang,
                        'controller'    => 'Advertiser\Controller\Advertiser',
                        'action'        => 'index',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
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
    'view_manager' => array(
        'template_path_stack' => array(
            'Advertiser' => __DIR__ . '/../view',
        ),
    ),
);