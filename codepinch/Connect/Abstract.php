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
 * CodePinch connect abstract
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
abstract class CodePinch_Connect_Abstract {

    /**
     * Process request
     * 
     * @return mixed
     * 
     * @access public
     */
    abstract public function process();
    
    /**
     * Print response
     * 
     * @param string Output
     * 
     * @return void
     */
    protected function printResponse($response, $headers = array()) {
        //clear the output buffer
        while (@ob_end_clean()) {}
        
        if ($headers) {
            foreach($headers as $header) {
                @header($header);
            }
        } else {
            @header('HTTP/1.1 200 OK');
        }
        
        echo $response;
        exit;
    }
    
    /**
     * Get error by the error report
     * 
     * @return stdClass|null
     * 
     * @access public
     */
    public function getErrorByReport($id = null) {
        $report = (empty($id) ? filter_input(INPUT_GET, 'report') : $id);
        
        //find the error by the report
        $error = null;
        foreach(CodePinch_Storage::getInstance()->getErrors(true) as $item) {
            if (isset($item->report) && ($item->report == $report)) {
                $error = $item;
                break;
            }
        }
        
        return $error;
    }
    
}