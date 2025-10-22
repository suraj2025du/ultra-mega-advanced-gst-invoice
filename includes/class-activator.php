<?php
/**
 * Plugin Activator Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Activator {
    
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create default pages
        self::create_pages();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_directories();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Customers table
        $table_customers = $wpdb->prefix . 'ugst_customers';
        $sql_customers = "CREATE TABLE $table_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_name varchar(255) NOT NULL,
            gstin varchar(15) DEFAULT NULL,
            contact_person varchar(100) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            phone varchar(20) DEFAULT NULL,
            address text DEFAULT NULL,
            city varchar(50) DEFAULT NULL,
            state varchar(50) DEFAULT NULL,
            pincode varchar(10) DEFAULT NULL,
            country varchar(50) DEFAULT 'India',
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY gstin (gstin),
            KEY status (status)
        ) $charset_collate;";
        
        // Invoices table
        $table_invoices = $wpdb->prefix . 'ugst_invoices';
        $sql_invoices = "CREATE TABLE $table_invoices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_number varchar(50) NOT NULL,
            customer_id bigint(20) NOT NULL,
            invoice_date date NOT NULL,
            due_date date NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            cgst decimal(10,2) NOT NULL DEFAULT 0.00,
            sgst decimal(10,2) NOT NULL DEFAULT 0.00,
            igst decimal(10,2) NOT NULL DEFAULT 0.00,
            total_tax decimal(10,2) NOT NULL DEFAULT 0.00,
            discount decimal(10,2) NOT NULL DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            paid_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'draft',
            notes text DEFAULT NULL,
            terms text DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY invoice_date (invoice_date)
        ) $charset_collate;";
        
        // Invoice Items table
        $table_invoice_items = $wpdb->prefix . 'ugst_invoice_items';
        $sql_invoice_items = "CREATE TABLE $table_invoice_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            product_id bigint(20) DEFAULT NULL,
            description text NOT NULL,
            hsn_code varchar(20) DEFAULT NULL,
            quantity decimal(10,2) NOT NULL DEFAULT 1.00,
            unit varchar(20) DEFAULT 'Nos',
            rate decimal(10,2) NOT NULL DEFAULT 0.00,
            discount decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Products table
        $table_products = $wpdb->prefix . 'ugst_products';
        $sql_products = "CREATE TABLE $table_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            sku varchar(100) DEFAULT NULL,
            hsn_code varchar(20) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            unit varchar(20) DEFAULT 'Nos',
            purchase_price decimal(10,2) DEFAULT 0.00,
            selling_price decimal(10,2) DEFAULT 0.00,
            tax_rate decimal(5,2) DEFAULT 0.00,
            stock_quantity decimal(10,2) DEFAULT 0.00,
            min_stock_level decimal(10,2) DEFAULT 0.00,
            barcode varchar(100) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            KEY hsn_code (hsn_code),
            KEY category (category),
            KEY status (status)
        ) $charset_collate;";
        
        // Payments table
        $table_payments = $wpdb->prefix . 'ugst_payments';
        $sql_payments = "CREATE TABLE $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            payment_date datetime NOT NULL,
            status varchar(20) DEFAULT 'completed',
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            KEY customer_id (customer_id),
            KEY transaction_id (transaction_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Coupons table
        $table_coupons = $wpdb->prefix . 'ugst_coupons';
        $sql_coupons = "CREATE TABLE $table_coupons (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text DEFAULT NULL,
            discount_type varchar(20) NOT NULL DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
            minimum_amount decimal(10,2) DEFAULT 0.00,
            maximum_discount decimal(10,2) DEFAULT NULL,
            usage_limit int(11) DEFAULT NULL,
            used_count int(11) DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            applicable_products text DEFAULT NULL,
            applicable_customers text DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY status (status),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        // Subscriptions table
        $table_subscriptions = $wpdb->prefix . 'ugst_subscriptions';
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_name varchar(50) NOT NULL,
            plan_price decimal(10,2) NOT NULL,
            billing_cycle varchar(20) NOT NULL DEFAULT 'monthly',
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(20) DEFAULT 'active',
            payment_method varchar(50) DEFAULT NULL,
            subscription_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY subscription_id (subscription_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_customers);
        dbDelta($sql_invoices);
        dbDelta($sql_invoice_items);
        dbDelta($sql_products);
        dbDelta($sql_payments);
        dbDelta($sql_coupons);
        dbDelta($sql_subscriptions);
    }
    
    private static function create_pages() {
        // Customer Dashboard Page
        $dashboard_page = array(
            'post_title'    => 'Customer Dashboard',
            'post_content'  => '[ugst_customer_dashboard]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'customer-dashboard'
        );
        
        $dashboard_id = wp_insert_post($dashboard_page);
        update_option('ugst_customer_dashboard_page_id', $dashboard_id);
        
        // Invoice Portal Page
        $invoice_page = array(
            'post_title'    => 'Invoice Portal',
            'post_content'  => '[ugst_invoice_portal]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'invoice-portal'
        );
        
        $invoice_id = wp_insert_post($invoice_page);
        update_option('ugst_invoice_portal_page_id', $invoice_id);
        
        // Payment Page
        $payment_page = array(
            'post_title'    => 'Payment',
            'post_content'  => '[ugst_payment_form]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'payment'
        );
        
        $payment_id = wp_insert_post($payment_page);
        update_option('ugst_payment_page_id', $payment_id);
    }
    
    private static function set_default_options() {
        // General settings
        update_option('ugst_company_name', get_bloginfo('name'));
        update_option('ugst_company_email', get_option('admin_email'));
        update_option('ugst_invoice_prefix', 'INV-');
        update_option('ugst_invoice_start_number', 1);
        update_option('ugst_currency', 'INR');
        update_option('ugst_currency_symbol', '₹');
        update_option('ugst_date_format', 'd/m/Y');
        update_option('ugst_time_format', 'H:i');
        
        // Tax settings
        update_option('ugst_default_tax_rate', 18);
        update_option('ugst_tax_inclusive', 'no');
        
        // Email settings
        update_option('ugst_email_from_name', get_bloginfo('name'));
        update_option('ugst_email_from_email', get_option('admin_email'));
        
        // SEO settings
        update_option('ugst_enable_seo', 'yes');
        update_option('ugst_enable_schema', 'yes');
        update_option('ugst_enable_sitemap', 'yes');
        
        // Subscription plans
        $plans = array(
            'basic' => array(
                'name' => 'Basic Plan',
                'price' => 999,
                'features' => array(
                    'invoices_limit' => 100,
                    'customers_limit' => 50,
                    'products_limit' => 100,
                    'storage_limit' => '1GB',
                    'support' => 'email'
                )
            ),
            'pro' => array(
                'name' => 'Pro Plan',
                'price' => 1999,
                'features' => array(
                    'invoices_limit' => 500,
                    'customers_limit' => 200,
                    'products_limit' => 500,
                    'storage_limit' => '5GB',
                    'support' => 'priority'
                )
            ),
            'enterprise' => array(
                'name' => 'Enterprise Plan',
                'price' => 4999,
                'features' => array(
                    'invoices_limit' => -1, // unlimited
                    'customers_limit' => -1,
                    'products_limit' => -1,
                    'storage_limit' => '50GB',
                    'support' => '24/7'
                )
            )
        );
        
        update_option('ugst_subscription_plans', $plans);
    }
    
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $ugst_dir = $upload_dir['basedir'] . '/ugst-invoices';
        
        if (!file_exists($ugst_dir)) {
            wp_mkdir_p($ugst_dir);
        }
        
        // Create subdirectories
        $subdirs = array('invoices', 'receipts', 'reports', 'backups');
        
        foreach ($subdirs as $subdir) {
            $dir_path = $ugst_dir . '/' . $subdir;
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
            }
        }
        
        // Create .htaccess file for security
        $htaccess_content = "Options -Indexes\n<Files *.php>\ndeny from all\n</Files>";
        file_put_contents($ugst_dir . '/.htaccess', $htaccess_content);
    }
    
    private static function schedule_cron_jobs() {
        // Daily maintenance
        if (!wp_next_scheduled('ugst_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'ugst_daily_maintenance');
        }
        
        // Weekly reports
        if (!wp_next_scheduled('ugst_weekly_reports')) {
            wp_schedule_event(time(), 'weekly', 'ugst_weekly_reports');
        }
        
        // Monthly billing
        if (!wp_next_scheduled('ugst_monthly_billing')) {
            wp_schedule_event(time(), 'monthly', 'ugst_monthly_billing');
        }
    }
}