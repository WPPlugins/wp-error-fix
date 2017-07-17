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
 * CodePinch notes storage
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Note {

    /**
     * Instance of itself
     * 
     * @var CodePinch_Note 
     * 
     * @access protected
     */
    protected static $instance = null;
    
    /**
     * Note list
     * 
     * @var array 
     * 
     * @access protected
     */
    protected static $notes = array();
    
    /**
     * 
     */
    protected function __construct() {
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            self::$notes = call_user_func("$classname::load", 'notes'); //PHP 5.2
        } else { //fallback to default storage method
            self::$notes = CodePinch_Storage_File::load('notes');
        }
    }

    /**
     * 
     */
    public function save() {
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            call_user_func("$classname::save", self::$notes, 'notes'); //PHP 5.2
        } else { //fallback to default storage method
            CodePinch_Storage_File::save(self::$notes, 'notes');
        }
    }

    /**
     * Add new note
     * 
     * @param array $note
     */
    public function addNote(stdClass $note) {
        if (!isset(self::$notes[$note->code])) {
            self::$notes[$note->code] = $note;
        }
    }
    
    /**
     * 
     * @param type $code
     */
    public function removeNote($code) {
        if (isset(self::$notes[$code])) {
            unset(self::$notes[$code]);
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getNotes() {
        return self::$notes;
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