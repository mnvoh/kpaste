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
            'User\Controller\User' => 'User\Controller\UserController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'user' => array(
                'type' => 'segment', 
                'options' => array(
                    'route' => '/:lang/user[/:action[/:request_confirmation_code]][/continue/:continue]',
                    'constraints' => array(
                        'lang'          => '[a-z]{2}',
                        'action'        => '[a-zA-Z][a-zA-Z0-9-_]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'User\Controller',
                        'lang'          => $lang,
                        'controller'    => 'User\Controller\User',
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
            'User' => __DIR__ . '/../view',
        ),
    ),
);