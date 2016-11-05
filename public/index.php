<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
require './constants.php';

chdir(dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', true);

date_default_timezone_set(TIMEZONE);

// Setup autoloading
require 'init_autoloader.php';

//Load application settings
$container = new Zend\Session\Container('settings');
if(!isset($container->settings) || true) 
{
    $confReader = new Zend\Config\Reader\Json();
    $settings = $confReader->fromFile('config/settings.json');

    //Store the application settings in session
    $container->settings = $settings;
}

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();

