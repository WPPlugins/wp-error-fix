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
 * Debugging tools
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Connect_Debug extends CodePinch_Connect_Abstract {
    
    /**
     * Process request
     * 
     * @return void
     * 
     * @access public
     */
    public function process() {
        $tool = filter_input(INPUT_GET, 'tool');
        
        if (method_exists($this, $tool)) {
            call_user_func(array($this, $tool));
        }
    }
    
    /**
     * Get error storage
     * 
     * @return void
     * 
     * @access protected
     */
    protected function getStorage() {
        $storage = json_encode(CodePinch_Storage::getInstance()->getErrors(true));
        
        $this->printResponse($storage);
    }
    
    /**
     * Reset storage
     * 
     * @access protected 
     * 
     * @return void
     */
    protected function resetStorage() {
        CodePinch_Storage::getInstance()->reset();
        
        $this->printResponse(json_encode(array('status' => 'success')));
    }
    
}