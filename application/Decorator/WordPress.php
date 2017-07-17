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
 * Error Metadata for WordPress
 * 
 * @package CodePinch
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class ErrorFix_Decorator_WordPress {

    /**
     * Metadata configurations
     * 
     * Set of useful configurations like siteurl or absolute path to the system
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $config = array();
    
    /**
     * Initialize the decorator
     * 
     * @return void
     * 
     * @access public
     * @throws Exception When WordPress core is not detected
     */
    public function __construct() {
        if (!defined('ABSPATH')) {
            Throw new Exception('Failed to detect WordPress core');
        } else {
            $this->config['siteurl'] = get_option('siteurl');
            $this->config['abspath'] = $this->normalizePath(ABSPATH);
        }
    }
    
    /**
     * Decorate the error
     * 
     * Added additional metadata to the captured error. Based on the physical 
     * filepath, detect what module (plugin, theme, WordPress core or custom code
     * inside the wp-content folder) caused the error.
     * 
     * Also normalize all pathes to indicated files and error message.
     * 
     * @param stdClass $error
     * 
     * @return boolean
     * 
     * @access public
     */
    public function decorate(stdClass $error) {
        //add module information
        $error->module  = $this->getModule($error->filepath);
        
        //normalized relative path to file from the system root
        $error->relpath = str_replace(
            $this->config['abspath'] . '/', 
            '', 
            $this->normalizePath($error->filepath)
        );
        
        //strip HTML tags if reference present & normalize file path
        $error->message = $this->filterMessage($error->message);
        
        //change status to analyzed
        $error->status = 'analyzed';
        
        return !empty($error->module);
    }
    
    /**
     * 
     * @param type $message
     * @return type
     */
    public function filterMessage($message) {
        //decorate the error message
        $filtered = str_replace('\\', '/', $message);
        $abspath  = $this->config['abspath'] . '/';
        $siteurl  = $this->config['siteurl'];
        
        //strip HTML tags if reference present & normalize file path
        return strip_tags(str_replace(array($abspath, $siteurl), '', $filtered));
    }
    
    /**
     * 
     * @param type $filepath
     * @return type
     */
    public function getModule($filepath) {
        $themeDir   = $this->normalizePath(get_theme_root());
        $pluginDir  = $this->normalizePath(WP_PLUGIN_DIR);
        $filename   = $this->normalizePath($filepath);

        if (strpos($filename, $themeDir . '/') !== false) {
            $module = $this->getTheme($this->getName($filename, $themeDir));
        } elseif (strpos($filename, $pluginDir . '/') !== false) {
            $module = $this->getPlugin($this->getName($filename, $pluginDir));
        } elseif (strpos($filename, $this->config['abspath'] . '/') !== false) {
            $module = $this->getCore();
        } else {
            $module = null;
        }

        return $module;
    }

    /**
     * 
     * @param type $filepath
     * @param type $basedir
     * @return type
     */
    protected function getName($filepath, $basedir) {
        $chunk = explode('/', str_replace($basedir . '/', '', $filepath));

        return array_shift($chunk);
    }

    /**
     * 
     * @param type $name
     * @return string
     */
    protected function getPlugin($name) {
        $module = null;
        
        foreach ($this->getPluginList() as $plugin => $meta) {
            $chunk = explode('/', $plugin);
            if ($chunk[0] == $name) {
                //"count($chunk) > 1" in case of Plugin like Hello Dolly
                $path = realpath(
                    WP_PLUGIN_DIR . (count($chunk) > 1 ? "/{$chunk[0]}" : '')
                );

                $module = array(
                    'name'    => $meta['Name'],
                    'version' => $meta['Version'],
                    'path'    => $this->normalizePath($path) . '/'
                );
                break;
            }
        }

        return $module;
    }

    /**
     * 
     * @staticvar type $plugins
     * @return type
     */
    protected function getPluginList() {
        static $plugins = null;

        if (is_null($plugins)) {
            if (!function_exists('get_plugins')) { //load it first
                //this is required to load if cron is on and frontend runs
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugins = get_plugins();
        }

        return $plugins;
    }

    /**
     * 
     * @param type $name
     * @return type
     */
    protected function getTheme($name) {
        $module = null;
        
        foreach ($this->getThemeList() as $meta) {
            if ($meta->get_template() == $name) {
                $path   = $meta->get_template_directory();
                
                $module = array(
                    'name'    => $meta->get('Name'),
                    'version' => $meta->get('Version'),
                    'path'    => $this->normalizePath($path) . '/'
                );
                break;
            }
        }

        return $module;
    }

    /**
     * 
     * @global $wp_version $wp_version
     * @staticvar type $themes
     * @return type
     */
    protected function getThemeList() {
        global $wp_version;
        static $themes = null;

        if (is_null($themes)) {
            if (version_compare($wp_version, '3.4.0', '<')) {
                $themes = get_themes();
            } else {
                $themes = wp_get_themes();
            }
        }

        return $themes;
    }

    /**
     * 
     * @global $wp_version $wp_version
     * @return type
     */
    protected function getCore() {
        global $wp_version;

        return array(
            'name'    => 'WordPress',
            'version' => $wp_version,
            'path'    => $this->config['abspath'] . '/'
        );
    }

    /**
     * 
     * @param type $path
     * @return type
     */
    protected function normalizePath($path) {
        return str_replace('\\', '/', realpath($path));
    }
    
}