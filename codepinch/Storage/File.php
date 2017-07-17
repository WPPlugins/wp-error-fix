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
 * File storage
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Storage_File {

    /**
     * Load all records
     * 
     * @return array
     * 
     * @access public
     * @static
     */
    public static function load($namespace = 'storage') {
        $log = CodePinch_Core::get('basedir') . '/' . $namespace . '.php';

        if (file_exists($log)) {
            //skip first 8 characters "<?php //"
            $errors = @unserialize(substr(file_get_contents($log), 8));
        } else {
            $errors = array();
        }

        return (is_array($errors) ? $errors : array());
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
        $log = CodePinch_Core::get('basedir') . '/' . $namespace . '.php';

        if (file_exists($log)) {
            @unlink($log);
        }
    }

    /**
     * Save errors
     * 
     * @param array $errors
     * 
     * @return boolean
     * 
     * @access public
     */
    public static function save($errors, $namespace = 'storage') {
        $base = CodePinch_Core::get('basedir');
        $log  = $base . '/' . $namespace . '.php';
        
        if (!empty($errors)) {
            if (file_exists($base) === false) {
                @mkdir($base, fileperms(ABSPATH) & 0777 | 0755, true);
            }
            $result = @file_put_contents($log, '<?php //' . serialize($errors));
        } elseif (empty($errors) && file_exists($log)) {
            unlink($log);
        }
        
        return (empty($result) ? false : true);
    }

}