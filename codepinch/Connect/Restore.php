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
 * Restore original file
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Connect_Restore extends CodePinch_Connect_Abstract {
    
    /**
     * Restore original file
     * 
     * @return void
     * 
     * @access public
     */
    public function process() {
        $patch    = filter_input(INPUT_POST, 'patch');
        $content  = base64_decode(filter_input(INPUT_POST, 'content'));
        $filepath = null;
        
        foreach (CodePinch_History::getInstance()->getErrors() as $error) {
            if ($error->patch['id'] == $patch) {
                $filepath = $error->filepath;
                break;
            }
        }
        
        if (!empty($filepath) && is_writable($filepath)) {
            $result = file_put_contents($filepath, $content);
        }
        
        $this->printResponse(json_encode(array(
            'status' => empty($result) ? 'failure' : 'success'
        )));
    }
    
}