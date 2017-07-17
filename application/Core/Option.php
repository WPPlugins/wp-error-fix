<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ErrorFix core option
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
final class ErrorFix_Core_Option {

    /**
     * Error Fix unique Id
     */
    const ID = 'errorfix_id';

    /**
     * Error Fix balance
     */
    const BALANCE = 'errorfix_balance';

    /**
     * 
     */
    const VIP = 'errorfix_vip';
    
    /**
     * Error Fix WP Settings
     * 
     * @since 3.3
     */
    const SETTINGS = 'errorfix_settings';
    
    /**
     *
     * @var type 
     */
    protected static $cache = array();

    /**
     * 
     * @return type
     */
    public static function getId() {
        return self::getOption(self::ID, null);
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function setId($id) {
        return self::updateOption(self::ID, $id);
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function updateId($id) {
        return self::updateOption(self::ID, $id);
    }

    /**
     * 
     * @return type
     */
    public static function getBalance() {
        return self::getOption(self::BALANCE, 0);
    }

    /**
     * 
     * @param type $balance
     * @return type
     */
    public static function updateBalance($balance) {
        return self::updateOption(self::BALANCE, $balance);
    }

    /**
     * 
     * @return type
     */
    public static function getVip() {
        return self::getOption(self::VIP, 0);
    }

    /**
     * 
     * @param type $vip
     * @return type
     */
    public static function updateVip($vip) {
        return self::updateOption(self::VIP, $vip);
    }

    /**
     * 
     * @return type
     */
    public static function deleteVip() {
        return self::deleteOption(self::VIP);
    }
    
    /**
     * 
     * @return type
     */
    public static function getSettings($option = null, $default = null) {
        $settings = self::getOption(self::SETTINGS, array());
        
        if ($option) {
            $response = (isset($settings[$option]) ? $settings[$option] : $default);
        } else {
            $response = $settings;
        }
        
        return $response;
    }
    
    /**
     * 
     * @param array $settings
     * @return type
     */
    public static function updateSettings(array $settings) {
        return self::updateOption(self::SETTINGS, $settings);
    }
    
    /**
     * 
     * @return type
     */
    public static function deleteSettings() {
        return self::deleteOption(self::SETTINGS);
    }

    /**
     * Get option
     * 
     * @param type $option
     * @param type $default
     * @return type
     */
    public static function getOption($option, $default = null) {
        if (!isset(self::$cache[$option])) {
            if (is_multisite()) {
                $id = (defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : 1);
                self::$cache[$option] = get_blog_option($id, $option, $default);
            } else {
                self::$cache[$option] = get_option($option, $default);
            }
        }

        return self::$cache[$option];
    }

    /**
     * Delete option
     * 
     * @param type $option
     * @return type
     */
    public static function deleteOption($option) {
        if (is_multisite()) {
            $id     = (defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : 1);
            $result = delete_blog_option($id, $option);
        } else {
            $result = delete_option($option);
        }
        
        return $result;
    }

    /**
     * Update option
     * 
     * @param type $option
     * @param type $value
     */
    public static function updateOption($option, $value) {
        self::$cache[$option] = $value;
        
        if (is_multisite()) {
            $id     = (defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : 1);
            $result = update_blog_option($id, $option, $value);
        } else {
            $result = update_option($option, $value);
        }
        
        return $result;
    }

}