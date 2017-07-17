<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend manager
 * 
 * @package ErrorFix
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Backend_Manager {

    /**
     * Single instance of itself
     * 
     * @var ErrorFix_Backend_Manager
     * 
     * @access private 
     */
    private static $_instance = null;

    /**
     * Initialize the object
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //print required JS & CSS
        if (ErrorFix::isErrorFix()) {
            add_action('admin_print_scripts', array($this, 'printJavascript'));
            add_action('admin_print_styles', array($this, 'printStylesheet'));
        }

        //manager Admin Menu
        if (is_multisite() && is_network_admin()) {
            add_action('network_admin_menu', array($this, 'adminMenu'), 999);
            add_action('network_admin_notices', array($this, 'mainNotice'), 1);
        } elseif (!is_multisite()) {
            add_action('admin_menu', array($this, 'adminMenu'), 999);
            add_action('admin_notices', array($this, 'mainNotice'), 1);
        }
        
        //dashboard widget
        add_action('wp_dashboard_setup', array($this, 'registerWidget'));

        //manager AAM Ajax Requests
        add_action('wp_ajax_errorfix', array($this, 'ajax'));
        
        //footer thank you
        add_filter('admin_footer_text', array($this, 'thankYou'), 999);
        
        //check plugin file structure
        $this->checkFileStructure();
    }
    
    /**
     * 
     * @param type $text
     * @return string
     */
    public function thankYou($text) {
        if (ErrorFix::isErrorFix()) {
            $text  = '<span id="footer-thankyou">';
            $text .= '<b>Please help us</b> and submit your review <a href="';
            $text .= 'https://wordpress.org/support/plugin/wp-error-fix/reviews/"';
            $text .= 'target="_blank"><i class="icon-star"></i>';
            $text .= '<i class="icon-star"></i><i class="icon-star"></i>';
            $text .= '<i class="icon-star"></i><i class="icon-star"></i></a>';
            $text .= '</span>';
        }
        
        return $text;
    }

    /**
     * Print javascript libraries
     *
     * @return void
     *
     * @access public
     */
    public function printJavascript() {
        wp_enqueue_script('errorfix-bt', ERRORFIX_MEDIA . '/js/bootstrap.min.js');
        wp_enqueue_script('errorfix-dt', ERRORFIX_MEDIA . '/js/datatables.min.js');
        wp_enqueue_script('errorfix-rp', ERRORFIX_MEDIA . '/js/raphael.min.js');
        wp_enqueue_script('errorfix-ms', ERRORFIX_MEDIA . '/js/morris.min.js');
        wp_enqueue_script('errorfix-tg', ERRORFIX_MEDIA . '/js/toggle.min.js');
        wp_enqueue_script('errorfix-main', ERRORFIX_MEDIA . '/js/errorfix.js');
        //add plugin localization
        $this->printLocalization('errorfix-main');
    }

    /**
     * Print plugin localization
     * 
     * @param string $localKey
     * 
     * @return void
     * 
     * @access protected
     */
    protected function printLocalization($localKey) {
        wp_localize_script($localKey, 'errorFixLocal', array(
            'nonce' => wp_create_nonce('errorfix_ajax'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'translation' => require (dirname(__FILE__) . '/Localization.php')
        ));
    }

    /**
     * Print necessary styles
     *
     * @return void
     *
     * @access public
     */
    public function printStylesheet() {
        wp_enqueue_style('errorfix-bt', ERRORFIX_MEDIA . '/css/bootstrap.min.css');
        wp_enqueue_style('errorfix-db', ERRORFIX_MEDIA . '/css/datatables.min.css');
        wp_enqueue_style('errorfix-morris', ERRORFIX_MEDIA . '/css/morris.min.css');
        wp_enqueue_style('errorfix-main', ERRORFIX_MEDIA . '/css/errorfix.css');
    }
    
    /**
     * Check plugin's file structure
     * 
     * Verify that all necessary folders are in place and are writable
     * 
     * @return void
     * 
     * @access protected
     */
    protected function checkFileStructure() {
        foreach(CodePinch_Storage::getInstance()->getErrors() as $error) {
            $vip = ErrorFix_Core_Option::getVip();
            //If VIP then check file if file writable even before report is resolved
            if ($error->status == 'resolved' || !empty($error->requested) || $vip) {
                if (!@fopen($error->filepath, 'a')) {
                    ErrorFix_Core_Console::add(
                        sprintf(
                            $this->preparePhrase('File [%s] is not writable', 'b'), 
                            $error->relpath
                        )
                    );
                }
            }
        }
    }
    
    /**
     * Register Admin Menu
     *
     * @return void
     *
     * @access public
     */
    public function adminMenu() {
        $counter = count(CodePinch_Storage::getInstance()->getErrors());
        
        if ($counter) {
            $trail  = '&nbsp;<span class="update-plugins">';
            $trail .= '<span class="plugin-count">' . $counter . '</span></span>';
        } else {
            $trail = '';
        }

        //register the menu
        add_menu_page(
            'CodePinch', 
            'CodePinch' . $trail, 
            'administrator', 
            'errorfix', 
            array($this, 'renderPage'), 
            ERRORFIX_MEDIA . '/active-menu.svg'
        );
    }
    
    /**
     * Main Dashboard notification
     * 
     * @return void
     * 
     * @access public
     */
    public function mainNotice() {
        $patches = CodePinch_Storage::getInstance()->getPatchList();
        $allowed = current_user_can('edit_plugins');
        $screen  = get_current_screen();
        
        if (count($patches) && $allowed 
                            && (isset($screen->id) && ($screen->id == 'dashboard'))) {
            if (is_network_admin()) {
                $url = network_admin_url('?page=errorfix');
            } else {
                $url = admin_url('?page=errorfix');
            }
            
            $style = 'padding: 10px; font-weight: 700; letter-spacing:0.5px;';
            
            echo '<div class="updated notice"><p style="' . $style . '">';
            echo 'New fix is available for download. ';
            echo '<a href="' . $url . '">Go to CodePinch.</a>';
            echo '</p></div>';
        }
    }
    
    /**
     * Register dashboard widget
     * 
     * @return void
     * 
     * @access public
     */
    public function registerWidget() {
        wp_add_dashboard_widget(
            'errorfix-widget', 
            __('CodePinch Widget', ERRORFIX_KEY),
            array($this, 'renderWidget')
        );	
    }
    
    /**
     * Render dashboard widget
     * 
     * All styling is inline to avoid additional CSS printed in the header
     * 
     * @return void
     * 
     * @access public
     */
    public function renderWidget() {
        //prepare stats
        $stats = (object) array('errors' => 0, 'reported' => 0, 'fixes' => 0);
        foreach(CodePinch_Storage::getInstance()->getErrors() as $error) {
            $stats->errors   += 1;
            $stats->reported += ($error->status == 'reported' ? 1 : 0);
        }
        $stats->fixed = count(CodePinch_History::getInstance()->getErrors());
        
        ob_start();
        require_once(dirname(__FILE__) . '/view/widget.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        echo $content;
    }

    /**
     * Render Main Content page
     *
     * @return void
     *
     * @access public
     */
    public function renderPage() {
        ob_start();
        require_once(dirname(__FILE__) . '/view/index.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        echo $content;
    }
    
    /**
     * Handle Ajax calls to ErrorFix
     *
     * @return void
     *
     * @access public
     */
    public function ajax() {
        check_ajax_referer('errorfix_ajax');

        //clean buffer to make sure that nothing messing around with system
        while (@ob_end_clean()) {}

        //process ajax request
        $ajax = new ErrorFix_Backend_Ajax;
        echo $ajax->process();
        exit;
    }
    
    /**
     * Prepare phrase or label
     * 
     * @param string $phrase
     * @param mixed  $...
     * 
     * @return string
     * 
     * @access protected
     */
    public function preparePhrase($phrase) {
        //prepare search patterns
        $num = func_num_args();
        $search = array_fill(0, ($num - 1) * 2, null);
        array_walk($search, array($this, 'prepareWalk'));

        $replace = array();
        foreach (array_slice(func_get_args(), 1) as $key) {
            array_push($replace, "<{$key}>", "</{$key}>");
        }

        //localize the phase first
        return preg_replace($search, $replace, __($phrase, ERRORFIX_KEY), 1);
    }
    
    /**
     * 
     * @param string $value
     * @param type $index
     */
    protected function prepareWalk(&$value, $index) {
        $value = '/\\' . ($index % 2 ? ']' : '[') . '/';
    }
    

    /**
     * Bootstrap the manager
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
    }

    /**
     * Get instance of itself
     * 
     * @return AAM_Backend_View
     * 
     * @access public
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::bootstrap();
        }

        return self::$_instance;
    }

}