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
        add_action('wp_enqueue_scripts', [$this, 'process_styles'], 999999);
    }

    public static function options() {
        $options = get_option('css_debloat_options', []);
        return (object) wp_parse_args($options, [
            'remove_css_all' => false,
            'remove_css_theme' => false,
            'remove_css_plugins' => false,
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
        $dom = $this->get_dom();
        
        if (!$dom) {
            return;
        }
        
        $stylesheets = [];
        foreach ($wp_styles->queue as $handle) {
            $stylesheet = new OptimizeCss\Stylesheet();
            $stylesheet->url = $wp_styles->registered[$handle]->src;
            $stylesheet->id = $handle;
            $stylesheets[] = $stylesheet;
        }
        
        $remover = new RemoveCss($stylesheets, $dom, ob_get_clean());
        $content = $remover->process();
        
        echo $content;
    }
    
    private function should_process() {
        if (is_admin()) {
            return false;
        }
        
        return true;
    }
    
    private function get_dom() {
        ob_start();
        return new \DOMDocument();
    }

    public static function file_system() {
        return new FileSystem();
    }

    public static function delay_load() {
        return new DelayLoad();
    }
}