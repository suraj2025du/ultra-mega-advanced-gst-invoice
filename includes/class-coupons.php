<?php
/**
 * Coupon System Class - Advanced Coupon Management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Coupons {
    
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_ugst_create_coupon', array($this, 'ajax_create_coupon'));
        add_action('wp_ajax_ugst_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_ugst_apply_coupon', array($this, 'ajax_apply_coupon'));
        add_action('wp_ajax_ugst_bulk_generate_coupons', array($this, 'ajax_bulk_generate_coupons'));
        
        // Add shortcodes
        add_shortcode('ugst_coupon_form', array($this, 'coupon_form_shortcode'));
    }
    
    /**
     * Create a new coupon
     */
    public function create_coupon($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['code']) || empty($data['discount_type']) || empty($data['discount_value'])) {
            return array('success' => false, 'message' => __('Required fields are missing', 'ultra-gst-invoice'));
        }
        
        // Check if coupon code already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ugst_coupons WHERE code = %s",
            $data['code']
        ));
        
        if ($existing) {
            return array('success' => false, 'message' => __('Coupon code already exists', 'ultra-gst-invoice'));
        }
        
        // Prepare coupon data
        $coupon_data = array(
            'code' => strtoupper(sanitize_text_field($data['code'])),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'discount_value' => floatval($data['discount_value']),
            'minimum_amount' => floatval($data['minimum_amount'] ?? 0),
            'maximum_discount' => !empty($data['maximum_discount']) ? floatval($data['maximum_discount']) : null,
            'usage_limit' => !empty($data['usage_limit']) ? intval($data['usage_limit']) : null,
            'used_count' => 0,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'applicable_products' => !empty($data['applicable_products']) ? json_encode($data['applicable_products']) : null,
            'applicable_customers' => !empty($data['applicable_customers']) ? json_encode($data['applicable_customers']) : null,
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'ugst_coupons',
            $coupon_data,
            array('%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            return array(
                'success' => true,
                'message' => __('Coupon created successfully', 'ultra-gst-invoice'),
                'coupon_id' => $wpdb->insert_id
            );
        } else {
            return array('success' => false, 'message' => __('Failed to create coupon', 'ultra-gst-invoice'));
        }
    }
    
    /**
     * Validate coupon code
     */
    public function validate_coupon($code, $customer_id = null, $total_amount = 0, $products = array()) {
        global $wpdb;
        
        // Get coupon data
        $coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_coupons WHERE code = %s AND status = 'active'",
            strtoupper($code)
        ));
        
        if (!$coupon) {
            return array('valid' => false, 'message' => __('Invalid coupon code', 'ultra-gst-invoice'));
        }
        
        // Check if coupon is expired
        if ($coupon->end_date && strtotime($coupon->end_date) < time()) {
            return array('valid' => false, 'message' => __('Coupon has expired', 'ultra-gst-invoice'));
        }
        
        // Check if coupon is not yet active
        if ($coupon->start_date && strtotime($coupon->start_date) > time()) {
            return array('valid' => false, 'message' => __('Coupon is not yet active', 'ultra-gst-invoice'));
        }
        
        // Check usage limit
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return array('valid' => false, 'message' => __('Coupon usage limit exceeded', 'ultra-gst-invoice'));
        }
        
        // Check minimum amount
        if ($coupon->minimum_amount > 0 && $total_amount < $coupon->minimum_amount) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __('Minimum order amount of %s required', 'ultra-gst-invoice'),
                    get_option('ugst_currency_symbol', '₹') . number_format($coupon->minimum_amount, 2)
                )
            );
        }
        
        // Check applicable customers
        if ($coupon->applicable_customers && $customer_id) {
            $applicable_customers = json_decode($coupon->applicable_customers, true);
            if (!in_array($customer_id, $applicable_customers)) {
                return array('valid' => false, 'message' => __('Coupon not applicable for this customer', 'ultra-gst-invoice'));
            }
        }
        
        // Check applicable products
        if ($coupon->applicable_products && !empty($products)) {
            $applicable_products = json_decode($coupon->applicable_products, true);
            $has_applicable_product = false;
            
            foreach ($products as $product_id) {
                if (in_array($product_id, $applicable_products)) {
                    $has_applicable_product = true;
                    break;
                }
            }
            
            if (!$has_applicable_product) {
                return array('valid' => false, 'message' => __('Coupon not applicable for selected products', 'ultra-gst-invoice'));
            }
        }
        
        return array('valid' => true, 'coupon' => $coupon);
    }
    
    /**
     * Apply coupon and calculate discount
     */
    public function apply_coupon($code, $total_amount, $customer_id = null, $products = array()) {
        $validation = $this->validate_coupon($code, $customer_id, $total_amount, $products);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        $coupon = $validation['coupon'];
        $discount_amount = 0;
        
        // Calculate discount based on type
        switch ($coupon->discount_type) {
            case 'percentage':
                $discount_amount = ($total_amount * $coupon->discount_value) / 100;
                
                // Apply maximum discount limit if set
                if ($coupon->maximum_discount && $discount_amount > $coupon->maximum_discount) {
                    $discount_amount = $coupon->maximum_discount;
                }
                break;
                
            case 'fixed':
                $discount_amount = $coupon->discount_value;
                
                // Discount cannot be more than total amount
                if ($discount_amount > $total_amount) {
                    $discount_amount = $total_amount;
                }
                break;
                
            case 'free_shipping':
                // This would be handled in shipping calculation
                $discount_amount = 0;
                break;
        }
        
        return array(
            'success' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount_amount,
            'final_amount' => $total_amount - $discount_amount
        );
    }
    
    /**
     * Mark coupon as used
     */
    public function use_coupon($coupon_id, $invoice_id = null) {
        global $wpdb;
        
        // Increment used count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ugst_coupons SET used_count = used_count + 1 WHERE id = %d",
            $coupon_id
        ));
        
        // Log coupon usage
        $this->log_coupon_usage($coupon_id, $invoice_id);
        
        return true;
    }
    
    /**
     * Log coupon usage for tracking
     */
    private function log_coupon_usage($coupon_id, $invoice_id = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ugst_coupon_usage',
            array(
                'coupon_id' => $coupon_id,
                'invoice_id' => $invoice_id,
                'user_id' => get_current_user_id(),
                'used_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Generate bulk coupons
     */
    public function bulk_generate_coupons($data) {
        $count = intval($data['count']);
        $prefix = sanitize_text_field($data['prefix'] ?? 'BULK');
        $discount_type = sanitize_text_field($data['discount_type']);
        $discount_value = floatval($data['discount_value']);
        $expiry_days = intval($data['expiry_days'] ?? 30);
        
        $generated_coupons = array();
        
        for ($i = 1; $i <= $count; $i++) {
            $code = $prefix . '-' . strtoupper(wp_generate_password(8, false));
            
            $coupon_data = array(
                'code' => $code,
                'description' => sprintf(__('Bulk generated coupon #%d', 'ultra-gst-invoice'), $i),
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'end_date' => date('Y-m-d H:i:s', strtotime("+{$expiry_days} days")),
                'usage_limit' => 1
            );
            
            $result = $this->create_coupon($coupon_data);
            
            if ($result['success']) {
                $generated_coupons[] = $code;
            }
        }
        
        return array(
            'success' => true,
            'generated_count' => count($generated_coupons),
            'coupons' => $generated_coupons
        );
    }
    
    /**
     * Get coupon statistics
     */
    public function get_coupon_stats($coupon_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                c.*,
                COALESCE(SUM(i.total_amount), 0) as total_revenue,
                COUNT(cu.id) as total_usage
            FROM {$wpdb->prefix}ugst_coupons c
            LEFT JOIN {$wpdb->prefix}ugst_coupon_usage cu ON c.id = cu.coupon_id
            LEFT JOIN {$wpdb->prefix}ugst_invoices i ON cu.invoice_id = i.id
            WHERE c.id = %d
            GROUP BY c.id",
            $coupon_id
        ));
        
        return $stats;
    }
    
    /**
     * Get all coupons with pagination
     */
    public function get_coupons($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        $params = array();
        
        if ($args['status'] !== 'all') {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $coupons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ugst_coupons 
            {$where} 
            ORDER BY {$args['orderby']} {$args['order']} 
            LIMIT %d OFFSET %d",
            array_merge($params, array($args['per_page'], $offset))
        ));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_coupons {$where}",
            $params
        ));
        
        return array(
            'coupons' => $coupons,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }
    
    /**
     * Delete coupon
     */
    public function delete_coupon($coupon_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'ugst_coupons',
            array('id' => $coupon_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * AJAX: Create coupon
     */
    public function ajax_create_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        if (!current_user_can('ugst_manage_settings')) {
            wp_die(__('Insufficient permissions', 'ultra-gst-invoice'));
        }
        
        $result = $this->create_coupon($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Validate coupon
     */
    public function ajax_validate_coupon() {
        $code = sanitize_text_field($_POST['code']);
        $total_amount = floatval($_POST['total_amount']);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        $result = $this->validate_coupon($code, $customer_id, $total_amount);
        
        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Apply coupon
     */
    public function ajax_apply_coupon() {
        $code = sanitize_text_field($_POST['code']);
        $total_amount = floatval($_POST['total_amount']);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        $result = $this->apply_coupon($code, $total_amount, $customer_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Bulk generate coupons
     */
    public function ajax_bulk_generate_coupons() {
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        if (!current_user_can('ugst_manage_settings')) {
            wp_die(__('Insufficient permissions', 'ultra-gst-invoice'));
        }
        
        $result = $this->bulk_generate_coupons($_POST);
        
        wp_send_json_success($result);
    }
    
    /**
     * Shortcode: Coupon form
     */
    public function coupon_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_title' => 'yes',
            'button_text' => __('Apply Coupon', 'ultra-gst-invoice')
        ), $atts);
        
        ob_start();
        ?>
        <div class="ugst-coupon-form">
            <?php if ($atts['show_title'] === 'yes'): ?>
                <h4><?php _e('Have a Coupon Code?', 'ultra-gst-invoice'); ?></h4>
            <?php endif; ?>
            
            <form id="ugst-coupon-form" class="ugst-form">
                <div class="form-group">
                    <input type="text" 
                           id="ugst-coupon-code" 
                           name="coupon_code" 
                           placeholder="<?php _e('Enter coupon code', 'ultra-gst-invoice'); ?>"
                           class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                <div id="ugst-coupon-message" class="coupon-message"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ugst-coupon-form').on('submit', function(e) {
                e.preventDefault();
                
                var code = $('#ugst-coupon-code').val();
                var totalAmount = parseFloat($('#total-amount').val() || 0);
                
                if (!code) {
                    $('#ugst-coupon-message').html('<div class="alert alert-error"><?php _e("Please enter a coupon code", "ultra-gst-invoice"); ?></div>');
                    return;
                }
                
                $.ajax({
                    url: ugst_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ugst_apply_coupon',
                        code: code,
                        total_amount: totalAmount,
                        nonce: ugst_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var discount = response.data.discount_amount;
                            var finalAmount = response.data.final_amount;
                            
                            $('#ugst-coupon-message').html('<div class="alert alert-success"><?php _e("Coupon applied successfully!", "ultra-gst-invoice"); ?></div>');
                            $('#discount-amount').text(ugst_ajax.currency_symbol + discount.toFixed(2));
                            $('#final-amount').text(ugst_ajax.currency_symbol + finalAmount.toFixed(2));
                        } else {
                            $('#ugst-coupon-message').html('<div class="alert alert-error">' + response.data + '</div>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}