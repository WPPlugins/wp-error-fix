<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Error Fix notification sender
 * 
 * @package ErrorFix
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Core_Sender {
    
    /**
     * 
     * @param type $count
     * @return type
     */
    public function sendErrorReport($count) {
        $result = false;
        
        $settings = ErrorFix_Core_Option::getSettings();
        $email    = (empty($settings['email']) ? '' : $settings['email']);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message  = "Error Report:\n";
            $message .= "-------------\n\n";
            $message .= "Website: " . site_url() . "\n";
            $message .= "New Errors: " . $count . "\n\n";
            $message .= "Login to your website backend and go to Error Fix ";
            $message .= "page to find more information about new errors.";
                    
            $result = wp_mail($email, 'WP Error Fix: Error Report', $message);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param type $count
     * @return type
     */
    public function sendFixReport($count) {
        $result = false;
        
        $settings = ErrorFix_Core_Option::getSettings();
        $email    = (empty($settings['email']) ? '' : $settings['email']);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message  = "New fixes available:\n";
            $message .= "-------------\n\n";
            $message .= "Website: " . site_url() . "\n";
            $message .= "New Fixes: " . $count . "\n\n";
            $message .= "Login to your website backend and go to Error Fix ";
            $message .= "page to find more information about available fixes.";
                    
            $result = wp_mail(
                    $email, 'WP Error Fix: New fixes available', $message
            );
        }
        
        return $result;
    }
}