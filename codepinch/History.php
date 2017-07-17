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
 * CodePinch history storage
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_History {

    /**
     * Instance of itself
     * 
     * @var CodePinch_History 
     * 
     * @access protected
     */
    protected static $instance = null;
    
    /**
     * Error list
     * 
     * @var array 
     * 
     * @access protected
     */
    protected static $errors = array();
    
    /**
     * 
     */
    protected function __construct() {
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            self::$errors = call_user_func("$classname::load", 'history'); //PHP 5.2
        } else { //fallback to default storage method
            self::$errors = CodePinch_Storage_File::load('history');
        }
    }

    /**
     * 
     */
    public function save() {
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            call_user_func("$classname::save", self::$errors, 'history'); //PHP 5.2
        } else { //fallback to default storage method
            CodePinch_Storage_File::save(self::$errors, 'history');
        }
    }

    /**
     * Add error
     * 
     * Add fixed error to the history.
     * 
     * @param array $error
     */
    public function addError(stdClass $error) {
        if (!isset(self::$errors[$error->hash])) {
            if (count(self::$errors) >= CodePinch_Core::get('storageLimit')) {
                array_shift(self::$errors);
            }
            self::$errors[$error->hash] = $error;
            self::$errors[$error->hash]->resolved = time();
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getErrors() {
        return self::$errors;
    }
    
    /**
     * 
     * @return CodePinch_History
     * 
     * @throws Exception
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }

}