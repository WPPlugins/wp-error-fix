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


class CodePinch_Zip_Factory {

    /**
     * 
     * @return CodePinch_Zip_Adapter_Abstract
     */
    public static function get($adapter = 'PclZip') {
        //Try to open default 
        $zip       = new $adapter(self::getTmpDir() . '/' . uniqid());
        $classname = 'CodePinch_Zip_Adapter_' . $adapter;
        
        return new $classname($zip);
    }

    /**
     * 
     * @return string
     * @todo Cover scenario when all folders are not writable
     */
    protected static function getTmpDir() {
        if (function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
        } else {
            $dir = ini_get('upload_tmp_dir');
        }

        if (!@is_writable($dir)) {
            $dir = dirname(__FILE__) . '/tmp';
            if (!file_exists($dir)) {
                @mkdir($dir);
            }
        }

        return $dir;
    }

}