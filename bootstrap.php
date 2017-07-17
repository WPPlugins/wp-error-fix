<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

if (defined('ABSPATH')) {
    //define few common constants
    define('ERRORFIX_MEDIA', plugins_url('/media', __FILE__));
    define('ERRORFIX_KEY', 'wp-error-fix');
    define('CODEPINCH_BOOTSTRAP', dirname(__FILE__) . '/codepinch/bootstrap.php');
    
    //register autoloader
    require (dirname(__FILE__) . '/autoloader.php');
    ErrorFix_Autoloader::register();
    
    //the lowest priority
    add_action('init', 'ErrorFix::getInstance', -99);
    
    //bootstrap the ErrorFix framework
    require_once CODEPINCH_BOOTSTRAP;
    
    //set custom settings
    
    $exclude = ErrorFix_Core_Option::getSettings('exclude');
    $balance = ErrorFix_Core_Option::getBalance();
    $vip     = ErrorFix_Core_Option::getVip();
    
    CodePinch_Core::set('decorator', 'ErrorFix_Decorator_WordPress');
    CodePinch_Core::set('connector', 'ErrorFix_Connector_WordPress');
    CodePinch_Core::set('reportLimit', 40);
    CodePinch_Core::set('checkLimit', 20);
    CodePinch_Core::set('autofix', ($vip ? true : false));
    CodePinch_Core::set('version', '4.2.4');
    CodePinch_Core::set('balance', ($vip ? -1 : $balance));
    CodePinch_Core::set('instance', ErrorFix_Core_Option::getId());
    CodePinch_Core::set('siteurl', site_url());
    
    if ($exclude) {
        CodePinch_Core::set('exclude', array_map('trim', explode("\n", $exclude)));
    }
    
    CodePinch_Storage::getInstance()->setStrategy('ErrorFix_Storage_Wpdb');
    
    //schedule cron
    if (!wp_next_scheduled('errorfix-cron')) {
        wp_schedule_event(time(), 'hourly', 'errorfix-cron');
    }
    add_action('errorfix-cron', 'ErrorFix::cron');
    
    //schedule notification cron
    if (!wp_next_scheduled('errorfix-notification')) {
        wp_schedule_event(time(), 'twicedaily', 'errorfix-notification');
    }
    add_action('errorfix-notification', 'ErrorFix::notify');
    
    //register API endpoint
    add_action('rest_api_init', 'ErrorFix::registerAPI');
}