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
            'Support\Controller\Support' => 'Support\Controller\SupportController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'support' => array(
                'type' => 'segment', 
                'options' => array(
                    'route' => '/:lang/support[/:action][/:param1]',
                    'constraints' => array(
                        'lang'          => '[a-z]{2}',
                        'action'        => '[a-zA-Z][a-zA-Z0-9-_]*',
                        'param1'        => '[a-zA-Z0-9-_]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Support\Controller',
                        'lang'          => $lang,
                        'controller'    => 'Support\Controller\Support',
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
            'Support' => __DIR__ . '/../view',
        ),
    ),
);