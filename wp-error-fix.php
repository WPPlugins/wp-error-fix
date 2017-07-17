<?php

/**
  Plugin Name: CodePinch
  Description: Patent-pending technology that provides solutions to PHP errors within hours, preventing costly maintenance time and keeping your WordPress site error-free.
  Version: 4.2.4
  Author: Vasyl Martyniuk <vasyl@vasyltech.com>
  Author URI: https://vasyltech.com

  -------
  LICENSE: This file is subject to the terms and conditions defined in
  file 'license.txt', which is part of this source package.
 *
 */

/**
 * Main plugin's class
 * 
 * @package ErrorFix
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix {

    /**
     * Single instance of itself
     *
     * @var ErrorFix
     *
     * @access private
     */
    private static $_instance = null;

    /**
     * Initialize the ErrorFix Object
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct() {
        if (is_admin()) { //bootstrap the backend interface if necessary
            ErrorFix_Backend_Manager::bootstrap();
        } elseif (filter_input(INPUT_GET, 'errorfix-connect')) {
            CodePinch_Connect::factory(filter_input(INPUT_GET, 'action'))->process();
        }
    }

    /**
     * Make sure that ErrorFix UI Page is used
     *
     * @return boolean
     *
     * @access public
     */
    public static function isErrorFix() {
        $page   = filter_input(INPUT_GET, 'page');
        $action = filter_input(INPUT_POST, 'action');
        
        return (is_admin() && in_array('errorfix', array($page, $action)));
    }
    
    /**
     * Initialize the ErrorFix plugin
     *
     * @return ErrorFix
     *
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            load_plugin_textdomain(
                ERRORFIX_KEY, 
                false, 
                dirname(plugin_basename(__FILE__)) . '/lang/'
            );
            self::$_instance = new self;
        }

        return self::$_instance;
    }
    
    /**
     * Execute hourly routine
     * 
     * @return void
     * 
     * @access public
     */
    public static function cron() {
        if (ErrorFix_Core_Option::getId()) {
            $routine = new CodePinch_Routine;
            $routine->execute();
        }
    }
    
    /**
     * Register API endpoint
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function registerAPI() {
        register_rest_route('codepinch/v1', '/connect', array(
            'methods'  => 'GET',
            'callback' => array('ErrorFix', 'api')
        ));
    }
    
    /**
     * 
     * @param WP_REST_Request $request
     * 
     * @return void
     */
    public static function api(WP_REST_Request $request) {
        CodePinch_Connect::factory($request->get_param('action'))->process();
    }
    
    /**
     * Send user email notifications
     * 
     * If configured, send twice a day an email notification to the user when
     * new errors occur or new fixes are available for download
     * 
     * @return void
     * 
     * @access public
     */
    public static function notify() {
        //get number of new errors
        $errors = 0;
        foreach(CodePinch_Storage::getInstance()->getErrors() as $error) {
            $errors += (empty($error->notified) ? 1 : 0);
            $error->notified = true;
        }
        CodePinch_Storage::getInstance()->save();
        
        //get number of available fixes
        $fixes = count(CodePinch_Storage::getInstance()->getPatchList());
        
        $sender = new ErrorFix_Core_Sender;
        if ($errors) {
            $sender->sendErrorReport($errors);
        }
        
        if ($fixes) {
            $sender->sendFixReport($fixes);
        }
    }

    /**
     * Activate plugin
     * 
     * @return void
     * 
     * @access public
     */
    public static function activate() {
        global $wp_version;
        
        //check PHP Version
        if (version_compare(PHP_VERSION, '5.2') == -1) {
            exit(__('PHP 5.2 or higher is required.', ERRORFIX_KEY));
        } elseif (version_compare($wp_version, '3.8') == -1) {
            exit(__('WP 3.8 or higher is required.', ERRORFIX_KEY));
        }
        
        //write codepinch boostrap to wp-config.php
        $filename = ABSPATH . 'wp-config.php';

        if (is_readable($filename) && is_writable($filename)) {
            $content = file_get_contents($filename);
            
            if ($content && !strpos($content, 'CodePinch')) {
                $script  = "/* CodePinch: begin */\n";
                $script .= "if (file_exists('" . CODEPINCH_BOOTSTRAP . "')) {\n";
                $script .= "    require_once '" . CODEPINCH_BOOTSTRAP . "';\n";
                $script .= "}\n/* CodePinch: end */\n";
                $script .= "/* That's all, stop editing! Happy blogging. */";

                file_put_contents(
                    $filename, 
                    str_replace(
                        "/* That's all, stop editing! Happy blogging. */", 
                        $script, 
                        $content
                    )
                );
            }
        }
    }

    /**
     * Uninstall hook
     *
     * Remove all leftovers from ErrorFix execution
     *
     * @return void
     *
     * @access public
     */
    public static function uninstall() {
        //delete options but not all in case customer will want to re-install
        ErrorFix_Core_Option::deleteSettings();
        
        //clear schedules
        wp_clear_scheduled_hook('errorfix-cron');
        wp_clear_scheduled_hook('errorfix-notification');
        
        //remove codepinch boostrap from wp-config.php
        $filename = ABSPATH . 'wp-config.php';

        if (is_readable($filename) && is_writable($filename)) {
            $content = file_get_contents($filename);
            
            if ($content) {
                file_put_contents(
                    $filename, 
                    preg_replace(
                        '/\/\* CodePinch: begin \*\/.*\/\* CodePinch: end \*\//si', 
                        '', 
                        $content
                    )
                );
            }
        }
    }

}

if (defined('ABSPATH')) {
    require (dirname(__FILE__) . '/bootstrap.php');
    
    //activation & deactivation hooks
    register_activation_hook(__FILE__, array('ErrorFix', 'activate'));
    register_uninstall_hook(__FILE__, array('ErrorFix', 'uninstall'));
}