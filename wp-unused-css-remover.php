<?php
/**
 * Plugin Name: WP Unused CSS Remover
 * Plugin URI: https://example.com/plugins/wp-unused-css-remover
 * Description: A WordPress plugin to remove unused CSS from your website
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-unused-css-remover
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// Initialize the plugin
function wp_unused_css_remover_init() {
    // Initialize classes
    $plugin = new Sphere\Debloat\Plugin();
    $plugin->init();
}
add_action('plugins_loaded', 'wp_unused_css_remover_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_unused_css_remover_activate');
function wp_unused_css_remover_activate() {
    // Add activation code here
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_unused_css_remover_deactivate');
function wp_unused_css_remover_deactivate() {
    // Add deactivation code here
    flush_rewrite_rules();
}