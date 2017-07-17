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
 * CodePinch connector
 * 
 * Download request source code
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Connect_Download extends CodePinch_Connect_Abstract {

    /**
     * Allowed files
     */
    const FILE_REGEXP = '/^.+\.(php|phtml|inc)$/i';
    
    /**
     * Process request
     * 
     * @return void
     * 
     * @access public
     */
    public function process() {
        $error   = $this->getErrorByReport();
        $object  = filter_input(INPUT_GET, 'object');
        $headers = array();
        $content = null;
        
        if (!empty($error)) {
            if ($object == 'file') {
                $content = $this->getFile($error);
                $headers = $this->prepareHeaders(
                            basename(str_replace('\\', '/', $error->filepath)),
                            strlen($content)
                );
            } elseif ($object == 'module') {
                $content = $this->getModule($error);
                $headers = $this->prepareHeaders(
                        basename($error->module['path']),
                        strlen($content)
                );
            } else {
                $headers = array('HTTP/1.1 400 Bad Request');
            }
        } else {
            $headers = array('HTTP/1.1 404 Not Found');
        }
        
        $this->printResponse($content, $headers);
    }

    /**
     * Download individual file
     * 
     * @param stdClass $error
     * 
     * @return blob
     * 
     * @access public
     */
    public function getFile($error) {
        $filename = str_replace('\\', '/', $error->filepath);
        $basepath = $error->module['path'];
        $zip      = CodePinch_Zip_Factory::get();
        $instance = CodePinch_Core::get('instance');

        $zip->add($filename, $basepath, $instance . '/');
        
        if ($zip->getError() != PCLZIP_ERR_NO_ERROR) { //fallback
            $zip = CodePinch_Zip_Factory::get('Zipstream');
            $zip->add($filename, $basepath, $instance . '/');
        }
        
        return $zip->output();
    }
    
    /**
     * Fetch module
     * 
     * @param stdClass $error
     * 
     * @return blob
     * 
     * @access public
     */
    public function getModule($error) {
        $path    = $error->module['path'];
        $zip     = $this->archiveModule($path);
        $content = null;
        
        if ($zip->getError() == PCLZIP_ERR_NO_ERROR) {
            $content = $zip->output();
        }
        
        return $content;
    }
    
    /**
     * Prepare download headers
     * 
     * @param string $fname
     * 
     * @return array
     * 
     * @access protected
     */
    protected function prepareHeaders($fname, $size) {
        if (filter_input(INPUT_GET, 'inbrowser')) {
            $headers = array(
                'HTTP/1.1 ' . ($size ? '200 OK' : '417 Expectation Failed'),
                'Content-Type: application/zip',
                'Content-Transfer-Encoding: Binary',
                'Content-disposition: attachment; filename="' . $fname . '.zip"'
            );
        } else {
            $headers = array();
        }
        
        return $headers;
    }
    
    /**
     * Archive module
     * 
     * @param string $basepath
     * 
     * @return PclZip
     * 
     * @access protected
     */
    protected function archiveModule($basepath) {
        $zip = CodePinch_Zip_Factory::get();
            
        $directory = new RecursiveDirectoryIterator($basepath);
        $iterator  = new RegexIterator(
                new RecursiveIteratorIterator($directory), 
                self::FILE_REGEXP, 
                RecursiveRegexIterator::GET_MATCH
        );

        foreach($iterator as $file) {
            $zip->add($file[0], dirname($basepath));
        }
        
        return $zip;
    }

}