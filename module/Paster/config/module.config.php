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
            'Paster\Controller\Paster' => 'Paster\Controller\PasterController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'paster' => array(
                'type' => 'segment', 
                'options' => array(
                    'route' => '/:lang/paster[/:action][/:param1]',
                    'constraints' => array(
                        'lang'          => '[a-z]{2}',
                        'action'        => '[a-zA-Z][a-zA-Z0-9-_]*',
                        'param1'        => '[a-zA-Z0-9-_]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Paster\Controller',
                        'lang'          => $lang,
                        'controller'    => 'Paster\Controller\Paster',
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
            'Paster' => __DIR__ . '/../view',
        ),
    ),
);