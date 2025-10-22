<?php
/**
 * Plugin Deactivator Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Deactivator {
    
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any cached data
        self::clear_cache();
        
        // Log deactivation
        self::log_deactivation();
    }
    
    private static function clear_scheduled_events() {
        // Clear all scheduled cron jobs
        wp_clear_scheduled_hook('ugst_daily_maintenance');
        wp_clear_scheduled_hook('ugst_weekly_reports');
        wp_clear_scheduled_hook('ugst_monthly_billing');
        wp_clear_scheduled_hook('ugst_backup_database');
        wp_clear_scheduled_hook('ugst_cleanup_temp_files');
        wp_clear_scheduled_hook('ugst_send_reminders');
        wp_clear_scheduled_hook('ugst_update_exchange_rates');
        wp_clear_scheduled_hook('ugst_generate_reports');
    }
    
    private static function clear_cache() {
        // Clear WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear object cache
        if (function_exists('wp_cache_delete_group')) {
            wp_cache_delete_group('ugst_invoices');
            wp_cache_delete_group('ugst_customers');
            wp_cache_delete_group('ugst_products');
            wp_cache_delete_group('ugst_reports');
        }
        
        // Clear transients
        delete_transient('ugst_dashboard_stats');
        delete_transient('ugst_monthly_sales');
        delete_transient('ugst_top_customers');
        delete_transient('ugst_low_stock_products');
        delete_transient('ugst_overdue_invoices');
    }
    
    private static function log_deactivation() {
        // Log plugin deactivation for debugging
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_email' => wp_get_current_user()->user_email,
            'site_url' => get_site_url(),
            'plugin_version' => UGST_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
        
        update_option('ugst_last_deactivation', $log_data);
    }
}