<?php
$settings = new Zend\Session\Container('settings');
$lang = substr($settings->settings['language'], 0, 2);

return array(
    'controllers' => array(
        'invokables' => array(
            'Admin\Controller\MasterAdmin'      => 'Admin\Controller\MasterAdminController',
            'Admin\Controller\SystemLog'        => 'Admin\Controller\SystemLogController',
            'Admin\Controller\SystemSettings'   => 'Admin\Controller\SystemSettingsController',
            'Admin\Controller\UserManagement'   => 'Admin\Controller\UserManagementController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'admin' => array(
                'type' => 'segment', 
                'options' => array(
                    'route' => '/:lang/admin[/:controller[/:action[/:param1]]]',
                    'constraints' => array(
                        'lang'          => '[a-z]{2}',
                        'controller'    => '[a-zA-Z][a-zA-Z0-9-_]*',
                        'action'        => '[a-zA-Z][a-zA-Z0-9-_]*',
                        'param1'        =>         '[a-zA-Z0-9-_]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Admin\Controller',
                        'lang'          => $lang,
                        'controller'    => 'Admin\Controller\SystemSettings',
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
            'Admin' => __DIR__ . '/../view',
        ),
    ),
);