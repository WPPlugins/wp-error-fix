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
 * CodePinch core
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Core {

    /**
     * Single instance of itself
     * 
     * @var CodePinch_Core
     * 
     * @access protected 
     */
    protected static $instance = null;
    
    /**
     * CodePinch_Core global configurations
     * 
     * @var array
     * 
     * @access protected
     * @see CodePinch_Core::bootstrap
     */
    protected static $config = array();
    
    /**
     * Core construct
     * 
     * Initialize the core framework object and register PHP error handlers for
     * errors, uncatched exceptions and script shutdown execution
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        ob_start();
        
        //set custom error handlers
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));
    }
    
    /**
     * Handle the PHP error
     * 
     * When triggered PHP error matches the error reporting level, this function
     * prepare the md5 checksum of the reported file and store the error info to
     * the CodePinch storage.
     * 
     * @param int    $type
     * @param string $message
     * @param string $filepath
     * @param int    $line
     * 
     * @return    bool  Always return false to trigger PHP core error handling
     * @staticvar array $cache
     * 
     * @access public
     */
    public function errorHandler($type, $message, $filepath, $line) {
        if (error_reporting() & $type) {
            CodePinch_Storage::getInstance()->addError(array(
                'type'     => $type,
                'message'  => $message,
                'filepath' => $filepath,
                'line'     => $line,
                'checksum' => CodePinch_File::getChecksum($filepath),
                'time'     => time()
            ));
        }
        
        //let PHP core error handler finish the rest
        return false;
    }
    
    /**
     * Handle uncatched exception
     * 
     * @param Exception|Error $e
     * 
     * @return void
     * 
     * @access public
     */
    public function exceptionHandler($e) {
        $this->errorHandler(
                E_ERROR, 
                get_class($e) . ': ' . $e->getMessage(), 
                $e->getFile(), 
                $e->getLine()
        );
    }
    
    /**
     * Handle PHP shut down process
     * 
     * If the shut down has been initiated by the Fatal Error, then this
     * function will store the error to the CodePinch storage.
     * 
     * In addition this function trigger storage normalization process as well
     * as ask CodePinch storage to save data if modified
     *
     * @return void
     * 
     * @access public
     * @see CodePinch_Storage::normalize
     * @see CodePinch_Storage::save
     */
    public function shutdownHandler() {
        if ($err = error_get_last()) {
            if (in_array($err['type'], array(E_ERROR, E_USER_ERROR))) {
                $this->errorHandler(
                    $err['type'], $err['message'], $err['file'], $err['line']
                );
            }
        }

        //cover the case when plugin is deleted
        if (class_exists('CodePinch_Storage')) {
            CodePinch_Storage::getInstance()->save();
        }
        
        @ob_end_flush();
    }

    /**
     * Autoloader for CodePinch framework
     *
     * Try to load a class if prefix is CodePinch_
     *
     * @param string $classname
     * 
     * @return void
     * 
     * @access public
     * @static
     */
    public static function autoload($classname) {
        $chunks = explode('_', $classname);
        $prefix = array_shift($chunks);

        if ($prefix === 'CodePinch') {
            $basedir  = dirname(__FILE__);
            $filename = $basedir . '/' . implode('/', $chunks) . '.php';
        } elseif (in_array($classname, array('PclZip', 'Zipstream'))) {
            $filename = dirname(__FILE__) . '/Vendor/' . $classname . '.php';
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * 
     * @param type $param
     * @param type $default
     * @return type
     */
    public static function get($param, $default = null) {
        return isset(self::$config[$param]) ? self::$config[$param] : $default;
    }
    
    /**
     * 
     * @param type $param
     * @param type $value
     */
    public static function set($param, $value) {
        self::$config[$param] = $value;
    }

    /**
     * Register autoloader
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap(array $config = array()) {
        //set autoloader for the Error Fix framework
        spl_autoload_register('CodePinch_Core::autoload');
        
        //set Error Fix configurations
        self::$config = array_merge(
                array(
                    'storageLimit' => 1000,
                    'reportLimit'  => 10,
                    'checkLimit'   => 20,
                    'endpoint'     => 'http://errorfix.vasyltech.com/v3',
                    'basedir'      => dirname(__FILE__) . '/Logs'
                ),
                $config
        );
        
        //create an instance of Error Fix Core
        self::$instance = new self;
    }

}