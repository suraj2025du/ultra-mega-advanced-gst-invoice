<?php
/**
 * Core functionality class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Core {
    
    public function init() {
        // Register post types
        add_action('init', array($this, 'register_post_types'));
        
        // Register taxonomies
        add_action('init', array($this, 'register_taxonomies'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Add custom user roles
        add_action('init', array($this, 'add_custom_roles'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add cron job handlers
        add_action('ugst_daily_maintenance', array($this, 'daily_maintenance'));
        add_action('ugst_weekly_reports', array($this, 'weekly_reports'));
        add_action('ugst_monthly_billing', array($this, 'monthly_billing'));
    }
    
    public function register_post_types() {
        // Register Invoice post type
        register_post_type('ugst_invoice', array(
            'labels' => array(
                'name' => __('Invoices', 'ultra-gst-invoice'),
                'singular_name' => __('Invoice', 'ultra-gst-invoice'),
                'add_new' => __('Add New Invoice', 'ultra-gst-invoice'),
                'add_new_item' => __('Add New Invoice', 'ultra-gst-invoice'),
                'edit_item' => __('Edit Invoice', 'ultra-gst-invoice'),
                'new_item' => __('New Invoice', 'ultra-gst-invoice'),
                'view_item' => __('View Invoice', 'ultra-gst-invoice'),
                'search_items' => __('Search Invoices', 'ultra-gst-invoice'),
                'not_found' => __('No invoices found', 'ultra-gst-invoice'),
                'not_found_in_trash' => __('No invoices found in trash', 'ultra-gst-invoice')
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false
        ));
        
        // Register Product post type
        register_post_type('ugst_product', array(
            'labels' => array(
                'name' => __('Products', 'ultra-gst-invoice'),
                'singular_name' => __('Product', 'ultra-gst-invoice'),
                'add_new' => __('Add New Product', 'ultra-gst-invoice'),
                'add_new_item' => __('Add New Product', 'ultra-gst-invoice'),
                'edit_item' => __('Edit Product', 'ultra-gst-invoice'),
                'new_item' => __('New Product', 'ultra-gst-invoice'),
                'view_item' => __('View Product', 'ultra-gst-invoice'),
                'search_items' => __('Search Products', 'ultra-gst-invoice'),
                'not_found' => __('No products found', 'ultra-gst-invoice'),
                'not_found_in_trash' => __('No products found in trash', 'ultra-gst-invoice')
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false
        ));
    }
    
    public function register_taxonomies() {
        // Register Product Category taxonomy
        register_taxonomy('ugst_product_category', 'ugst_product', array(
            'labels' => array(
                'name' => __('Product Categories', 'ultra-gst-invoice'),
                'singular_name' => __('Product Category', 'ultra-gst-invoice'),
                'search_items' => __('Search Categories', 'ultra-gst-invoice'),
                'all_items' => __('All Categories', 'ultra-gst-invoice'),
                'parent_item' => __('Parent Category', 'ultra-gst-invoice'),
                'parent_item_colon' => __('Parent Category:', 'ultra-gst-invoice'),
                'edit_item' => __('Edit Category', 'ultra-gst-invoice'),
                'update_item' => __('Update Category', 'ultra-gst-invoice'),
                'add_new_item' => __('Add New Category', 'ultra-gst-invoice'),
                'new_item_name' => __('New Category Name', 'ultra-gst-invoice'),
                'menu_name' => __('Categories', 'ultra-gst-invoice')
            ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => false,
            'show_admin_column' => false,
            'query_var' => true,
            'rewrite' => false
        ));
    }
    
    public function enqueue_scripts() {
        // Frontend CSS
        wp_enqueue_style(
            'ugst-frontend-style',
            UGST_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            UGST_VERSION
        );
        
        // Frontend JS
        wp_enqueue_script(
            'ugst-frontend-script',
            UGST_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            UGST_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('ugst-frontend-script', 'ugst_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ugst_nonce'),
            'currency_symbol' => get_option('ugst_currency_symbol', '₹')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'ugst') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'ugst-admin-style',
            UGST_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UGST_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'ugst-admin-script',
            UGST_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-autocomplete'),
            UGST_VERSION,
            true
        );
        
        // Chart.js for reports
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize admin script
        wp_localize_script('ugst-admin-script', 'ugst_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ugst_admin_nonce'),
            'currency_symbol' => get_option('ugst_currency_symbol', '₹'),
            'date_format' => get_option('ugst_date_format', 'd/m/Y'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'ultra-gst-invoice'),
                'loading' => __('Loading...', 'ultra-gst-invoice'),
                'error' => __('An error occurred. Please try again.', 'ultra-gst-invoice'),
                'success' => __('Operation completed successfully.', 'ultra-gst-invoice')
            )
        ));
    }
    
    public function register_shortcodes() {
        add_shortcode('ugst_customer_dashboard', array($this, 'customer_dashboard_shortcode'));
        add_shortcode('ugst_invoice_portal', array($this, 'invoice_portal_shortcode'));
        add_shortcode('ugst_payment_form', array($this, 'payment_form_shortcode'));
        add_shortcode('ugst_invoice_view', array($this, 'invoice_view_shortcode'));
        add_shortcode('ugst_product_catalog', array($this, 'product_catalog_shortcode'));
    }
    
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to access your dashboard.', 'ultra-gst-invoice') . '</p>';
        }
        
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/customer-dashboard.php';
        return ob_get_clean();
    }
    
    public function invoice_portal_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to access invoices.', 'ultra-gst-invoice') . '</p>';
        }
        
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/invoice-portal.php';
        return ob_get_clean();
    }
    
    public function payment_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'invoice_id' => 0
        ), $atts);
        
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/payment-form.php';
        return ob_get_clean();
    }
    
    public function invoice_view_shortcode($atts) {
        $atts = shortcode_atts(array(
            'invoice_id' => 0
        ), $atts);
        
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/invoice-view.php';
        return ob_get_clean();
    }
    
    public function product_catalog_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 12
        ), $atts);
        
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/product-catalog.php';
        return ob_get_clean();
    }
    
    public function add_custom_roles() {
        // Add customer role
        add_role('ugst_customer', __('GST Customer', 'ultra-gst-invoice'), array(
            'read' => true,
            'ugst_view_invoices' => true,
            'ugst_make_payments' => true
        ));
        
        // Add staff role
        add_role('ugst_staff', __('GST Staff', 'ultra-gst-invoice'), array(
            'read' => true,
            'ugst_manage_customers' => true,
            'ugst_create_invoices' => true,
            'ugst_manage_products' => true,
            'ugst_view_reports' => true
        ));
        
        // Add manager role
        add_role('ugst_manager', __('GST Manager', 'ultra-gst-invoice'), array(
            'read' => true,
            'ugst_manage_customers' => true,
            'ugst_create_invoices' => true,
            'ugst_manage_products' => true,
            'ugst_view_reports' => true,
            'ugst_manage_settings' => true,
            'ugst_manage_staff' => true
        ));
    }
    
    public function register_rest_routes() {
        register_rest_route('ugst/v1', '/invoices', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_invoices_api'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        register_rest_route('ugst/v1', '/invoices/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_invoice_api'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        register_rest_route('ugst/v1', '/customers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_customers_api'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        register_rest_route('ugst/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products_api'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
    }
    
    public function check_api_permissions() {
        return current_user_can('ugst_view_reports') || current_user_can('manage_options');
    }
    
    public function get_invoices_api($request) {
        global $wpdb;
        
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 10;
        $offset = ($page - 1) * $per_page;
        
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_invoices ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        return rest_ensure_response($invoices);
    }
    
    public function get_invoice_api($request) {
        global $wpdb;
        
        $invoice_id = $request->get_param('id');
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_invoices WHERE id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            return new WP_Error('invoice_not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Get invoice items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_invoice_items WHERE invoice_id = %d",
            $invoice_id
        ));
        
        $invoice->items = $items;
        
        return rest_ensure_response($invoice);
    }
    
    public function get_customers_api($request) {
        global $wpdb;
        
        $customers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ugst_customers WHERE status = 'active' ORDER BY company_name ASC"
        );
        
        return rest_ensure_response($customers);
    }
    
    public function get_products_api($request) {
        global $wpdb;
        
        $products = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ugst_products WHERE status = 'active' ORDER BY name ASC"
        );
        
        return rest_ensure_response($products);
    }
    
    public function daily_maintenance() {
        // Clean up expired sessions
        $this->cleanup_expired_sessions();
        
        // Update stock levels
        $this->update_stock_levels();
        
        // Send low stock alerts
        $this->send_low_stock_alerts();
        
        // Clean up temporary files
        $this->cleanup_temp_files();
    }
    
    public function weekly_reports() {
        // Generate weekly sales report
        $this->generate_weekly_sales_report();
        
        // Send weekly summary emails
        $this->send_weekly_summary_emails();
    }
    
    public function monthly_billing() {
        // Process subscription renewals
        $this->process_subscription_renewals();
        
        // Generate monthly invoices
        $this->generate_monthly_invoices();
        
        // Send billing reminders
        $this->send_billing_reminders();
    }
    
    private function cleanup_expired_sessions() {
        // Implementation for cleaning up expired sessions
    }
    
    private function update_stock_levels() {
        // Implementation for updating stock levels
    }
    
    private function send_low_stock_alerts() {
        // Implementation for sending low stock alerts
    }
    
    private function cleanup_temp_files() {
        // Implementation for cleaning up temporary files
    }
    
    private function generate_weekly_sales_report() {
        // Implementation for generating weekly sales report
    }
    
    private function send_weekly_summary_emails() {
        // Implementation for sending weekly summary emails
    }
    
    private function process_subscription_renewals() {
        // Implementation for processing subscription renewals
    }
    
    private function generate_monthly_invoices() {
        // Implementation for generating monthly invoices
    }
    
    private function send_billing_reminders() {
        // Implementation for sending billing reminders
    }
}