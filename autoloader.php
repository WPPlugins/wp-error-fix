<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Project autloader
 * 
 * @package ErrorFix
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Autoloader {

    /**
     *
     * @var type 
     */
    protected static $classmap = array();

    /**
     * 
     * @param type $classname
     * @param type $filepath
     */
    public static function add($classname, $filepath) {
        self::$classmap[$classname] = $filepath;
    }

    /**
     * Autoloader for project WP Error Fix
     *
     * Try to load a class if prefix is ErrorFix_
     *
     * @param string $classname
     */
    public static function load($classname) {
        if (array_key_exists($classname, self::$classmap)) {
            $filename = self::$classmap[$classname];
        } else {
            $chunks = explode('_', $classname);
            $prefix = array_shift($chunks);

            if ($prefix === 'ErrorFix') {
                $base_path = dirname(__FILE__) . '/application';
                $filename = $base_path . '/' . implode('/', $chunks) . '.php';
            }
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * Register autoloader
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        spl_autoload_register('ErrorFix_Autoloader::load');
    }

}