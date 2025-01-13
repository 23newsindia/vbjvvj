<?php

namespace Sphere\Debloat;

class Plugin {
    private static $instance = null;
    private $settings;
    
    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }
        
        self::$instance = $this;
    }
    
    public function init() {
        // Initialize the admin settings
        if (is_admin()) {
            $this->settings = new Admin\Settings();
        }

        // Initialize the plugin components
        add_action('wp_print_styles', [$this, 'process_styles'], 999999);
    }

    public static function options() {
        $options = get_option('css_debloat_options', []);
        return (object) wp_parse_args($options, [
            'remove_css_all' => true, // Set to true by default
            'remove_css_theme' => true,
            'remove_css_plugins' => true,
            'remove_css_excludes' => '',
            'allow_css_selectors' => '',
            'allow_css_conditionals' => false,
            'allow_conditionals_data' => [],
            'delay_css_type' => 'onload'
        ]);
    }
    
    public function process_styles() {
        if (!$this->should_process()) {
            return;
        }
        
        global $wp_styles;
        
        if (!is_object($wp_styles)) {
            return;
        }

        // Start output buffering
        ob_start();
        
        // Create DOM document
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        
        $stylesheets = [];
        foreach ($wp_styles->queue as $handle) {
            if (!isset($wp_styles->registered[$handle])) {
                continue;
            }

            $style = $wp_styles->registered[$handle];
            
            // Skip if no source
            if (!$style->src) {
                continue;
            }

            $stylesheet = new OptimizeCss\Stylesheet();
            $stylesheet->url = $style->src;
            $stylesheet->id = $handle;
            
            // Convert relative URLs to absolute
            if (strpos($stylesheet->url, '//') === false) {
                if (strpos($stylesheet->url, '/') === 0) {
                    $stylesheet->url = site_url($stylesheet->url);
                } else {
                    $stylesheet->url = site_url('/' . $stylesheet->url);
                }
            }
            
            $stylesheets[] = $stylesheet;
            
            // Dequeue the original stylesheet
            wp_dequeue_style($handle);
        }
        
        if (empty($stylesheets)) {
            return;
        }

        // Process the stylesheets
        $remover = new RemoveCss($stylesheets, $dom, '');
        $remover->process();

        // Re-enqueue optimized stylesheets
        foreach ($stylesheets as $sheet) {
            if (!empty($sheet->content)) {
                wp_enqueue_style(
                    'optimized-' . $sheet->id,
                    false,
                    [],
                    null
                );
                wp_add_inline_style('optimized-' . $sheet->id, $sheet->content);
            } else {
                // If optimization failed, re-enqueue original
                wp_enqueue_style($sheet->id);
            }
        }

        libxml_clear_errors();
    }
    
    private function should_process() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return false;
        }
        
        // Don't process on login/register pages
        if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) {
            return false;
        }

        return true;
    }

    public static function file_system() {
        return new FileSystem();
    }

    public static function delay_load() {
        return new DelayLoad();
    }
}
