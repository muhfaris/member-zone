<?php

namespace MemberZone;

/*
 * Plugin Name: Member Zone
 * Description: A comprehensive membership plugin with customizable dashboard and payment integration
 * Version: 1.0.0
 * Author: Muh Faris
 * Author URI: https://muhfaris.com
 * Text Domain: memberzone
 */

ob_start();

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly
}

class Plugin
{
    public function __construct()
    {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Action hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Include other plugin files
        $this->include_files();
    }

    public function activate()
    {
        // Activation logic (e.g., create database tables)
        // Uncomment and add activation code here if needed
        // Clear the permalinks after the post type has been registered.
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        // Deactivation logic
        // Uncomment and add deactivation code here if needed
        // Clear the permalinks after the post type has been registered.
        flush_rewrite_rules();
    }

    public function init()
    {
        // Initialize plugin components
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('memberzone', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function include_files()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/registration.php';
        require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
        require_once plugin_dir_path(__FILE__) . 'includes/members.php';
        require_once plugin_dir_path(__FILE__) . 'includes/membership-levels.php';
        require_once plugin_dir_path(__FILE__) . 'includes/email.php';
        require_once plugin_dir_path(__FILE__) . 'includes/login.php';
    }

    public function enqueue_scripts()
    {
        // Correctly enqueue the CSS file
        wp_enqueue_style(
            'memberzone-registration',
            plugin_dir_url(__FILE__) . 'assets/css/registration.css',
            array(),
            null
        );

        // wp_enqueue_script(
        //     'memberzone-registration',
        //     plugin_dir_url(__FILE__) . 'assets/js/registration.js',
        //     array('jquery'),
        //     '1.0',
        //     true
        // );

        wp_localize_script('memberzone-registration', 'memberzone_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('memberzone_register_nonce')
        ));

        // Correctly enqueue the CSS file
        wp_enqueue_style(
            'memberzone-membership-levels',
            plugin_dir_url(__FILE__) . 'assets/css/membership-levels.css',
            array(),
            null
        );

        wp_enqueue_script(
            'memberzone-membership-levels',
            plugin_dir_url(__FILE__) . 'assets/js/membership-levels.js',
            array('jquery'),
            '1.0',
            true,
        );

        wp_localize_script('memberzone-membership-levels', 'memberzone_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('memberzone_register_nonce')
        ));
    }
}

// Instantiate the plugin class
new Plugin();
