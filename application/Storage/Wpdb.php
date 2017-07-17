<?php

/**
  Copyright (c) 2016 VASYLTECH.COM

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
 */

/**
 * WordPress Database storage
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Storage_Wpdb {

    /**
     * Load all records
     * 
     * @return array
     * 
     * @access public
     * @static
     */
    public static function load($namespace = 'storage') {
        //First implement fallback to transfer all errors from file. v3.7 and lower
        //TODO - remove in Mar 2017
        $errors = ErrorFix_Core_Option::getOption('errorfix-' . $namespace, array());
        
        if (empty($errors)) {
            $errors = CodePinch_Storage_File::load($namespace);
            CodePinch_Storage_File::clear($namespace);
            if (!empty($errors)) {
                self::save($errors, $namespace);
            }
        }
        
        return is_array($errors) ? $errors : array();
    }
    
    /**
     * Clear error log
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function clear($namespace = 'storage') {
        return ErrorFix_Core_Option::deleteOption('errorfix-' . $namespace);
    }
    
    /**
     * Save errors
     * 
     * @param array $errors
     * 
     * @return boolean
     * 
     * @access public
     * @static
     */
    public static function save(array $errors, $namespace = 'storage') {
        return ErrorFix_Core_Option::updateOption('errorfix-' . $namespace, $errors);
    }
    
}