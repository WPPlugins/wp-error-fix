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
 * CodePinch cron job
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class CodePinch_Routine {

    /**
     *
     * @var type 
     */
    protected $trace = false;
    
    /**
     *
     * @var type 
     */
    protected $logs = array();
    
    /**
     * List of uploads
     * 
     * @var type 
     */
    protected $uploaded = array();
    
    /**
     * Execute the routine
     * 
     * Typically this function is used to execute the cron job.
     * 
     * @param boolean $trace
     * 
     * @return array
     * 
     * @access public
     */
    public function execute($trace = false) {
        $this->trace = $trace;
        
        //check for available solutions
        $this->checkReports();
        
        //report errors
        $this->reportErrors();
        
        //patch errors
        $this->patchErrors();
        
        $this->log(
                'Storage', 
                json_encode(CodePinch_Storage::getInstance()->getErrors(true))
        );
        
        CodePinch_Storage::getInstance()->save();
        
        return array('status' => 'success', 'logs' => $this->logs);
    }

    /**
     * Report errors
     * 
     * Get all pending errors and try to report them to the external server.
     * 
     * @return void
     * 
     * @access protected
     */
    protected function reportErrors() {
        $server = new CodePinch_Server;
        $errors = $this->preparePendingErrors();
        
        if (count($errors)) {
            $result = $server->report(CodePinch_Core::get('instance'), $errors);
            
            $this->log('Report Trace', json_encode($result));
            
            if ($result->status == 'success') {
                $this->updateReportedErrors($result->reports);
            }
        }
    }
    
    /**
     * 
     * @param type $reports
     */
    protected function updateReportedErrors($reports) {
        $storage = CodePinch_Storage::getInstance();
        
        //update reports 
        foreach($reports as $report) {
            if ($error = $storage->getErrorByHash($report->hash)) {
                if (!empty($report->report)) {
                    $error->status = 'reported';
                    $error->report = $report->report;
                } elseif (!empty($report->rejected)) {
                    $error->status = 'rejected';
                } else {
                    $error->status = 'failed';
                }
            }
        }
    }
    
    /**
     * 
     * @return array
     */
    protected function preparePendingErrors() {
        $errors  = array();
        
        $count   = 1; //add only limited number of reports
        $limit   = CodePinch_Core::get('reportLimit');
        $allow   = array('analyzed', 'failed');
        $autofix = CodePinch_Core::get('autofix');
        
        foreach (CodePinch_Storage::getInstance()->getErrors() as $error) {
            if (!$autofix && empty($error->requested)) { continue; }
            if (($count <= $limit) && in_array($error->status, $allow)) {
                $filepath = str_replace('\\', '/', $error->filepath);
                $errors[] = array(
                    'module'   => base64_encode($error->module['name']),
                    'version'  => base64_encode($error->module['version']),
                    'file'     => str_replace($error->module['path'], '', $filepath),
                    'line'     => $error->line,
                    'type'     => $error->type,
                    'message'  => base64_encode($error->message),
                    'checksum' => $error->checksum,
                    'hash'     => $error->hash,
                    'encoded'  => 1 //TODO - Remove it in API v4
                );
                $count++;
            }
        }
        
        return $errors;
    }

    /**
     * 
     */
    protected function checkReports() {
        $storage  = CodePinch_Storage::getInstance();
        $queue    = $this->prepareCheckQueue();
        
        if (count($queue)) {
            $server = new CodePinch_Server;
            $result = $server->check(CodePinch_Core::get('instance'), $queue);
            
            //TODO - Move this outsite of the codepinch framework
            $this->uploaded = ErrorFix_Core_Option::getOption(
                    'errorfix_uploads', array()
            );
            
            $this->log('Check Trace', json_encode($result));
            
            if ($result->status == 'success') {
                foreach($result->reports as $report) {
                    if ($error = $storage->getErrorByHash($report->hash)) {
                        $this->updateError($error, $report);
                    }
                }
            }
        }
    }
    
    /**
     * 
     * @return type
     */
    protected function prepareCheckQueue() {
        $reports = array();
        
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            $queue = call_user_func("$classname::load", 'queue'); //PHP 5.2
        } else { //fallback to default storage method
            $queue = CodePinch_Storage_File::load('queue');
        }
        
        $storage = CodePinch_Storage::getInstance();
        
        if (empty($queue)) {
            foreach ($storage->getErrors() as $error) {
                if ($error->status == 'reported') {
                    $queue[] = $error->hash;
                }
            }
        }
        
        $count = 0;
        
        while(count($queue) && ($count++ < CodePinch_Core::get('checkLimit'))) {
            if ($error = $storage->getErrorByHash(array_shift($queue))) {
                $reports[] = array('id' => $error->report, 'hash' => $error->hash);
            }
        }
        
        $this->saveCheckQueue($queue);
        
        return $reports;
    }
    
    /**
     * 
     * @param type $queue
     */
    protected function saveCheckQueue($queue) {
        $classname = CodePinch_Core::get('storage');
        if (class_exists($classname)) {
            $queue = call_user_func("$classname::save", $queue, 'queue'); //PHP 5.2
        } else { //fallback to default storage method
            $queue = CodePinch_Storage_File::save($queue, 'queue');
        }
    }
    
    /**
     * 
     * @param type $error
     * @param type $res
     */
    protected function updateError($error, $res) {
        switch ($res->status) {
            case 'resolved':
                $error->status = 'resolved';
                $error->patch  = array(
                    'id'    => $res->patch, 
                    'price' => $res->price
                );
                break;

            case 'rejected':
                $error->status = 'rejected';
                //TODO - Remove when notes is added to server side implementation
                if (isset($res->message)) {
                    CodePinch_Note::getInstance()->addNote((object) array(
                        'message' => $res->message, 'code' => $res->code
                    ));
                    CodePinch_Note::getInstance()->save();
                }
                break;
                
            case 'pending':
                if ($res->reason == 30) { //missing module
                    $this->uploadModule($error);
                } elseif ($res->reason == 35) { //missing file
                    $this->uploadFile($error);
                }
                break;
                
            case 'failed':
                CodePinch_Storage::getInstance()->removeError($res->hash);
                break;

            default:
                break;
        }
    }
    
    /**
     * 
     * @param type $error
     */
    protected function uploadModule($error) {
        $hash = sha1($error->module['name'] . $error->module['version']);
        
        if (!in_array($hash, $this->uploaded)) {
            $downloader = new CodePinch_Connect_Download;
            $data       = $downloader->getModule($error);
            $server     = new CodePinch_Server;
            
            $result = $server->upload(
                    CodePinch_Core::get('instance'), 
                    $error->report, 
                    base64_encode($data)
            );
            
            if ($result->status === 'success') {
                $this->uploaded[] = $hash;

                //TODO - Move this outside of the codepinch framework
                ErrorFix_Core_Option::updateOption(
                        'errorfix_uploads', $this->uploaded
                );
            }
        }
    }
    
    /**
     * 
     * @param type $error
     */
    protected function uploadFile($error) {
        $uid  = $error->module['name'] . $error->module['version'];
        $uid .= $error->filepath . $error->checksum;
        $hash = sha1($uid);
        
        if (!in_array($hash, $this->uploaded)) {
            $downloader = new CodePinch_Connect_Download;
            $data       = $downloader->getFile($error);
            $server     = new CodePinch_Server;
            
            $result = $server->upload(
                    CodePinch_Core::get('instance'), 
                    $error->report, 
                    base64_encode($data)
            );
            
            if ($result->status === 'success') { 
                $this->uploaded[] = $hash;

                //TODO - Move this outside of the codepinch framework
                ErrorFix_Core_Option::updateOption(
                        'errorfix_uploads', $this->uploaded
                );
            }
        }
    }
    
    /**
     * 
     */
    protected function patchErrors(){
        $storage = CodePinch_Storage::getInstance();
        $patcher = new CodePinch_Patcher;
        $autofix = CodePinch_Core::get('autofix');
        $fixed   = 0;
        
        foreach ($storage->getPatchList() as $patch) {
            try {
                if (!empty($patch['error']->requested) || $autofix) {
                    $patcher->patch($patch);
                    $fixed += $patch['errors'];
                }
            } catch (Exception $e) {
                $this->log('Patching Failure', $e->getMessage());
            }
        }
        
        return $fixed;
    }
    
    /**
     * 
     * @param type $row
     */
    protected function log($action, $row) {
        if ($this->trace) {
            $this->logs[$action] = $row;
        }
    }
    
}