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
 * CodePinch storage
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Storage {

    /**
     * Single instance of itself
     * 
     * @var CodePinch_Storage 
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
    protected $errors = array();
    
    /**
     *
     * @var type 
     */
    protected $decorator = null;

    /**
     *
     * @var type 
     */
    protected $strategy = 'CodePinch_Storage_File';
    
    /**
     * Initialize the storage
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {}

    /**
     * Save storage
     */
    public function save() {
        call_user_func("{$this->strategy}::save", $this->errors); //PHP 5.2
    }
    
    /**
     * 
     */
    public function reset() {
        $this->errors = array();
        $this->save();
    }
    
    /**
     * 
     * @param type $strategy
     */
    public function setStrategy($strategy) {
        //load existing errors first
        if (empty($this->errors)) {
            $this->errors = call_user_func("{$this->strategy}::load"); //PHP 5.2
        }
        
        //clear existing error log
        call_user_func("{$this->strategy}::clear"); //PHP 5.2
        
        //load new strategy errors
        $errors = call_user_func("{$strategy}::load"); //PHP 5.2
        
        //merge existing errors in new strategy with just captured
        foreach($this->errors as $hash => $error) {
            if (isset($errors[$hash])) {
                $errors[$hash]->hits += $error->hits;
                $errors[$hash]->time  = $error->time;
            } else {
                $errors[$hash] = $error;
            }
        }
        $this->errors = $errors;
        
        //set new storage strategy
        $this->strategy = $strategy;
        
        //set new storage to config
        CodePinch_Core::set('storage', $strategy);
    }
    
    /**
     * 
     * @param array $error
     */
    public function addError(array $error) {
        if (!$this->isAutoskip($error['message'])) { 
            $line  = $error['type'] . $error['filepath'];
            $line .= $error['line'] . $error['message'];

            $hash = md5($line);

            if (isset($this->errors[$hash])) {
                $this->errors[$hash]->hits++;
                $this->errors[$hash]->time = $error['time'];
            } else {
                if (count($this->errors) >= CodePinch_Core::get('storageLimit')) {
                    array_shift($this->errors);
                }
                $this->errors[$hash] = (object) $error;
                $this->errors[$hash]->status = 'new';
                $this->errors[$hash]->hash = $hash;
                $this->errors[$hash]->hits = 1;
            }
        }
    }
    
    /**
     * 
     * @param type $message
     * @return boolean
     */
    protected function isAutoskip($message) {
        $autoskip = false;
        
        foreach(CodePinch_Core::get('autoskip') as $regexp) {
            if (preg_match($regexp, $message)) {
                $autoskip = true;
                break;
            }
        }
        
        return $autoskip;
    }

    /**
     * 
     * @param type $hash
     */
    public function removeError($hash) {
        if (isset($this->errors[$hash])) {
            unset($this->errors[$hash]);
        }
    }

    /**
     * 
     */
    public function normalize() {
        foreach ($this->errors as $hash => $error) {
            if ($this->checksumMatch($error)) {
                $this->removeError($hash);
                continue;
            }

            //decorate any new error
            if ($error->status == 'new') {
                if ($this->getDecorator()->decorate($error) === false) {
                    $this->removeError($error->hash);
                    continue;
                }
            } else {
                $error->message = $this->getDecorator()->filterMessage($error->message);
                $module = $this->getDecorator()->getModule($error->filepath);
                if ($module['name'] != $error->module['name'] 
                        || $module['version'] != $error->module['version']) {
                    $this->removeError($error->hash);
                    continue;
                }
            }
            
            //check if error is not within the excluded list of directories
            if ($this->withinExcluded($error->relpath)) {
                $this->removeError($error->hash);
            }
        }
    }
    
    /**
     * 
     * @param type $error
     * @return type
     */
    protected function checksumMatch($error) {
        return CodePinch_File::getChecksum($error->filepath) != $error->checksum;
    }
    
    /**
     * 
     * @param type $path
     * @return boolean
     */
    protected function withinExcluded($path) {
        $within = false;
        
        $exclude = CodePinch_Core::get('exclude', array());
        if (is_array($exclude)) {
            foreach($exclude as $line) {
                if (!empty($line) && strpos($path, $line) === 0) {
                    $within = true;
                    break;
                }
            }
        }
        
        return $within;
    }

    /**
     * 
     * @return type
     */
    public function getErrors($all = false) {
        $this->normalize();
        
        if ($all) {
            $errors = $this->errors;
        } else {
            $errors = array();
            foreach ($this->errors as $error) {
                if ($error->status != 'rejected') {
                    $errors[$error->hash] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * 
     * @return type
     */
    public function getPatchList() {
        $patches = array();
        
        foreach ($this->getErrors() as $error) {
            if ($error->status == 'resolved') {
                if (isset($patches[$error->patch['id']])) {
                    $patches[$error->patch['id']]['errors']++;
                } else {
                    $patches[$error->patch['id']] = array_merge(
                            $error->patch, 
                            array(
                                'filepath' => $error->filepath,
                                'relpath'  => $error->relpath,
                                'checksum' => $error->checksum,
                                'errors'   => 1,
                                'error'    => $error
                            )
                    );
                }
            }
        }

        return $patches;
    }
    
    /**
     * 
     * @return type
     */
    public function getDecorator() {
       if (is_null($this->decorator)) {
            $classname = CodePinch_Core::get('decorator', 'CodePinch_Decorator_None');
            $this->decorator = new $classname;
        } 
        
        return $this->decorator;
    }

    /**
     * 
     * @param type $hash
     * @return type
     */
    public function getErrorByHash($hash) {
        $error  = null;
        $errors = $this->getErrors();
        
        if (isset($errors[$hash]) && ($errors[$hash]->status != 'rejected')) {
            $error = $errors[$hash];
        }

        return $error;
    }

    /**
     * Get instance of itself
     * 
     * @return CodePinch_Storage
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}