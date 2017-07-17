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
 * Error Fix file registry
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since 3.3.6
 */
class CodePinch_File {
    
    /**
     *
     * @var type 
     */
    protected static $cache = array();
    
    /**
     * Get file MD5 checksum
     * 
     * Get the file checksum at the moment of the error
     * 
     * @param string $filepath
     * 
     * @return string|null
     * 
     * @access public
     */
    public static function getChecksum($filepath, $reload = false) {
        if (!isset(self::$cache[$filepath]) || $reload) {
            if (@is_readable($filepath)) {
                self::$cache[$filepath] = md5_file($filepath);
            } else {
                self::$cache[$filepath] = null;
            }
        }
        
        return self::$cache[$filepath];
    }
    
    /**
     * Clear cache
     * 
     * @return void
     * 
     * @access public
     */
    public static function flush() {
        self::$cache = array();
    }
    
}