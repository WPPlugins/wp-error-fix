<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend ajax manager
 * 
 * @package ErrorFix
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Backend_Ajax {
    
    /**
     *
     * @var type 
     */
    protected $errorTypes = array(
        E_ERROR             => 'Fatal Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Fatal Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compiler Fatal Error',
        E_COMPILE_WARNING   => 'Compiler Warning',
        E_USER_ERROR        => 'User Fatal Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Fatal Error'
    );
    
    /**
     *
     * @var type 
     */
    protected $errorClass = array(
        E_ERROR             => 'error',
        E_WARNING           => 'warning',
        E_PARSE             => 'error',
        E_NOTICE            => 'notice',
        E_CORE_ERROR        => 'error',
        E_CORE_WARNING      => 'warning',
        E_COMPILE_ERROR     => 'error',
        E_COMPILE_WARNING   => 'warning',
        E_USER_ERROR        => 'error',
        E_USER_WARNING      => 'warning',
        E_USER_NOTICE       => 'notice',
        E_STRICT            => 'strict',
        E_RECOVERABLE_ERROR => 'error'
    );
    
    
    /**
     * Construct
     */
    public function __construct() {
        if (version_compare(PHP_VERSION, '5.3.0') !== -1) {
            $this->errorTypes[E_DEPRECATED]      = 'Deprecated';
            $this->errorClass[E_DEPRECATED]      = 'deprecated';
            $this->errorTypes[E_USER_DEPRECATED] = 'User Deprecated';
            $this->errorClass[E_USER_DEPRECATED] = 'deprecated';
        }
    }

    /**
     * 
     * @return type
     */
    public function process() {
        $action = filter_input(INPUT_POST, 'sub_action');

        if (method_exists($this, $action)) {
            $response = call_user_func(array($this, $action));
        } else {
            $response = null;
        }

        return json_encode($response);
    }

    /**
     * 
     * @return type
     */
    protected function getErrorList() {
        $response = array(
            'draw' => filter_input(INPUT_POST, 'draw'),
            'data' => array()
        );
        
        foreach (CodePinch_Storage::getInstance()->getErrors() as $error) {
            $row = array(
                $this->formatMessage($error),
                date('Y-m-d H:i:s', $error->time) . ' (' . $error->hits . ')',
                $error->module['name'],
                $this->getReportStatus($error),
                $error->status
            );
            
            if (ErrorFix_Core_Option::getId() && !ErrorFix_Core_Option::getVip()) {
                $row[] = (empty($error->requested) ? 0 : 1);
            }
            
            $row['DT_RowClass'] = $this->getTypeClass($error->type);
            $row['DT_RowId']    = $error->hash;
            
            $response['data'][] = $row;
        }

        return $response;
    }
    
    /**
     * 
     * @return type
     */
    protected function requestFix() {
        $result  = false;
        $balance = ErrorFix_Core_Option::getBalance();
        $vip     = ErrorFix_Core_Option::getVip();
        $status  = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
        
        if (!$status || $vip || ($balance > 1)) {
            $hash  = filter_input(INPUT_POST, 'hash');
            $error = CodePinch_Storage::getInstance()->getErrorByHash($hash);

            if ($status) {
                $error->requested = true;
            } else {
                unset($error->requested);
            }
            CodePinch_Storage::getInstance()->save();
            
            $result = true;
        }
        
        return array(
            'status' => $result ? 'success' : 'failure',
            'reason' => $result ? '' : __('At least $1.00 is required to request a fix', ERRORFIX_KEY)
        );
    }
    
    /**
     * 
     * @return type
     */
    protected function getNotesList() {
        $response = array(
            'draw' => filter_input(INPUT_POST, 'draw'),
            'data' => array()
        );

        foreach (CodePinch_Note::getInstance()->getNotes() as $note) {
            $response['data'][] = array(
                $note->code,
                htmlspecialchars($note->message), //We do not do XSS!
                ''
            );
        }

        return $response;
    }
    
    /**
     * 
     * @return type
     */
    protected function acceptNote() {
        $code = filter_input(INPUT_POST, 'code');
        CodePinch_Note::getInstance()->removeNote($code);
        CodePinch_Note::getInstance()->save();
        
        return array('status' => 'success');
    }
    
    /**
     * 
     * @return type
     */
    protected function getHistoryList() {
        $response = array(
            'draw' => filter_input(INPUT_POST, 'draw'),
            'data' => array()
        );

        foreach (CodePinch_History::getInstance()->getErrors() as $error) {
            $response['data'][] = array(
                $this->formatMessage($error),
                date('Y-m-d H:i:s', $error->resolved),
                'DT_RowClass' => str_replace(
                        ' ', '-', strtolower($this->getTypeLabel($error->type))
                ),
            );
        }

        return $response;
    }
    
    /**
     * 
     * @param type $error
     * @return string
     */
    protected function getReportStatus($error) {
        if ($error->status == 'resolved') {
            $status = $error->patch['id'];
        } elseif ($error->status == 'rejected') {
            $status = $error->reason;
        } else {
            $status = '';
        }
        
        return $status;
    }

    /**
     * 
     * @param type $error
     * @return string
     */
    protected function formatMessage($error) {
        $type     = $this->getTypeLabel($error->type);
        $message  = '<b>' . $type . ':</b> ' . $error->message . '<br/>';
        $message .= '<small>' . $error->relpath . ' ' . $error->line . '</small>';

        return $message;
    }
    
    /**
     * 
     * @param type $type
     * @return string
     */
    protected function getTypeLabel($type) {
        if (isset($this->errorTypes[$type])) {
            $label = $this->errorTypes[$type];
        } else {
            $label = 'Unknown';
        }
        
        return __($label, ERRORFIX_KEY);
    }
    
    /**
     * 
     * @param type $type
     * @return string
     */
    protected function getTypeClass($type) {
        if (isset($this->errorClass[$type])) {
            $class = $this->errorClass[$type];
        } else {
            $class = 'string';
        }
        
        return $class;
    }

    /**
     * 
     * @return type
     */
    protected function getPatchList() {
        $response = array(
            'draw' => filter_input(INPUT_POST, 'draw'), 'data' => array()
        );
        
        $vip = ErrorFix_Core_Option::getVip();

        foreach (CodePinch_Storage::getInstance()->getPatchList() as $patch) {
            $response['data'][] = array(
                $patch['id'],
                false,
                $patch['errors'],
                sprintf('%01.2f', ($vip ? 0 :$patch['price'])),
                ''
            );
        }

        return $response;
    }
    
    /**
     * 
     */
    protected function getPieData() {
        $grouped = array();
        foreach (CodePinch_Storage::getInstance()->getErrors() as $error) {
            if (!isset($grouped[$error->module['name']])) {
                $grouped[$error->module['name']] = 0;
            }
            $grouped[$error->module['name']] ++;
        }

        //decorate the response
        $response = array();
        foreach ($grouped as $module => $counter) {
            $response[] = array('label' => $module, 'value' => $counter);
        }

        return $response;
    }

    /**
     * 
     */
    protected function register() {
        $response = array('status' => 'failure');

        //check if environment is registered already
        $registered = ErrorFix_Core_Option::getId();

        if ($registered === null) {
            $server = new CodePinch_Server;
            $res = $server->register(
                    'WordPress', 
                    get_option('admin_email'), 
                    get_option('codepinch-affiliate')
            );
            if ($res->status === 'success') {
                ErrorFix_Core_Option::setId($res->instance);
                ErrorFix_Core_Option::updateBalance($res->balance);
                delete_option('codepinch-affiliate');
                $registered = true;
            }
            if (isset($res->reason)) {
                $response['message'] = $res->reason;
            }
            
        }

        if ($registered) {
            $response['status'] = 'success';
        }

        return $response;
    }
    
    /**
     * 
     * @return type
     */
    protected function activate() {
        $response = array('status' => 'failure');

        //check if environment is registered already
        $instance = ErrorFix_Core_Option::getId();
        $license  = filter_input(INPUT_POST, 'license');

        if ($instance && $license) {
            $server = new CodePinch_Server;
            $res = $server->activate($instance, $license);
            if ($res->status === 'success') {
                ErrorFix_Core_Option::updateVip($res->vip);
                $response['status'] = 'success';
            }
            
            if (isset($res->reason)) {
                $response['message'] = $res->reason;
            }
        } else {
            $response['message'] = 'Invalid input';
        }

        return $response;
    }

    /**
     * 
     * @return type
     */
    protected function getStatus() {
        $response = array(
            'status'  => 'success',
            'balance' => sprintf('%01.2f', ErrorFix_Core_Option::getBalance())
        );

        $server = new CodePinch_Server;
        $result = $server->status(ErrorFix_Core_Option::getId());
        
        if ($result->status === 'success') {
            if (isset($result->balance)) {
                ErrorFix_Core_Option::updateBalance($result->balance);
                ErrorFix_Core_Option::updateVip($result->vip);
                $response['balance'] = sprintf('%01.2f', $result->balance);
            }
        }
        
        if (isset($result->message)) {
            $response['message'] = $result->message;
        }

        return $response;
    }

    /**
     * 
     * @return type
     */
    protected function apply() {
        $result = false;

        $patch   = filter_input(INPUT_POST, 'patch');
        $patches = CodePinch_Storage::getInstance()->getPatchList();

        if (isset($patches[$patch])) {
            try {
                $patcher = new CodePinch_Patcher;
                $result  = $patcher->patch($patches[$patch]);
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        } else {
            $result = __('Failed to find the report', ERRORFIX_KEY);
        }

        return array(
            'status'  => ($result !== true ? 'failure' : 'success'),
            'message' => ($result !== true ? $result : '')
        );
    }
    
    /**
     * 
     * @return type
     */
    protected function check() {
        ErrorFix::getInstance()->cron();
    }
    
    /**
     * 
     * @return type
     * 
     * @since 3.3
     */
    protected function updateSetting() {
        $setting = filter_input(INPUT_POST, 'setting');
        $value   = filter_input(INPUT_POST, 'value');
        
        $settings = ErrorFix_Core_Option::getSettings();
        $settings[$setting] = $value;
        
        $result = ErrorFix_Core_Option::updateSettings($settings);
        
        return array('status' => ($result ? 'success' : 'failure'));
    }
    
    /**
     * 
     * @return type
     */
    protected function sendMessage() {
        $response = array('status' => 'failure');
        
        $fullname = filter_input(INPUT_POST, 'fullname');
        $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $message  = filter_input(INPUT_POST, 'message');
        $id       = ErrorFix_Core_Option::getId();

        if ($fullname && $email && $message && $id) {
            $server = new CodePinch_Server;
            $res = $server->message($id, $fullname, $email, $message);
            if ($res->status === 'success') {
                $response['status'] = 'success';
                //save Fullname and Contact Email address for future
                $settings = ErrorFix_Core_Option::getSettings();
                $settings['contactFullname'] = $fullname;
                $settings['contactEmail']    = $email;
                ErrorFix_Core_Option::updateSettings($settings);
            } else {
                $response['reason'] = $res->reason;
            }
        } else {
            $response['reason'] = 'All fields are required to contact us';
        }

        return $response;
    }

}