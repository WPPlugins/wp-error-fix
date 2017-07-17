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
 * Error Fix patcher
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Patcher {

    /**
     *
     * @var type 
     */
    protected $subject;
    
    /**
     * 
     * @return type
     */
    public function patch(array $subject) {
        $this->setSubject($subject);
        
        $filepath = $subject['filepath'];
        
        //first get the patch from the storage
        if (!file_exists($filepath) || !@fopen($filepath, 'a')) {
            Throw new Exception(sprintf('File %s is not writable', $filepath));
        }
        
        //make sure that file was not modified from the original state
        if ($subject['checksum'] != CodePinch_File::getChecksum($filepath, true)) {
            Throw new Exception('File checksum mismatch');
        }
        
        //check if instance have enough credit
        $balance = CodePinch_Core::get('balance');
        if (($balance != -1) && ($balance < $subject['price'])) {
            Throw new Exception('Not enough credit to apply the fix');
        }

        //retrieve patch from the external server & overwrite the file
        if (!file_put_contents($filepath, $this->retrievePatch())) {
            Throw new Exception(sprintf('Failed to overwrite %s file', $filepath));
        }
        
        //store resolved errors to history
        $this->updateHistory();
        
        //clear file checksum cache
        CodePinch_File::flush();

        return true;
    }

    /**
     * 
     * @return type
     * @throws Exception
     */
    protected function retrievePatch() {
        $server  = new CodePinch_Server;
        $subject = $this->getSubject();
        
        $response = $server->apply(
                CodePinch_Core::get('instance'), $subject['id']
        );
        if ($response->status == 'success') {
            $source = base64_decode($response->content);
            if (md5($source) != $response->checksum) {
                Throw new Exception('Failed to get fix. Checksum mismatch');
            }
        } else {
            Throw new Exception($response->reason);
        }
        
        return $source;
    }
    
    /**
     * 
     */
    protected function updateHistory() {
        $history = CodePinch_History::getInstance();
        $subject = $this->getSubject();
        
        foreach(CodePinch_Storage::getInstance()->getErrors() as $error) {
            if (isset($error->patch) && ($error->patch['id'] == $subject['id'])) {
                $history->addError($error);
            }
        }
        $history->save();
    }

    /**
     * 
     * @param array $subject
     */
    protected function setSubject(array $subject) {
        $this->subject = $subject;
    }

    /**
     * 
     * @return type
     */
    protected function getSubject() {
        return $this->subject;
    }

}