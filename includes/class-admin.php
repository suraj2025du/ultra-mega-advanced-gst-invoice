<?php
/**
 * Admin functionality class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Admin {
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin bar menu
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        
        // Handle admin AJAX requests
        add_action('wp_ajax_ugst_admin_action', array($this, 'handle_admin_ajax'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post data
        add_action('save_post', array($this, 'save_post_data'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('GST Invoice', 'ultra-gst-invoice'),
            __('GST Invoice', 'ultra-gst-invoice'),
            'manage_options',
            'ugst-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Dashboard', 'ultra-gst-invoice'),
            __('Dashboard', 'ultra-gst-invoice'),
            'manage_options',
            'ugst-dashboard',
            array($this, 'dashboard_page')
        );
        
        // Invoices submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Invoices', 'ultra-gst-invoice'),
            __('Invoices', 'ultra-gst-invoice'),
            'ugst_create_invoices',
            'ugst-invoices',
            array($this, 'invoices_page')
        );
        
        // Customers submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Customers', 'ultra-gst-invoice'),
            __('Customers', 'ultra-gst-invoice'),
            'ugst_manage_customers',
            'ugst-customers',
            array($this, 'customers_page')
        );
        
        // Products submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Products', 'ultra-gst-invoice'),
            __('Products', 'ultra-gst-invoice'),
            'ugst_manage_products',
            'ugst-products',
            array($this, 'products_page')
        );
        
        // Inventory submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Inventory', 'ultra-gst-invoice'),
            __('Inventory', 'ultra-gst-invoice'),
            'ugst_manage_products',
            'ugst-inventory',
            array($this, 'inventory_page')
        );
        
        // Payments submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Payments', 'ultra-gst-invoice'),
            __('Payments', 'ultra-gst-invoice'),
            'ugst_view_reports',
            'ugst-payments',
            array($this, 'payments_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Reports', 'ultra-gst-invoice'),
            __('Reports', 'ultra-gst-invoice'),
            'ugst_view_reports',
            'ugst-reports',
            array($this, 'reports_page')
        );
        
        // Coupons submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Coupons', 'ultra-gst-invoice'),
            __('Coupons', 'ultra-gst-invoice'),
            'ugst_manage_settings',
            'ugst-coupons',
            array($this, 'coupons_page')
        );
        
        // Subscriptions submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Subscriptions', 'ultra-gst-invoice'),
            __('Subscriptions', 'ultra-gst-invoice'),
            'manage_options',
            'ugst-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Settings', 'ultra-gst-invoice'),
            __('Settings', 'ultra-gst-invoice'),
            'ugst_manage_settings',
            'ugst-settings',
            array($this, 'settings_page')
        );
        
        // SEO submenu
        add_submenu_page(
            'ugst-dashboard',
            __('SEO', 'ultra-gst-invoice'),
            __('SEO', 'ultra-gst-invoice'),
            'manage_options',
            'ugst-seo',
            array($this, 'seo_page')
        );
        
        // Help submenu
        add_submenu_page(
            'ugst-dashboard',
            __('Help', 'ultra-gst-invoice'),
            __('Help', 'ultra-gst-invoice'),
            'read',
            'ugst-help',
            array($this, 'help_page')
        );
    }
    
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('ugst_view_reports')) {
            return;
        }
        
        $wp_admin_bar->add_menu(array(
            'id' => 'ugst-menu',
            'title' => __('GST Invoice', 'ultra-gst-invoice'),
            'href' => admin_url('admin.php?page=ugst-dashboard')
        ));
        
        $wp_admin_bar->add_menu(array(
            'parent' => 'ugst-menu',
            'id' => 'ugst-new-invoice',
            'title' => __('New Invoice', 'ultra-gst-invoice'),
            'href' => admin_url('admin.php?page=ugst-invoices&action=add')
        ));
        
        $wp_admin_bar->add_menu(array(
            'parent' => 'ugst-menu',
            'id' => 'ugst-dashboard',
            'title' => __('Dashboard', 'ultra-gst-invoice'),
            'href' => admin_url('admin.php?page=ugst-dashboard')
        ));
    }
    
    public function dashboard_page() {
        include UGST_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    public function invoices_page() {
        include UGST_PLUGIN_DIR . 'admin/invoices.php';
    }
    
    public function customers_page() {
        include UGST_PLUGIN_DIR . 'admin/customers.php';
    }
    
    public function products_page() {
        include UGST_PLUGIN_DIR . 'admin/products.php';
    }
    
    public function inventory_page() {
        include UGST_PLUGIN_DIR . 'admin/inventory.php';
    }
    
    public function payments_page() {
        include UGST_PLUGIN_DIR . 'admin/payments.php';
    }
    
    public function reports_page() {
        include UGST_PLUGIN_DIR . 'admin/reports.php';
    }
    
    public function coupons_page() {
        include UGST_PLUGIN_DIR . 'admin/coupons.php';
    }
    
    public function subscriptions_page() {
        include UGST_PLUGIN_DIR . 'admin/subscriptions.php';
    }
    
    public function settings_page() {
        include UGST_PLUGIN_DIR . 'admin/settings.php';
    }
    
    public function seo_page() {
        include UGST_PLUGIN_DIR . 'admin/seo.php';
    }
    
    public function help_page() {
        include UGST_PLUGIN_DIR . 'admin/help.php';
    }
    
    public function handle_admin_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        $action = sanitize_text_field($_POST['ugst_action']);
        
        switch ($action) {
            case 'get_customer_data':
                $this->ajax_get_customer_data();
                break;
                
            case 'get_product_data':
                $this->ajax_get_product_data();
                break;
                
            case 'save_invoice':
                $this->ajax_save_invoice();
                break;
                
            case 'delete_invoice':
                $this->ajax_delete_invoice();
                break;
                
            case 'generate_invoice_pdf':
                $this->ajax_generate_invoice_pdf();
                break;
                
            case 'send_invoice_email':
                $this->ajax_send_invoice_email();
                break;
                
            case 'apply_coupon':
                $this->ajax_apply_coupon();
                break;
                
            case 'get_dashboard_stats':
                $this->ajax_get_dashboard_stats();
                break;
                
            default:
                wp_die(__('Invalid action', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_get_customer_data() {
        $customer_id = intval($_POST['customer_id']);
        
        global $wpdb;
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_customers WHERE id = %d",
            $customer_id
        ));
        
        if ($customer) {
            wp_send_json_success($customer);
        } else {
            wp_send_json_error(__('Customer not found', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_get_product_data() {
        $product_id = intval($_POST['product_id']);
        
        global $wpdb;
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_products WHERE id = %d",
            $product_id
        ));
        
        if ($product) {
            wp_send_json_success($product);
        } else {
            wp_send_json_error(__('Product not found', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_save_invoice() {
        // Validate and sanitize input data
        $invoice_data = array(
            'customer_id' => intval($_POST['customer_id']),
            'invoice_date' => sanitize_text_field($_POST['invoice_date']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'terms' => sanitize_textarea_field($_POST['terms'])
        );
        
        $items = json_decode(stripslashes($_POST['items']), true);
        
        // Create invoice using UGST_Invoice class
        $invoice = new UGST_Invoice();
        $result = $invoice->create_invoice($invoice_data, $items);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Invoice saved successfully', 'ultra-gst-invoice'),
                'invoice_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to save invoice', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_delete_invoice() {
        $invoice_id = intval($_POST['invoice_id']);
        
        $invoice = new UGST_Invoice();
        $result = $invoice->delete_invoice($invoice_id);
        
        if ($result) {
            wp_send_json_success(__('Invoice deleted successfully', 'ultra-gst-invoice'));
        } else {
            wp_send_json_error(__('Failed to delete invoice', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_generate_invoice_pdf() {
        $invoice_id = intval($_POST['invoice_id']);
        
        $invoice = new UGST_Invoice();
        $pdf_url = $invoice->generate_pdf($invoice_id);
        
        if ($pdf_url) {
            wp_send_json_success(array(
                'pdf_url' => $pdf_url
            ));
        } else {
            wp_send_json_error(__('Failed to generate PDF', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_send_invoice_email() {
        $invoice_id = intval($_POST['invoice_id']);
        $email = sanitize_email($_POST['email']);
        
        $invoice = new UGST_Invoice();
        $result = $invoice->send_email($invoice_id, $email);
        
        if ($result) {
            wp_send_json_success(__('Invoice sent successfully', 'ultra-gst-invoice'));
        } else {
            wp_send_json_error(__('Failed to send invoice', 'ultra-gst-invoice'));
        }
    }
    
    private function ajax_apply_coupon() {
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $total_amount = floatval($_POST['total_amount']);
        
        $coupon = new UGST_Coupons();
        $result = $coupon->apply_coupon($coupon_code, $total_amount);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function ajax_get_dashboard_stats() {
        global $wpdb;
        
        // Get today's stats
        $today = date('Y-m-d');
        $this_month = date('Y-m');
        
        $stats = array(
            'today_sales' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM {$wpdb->prefix}ugst_invoices WHERE DATE(created_at) = %s AND status = 'paid'",
                $today
            )),
            'month_sales' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM {$wpdb->prefix}ugst_invoices WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s AND status = 'paid'",
                $this_month
            )),
            'total_customers' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_customers WHERE status = 'active'"
            ),
            'pending_invoices' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE status = 'pending'"
            ),
            'low_stock_products' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_products WHERE stock_quantity <= min_stock_level AND status = 'active'"
            )
        );
        
        wp_send_json_success($stats);
    }
    
    public function display_admin_notices() {
        // Check for plugin updates
        $this->check_plugin_updates();
        
        // Check for low stock alerts
        $this->check_low_stock_alerts();
        
        // Check for pending payments
        $this->check_pending_payments();
    }
    
    private function check_plugin_updates() {
        $current_version = get_option('ugst_version', '0.0.0');
        
        if (version_compare($current_version, UGST_VERSION, '<')) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . sprintf(
                __('GST Invoice plugin has been updated to version %s. Please check the changelog for new features.', 'ultra-gst-invoice'),
                UGST_VERSION
            ) . '</p>';
            echo '</div>';
            
            update_option('ugst_version', UGST_VERSION);
        }
    }
    
    private function check_low_stock_alerts() {
        global $wpdb;
        
        $low_stock_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_products WHERE stock_quantity <= min_stock_level AND status = 'active'"
        );
        
        if ($low_stock_count > 0) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                _n(
                    'You have %d product with low stock.',
                    'You have %d products with low stock.',
                    $low_stock_count,
                    'ultra-gst-invoice'
                ),
                $low_stock_count
            ) . ' <a href="' . admin_url('admin.php?page=ugst-inventory') . '">' . __('View Inventory', 'ultra-gst-invoice') . '</a></p>';
            echo '</div>';
        }
    }
    
    private function check_pending_payments() {
        global $wpdb;
        
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE status = 'pending' AND due_date < CURDATE()"
        );
        
        if ($pending_count > 0) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf(
                _n(
                    'You have %d overdue invoice.',
                    'You have %d overdue invoices.',
                    $pending_count,
                    'ultra-gst-invoice'
                ),
                $pending_count
            ) . ' <a href="' . admin_url('admin.php?page=ugst-invoices&status=overdue') . '">' . __('View Overdue Invoices', 'ultra-gst-invoice') . '</a></p>';
            echo '</div>';
        }
    }
    
    public function add_meta_boxes() {
        // Add meta boxes for custom post types if needed
    }
    
    public function save_post_data($post_id) {
        // Save custom post data if needed
    }
}