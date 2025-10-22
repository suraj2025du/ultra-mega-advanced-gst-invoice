<?php
/**
 * Coupons Management Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Initialize coupon class
$coupons = new UGST_Coupons();

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_POST) {
    if (wp_verify_nonce($_POST['ugst_nonce'], 'ugst_coupon_action')) {
        switch ($action) {
            case 'add':
            case 'edit':
                $result = $coupons->create_coupon($_POST);
                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
                    $action = 'list'; // Redirect to list after successful creation
                } else {
                    echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
                }
                break;
                
            case 'bulk_generate':
                $result = $coupons->bulk_generate_coupons($_POST);
                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>' . sprintf(__('%d coupons generated successfully', 'ultra-gst-invoice'), $result['generated_count']) . '</p></div>';
                }
                break;
        }
    }
}

// Handle delete action
if ($action === 'delete' && $coupon_id) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_coupon_' . $coupon_id)) {
        if ($coupons->delete_coupon($coupon_id)) {
            echo '<div class="notice notice-success"><p>' . __('Coupon deleted successfully', 'ultra-gst-invoice') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to delete coupon', 'ultra-gst-invoice') . '</p></div>';
        }
        $action = 'list';
    }
}

// Get coupon data for edit
$coupon_data = null;
if ($action === 'edit' && $coupon_id) {
    global $wpdb;
    $coupon_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ugst_coupons WHERE id = %d",
        $coupon_id
    ));
    
    if (!$coupon_data) {
        echo '<div class="notice notice-error"><p>' . __('Coupon not found', 'ultra-gst-invoice') . '</p></div>';
        $action = 'list';
    }
}
?>

<div class="wrap ugst-coupons">
    <h1 class="wp-heading-inline">
        <?php _e('Coupon Management', 'ultra-gst-invoice'); ?>
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=ugst-coupons&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Coupon', 'ultra-gst-invoice'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=ugst-coupons&action=bulk_generate'); ?>" class="page-title-action">
            <?php _e('Bulk Generate', 'ultra-gst-invoice'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($action === 'list'): ?>
        <?php
        // Get coupons list
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'active';
        
        $coupons_data = $coupons->get_coupons(array(
            'status' => $status,
            'page' => $page,
            'per_page' => 20
        ));
        ?>
        
        <!-- Filter Tabs -->
        <div class="subsubsub">
            <a href="<?php echo admin_url('admin.php?page=ugst-coupons&status=all'); ?>" 
               class="<?php echo $status === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'ultra-gst-invoice'); ?>
            </a> |
            <a href="<?php echo admin_url('admin.php?page=ugst-coupons&status=active'); ?>" 
               class="<?php echo $status === 'active' ? 'current' : ''; ?>">
                <?php _e('Active', 'ultra-gst-invoice'); ?>
            </a> |
            <a href="<?php echo admin_url('admin.php?page=ugst-coupons&status=expired'); ?>" 
               class="<?php echo $status === 'expired' ? 'current' : ''; ?>">
                <?php _e('Expired', 'ultra-gst-invoice'); ?>
            </a> |
            <a href="<?php echo admin_url('admin.php?page=ugst-coupons&status=inactive'); ?>" 
               class="<?php echo $status === 'inactive' ? 'current' : ''; ?>">
                <?php _e('Inactive', 'ultra-gst-invoice'); ?>
            </a>
        </div>
        
        <!-- Coupons Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" />
                    </th>
                    <th scope="col" class="manage-column column-code">
                        <?php _e('Code', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-description">
                        <?php _e('Description', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-discount">
                        <?php _e('Discount', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-usage">
                        <?php _e('Usage', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-validity">
                        <?php _e('Validity', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('Status', 'ultra-gst-invoice'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'ultra-gst-invoice'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($coupons_data['coupons'])): ?>
                    <?php foreach ($coupons_data['coupons'] as $coupon): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="coupon[]" value="<?php echo $coupon->id; ?>" />
                            </th>
                            <td class="column-code">
                                <strong><?php echo esc_html($coupon->code); ?></strong>
                            </td>
                            <td class="column-description">
                                <?php echo esc_html($coupon->description ?: __('No description', 'ultra-gst-invoice')); ?>
                            </td>
                            <td class="column-discount">
                                <?php if ($coupon->discount_type === 'percentage'): ?>
                                    <?php echo $coupon->discount_value . '%'; ?>
                                <?php elseif ($coupon->discount_type === 'fixed'): ?>
                                    <?php echo get_option('ugst_currency_symbol', '₹') . number_format($coupon->discount_value, 2); ?>
                                <?php else: ?>
                                    <?php echo ucfirst($coupon->discount_type); ?>
                                <?php endif; ?>
                                
                                <?php if ($coupon->minimum_amount > 0): ?>
                                    <br><small><?php echo sprintf(__('Min: %s', 'ultra-gst-invoice'), get_option('ugst_currency_symbol', '₹') . number_format($coupon->minimum_amount, 2)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="column-usage">
                                <?php echo $coupon->used_count; ?>
                                <?php if ($coupon->usage_limit): ?>
                                    / <?php echo $coupon->usage_limit; ?>
                                <?php else: ?>
                                    / <?php _e('Unlimited', 'ultra-gst-invoice'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-validity">
                                <?php if ($coupon->start_date): ?>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($coupon->start_date)); ?>
                                <?php else: ?>
                                    <?php _e('No start date', 'ultra-gst-invoice'); ?>
                                <?php endif; ?>
                                <br>
                                <?php if ($coupon->end_date): ?>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($coupon->end_date)); ?>
                                    <?php if (strtotime($coupon->end_date) < time()): ?>
                                        <span class="expired"><?php _e('(Expired)', 'ultra-gst-invoice'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php _e('No expiry', 'ultra-gst-invoice'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge status-<?php echo esc_attr($coupon->status); ?>">
                                    <?php echo ucfirst($coupon->status); ?>
                                </span>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo admin_url('admin.php?page=ugst-coupons&action=edit&id=' . $coupon->id); ?>" 
                                   class="button button-small">
                                    <?php _e('Edit', 'ultra-gst-invoice'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ugst-coupons&action=delete&id=' . $coupon->id), 'delete_coupon_' . $coupon->id); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php _e('Are you sure you want to delete this coupon?', 'ultra-gst-invoice'); ?>')">
                                    <?php _e('Delete', 'ultra-gst-invoice'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-items">
                            <?php _e('No coupons found.', 'ultra-gst-invoice'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($coupons_data['pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $coupons_data['pages'],
                        'current' => $page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Coupon Form -->
        <div class="ugst-form-container">
            <form method="post" class="ugst-coupon-form">
                <?php wp_nonce_field('ugst_coupon_action', 'ugst_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="code"><?php _e('Coupon Code', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="code" 
                                       name="code" 
                                       value="<?php echo $coupon_data ? esc_attr($coupon_data->code) : ''; ?>" 
                                       class="regular-text" 
                                       required 
                                       style="text-transform: uppercase;">
                                <p class="description"><?php _e('Enter a unique coupon code (will be converted to uppercase)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description"><?php _e('Description', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <textarea id="description" 
                                          name="description" 
                                          rows="3" 
                                          class="large-text"><?php echo $coupon_data ? esc_textarea($coupon_data->description) : ''; ?></textarea>
                                <p class="description"><?php _e('Optional description for internal reference', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="discount_type"><?php _e('Discount Type', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <select id="discount_type" name="discount_type" required>
                                    <option value="percentage" <?php selected($coupon_data ? $coupon_data->discount_type : '', 'percentage'); ?>>
                                        <?php _e('Percentage Discount', 'ultra-gst-invoice'); ?>
                                    </option>
                                    <option value="fixed" <?php selected($coupon_data ? $coupon_data->discount_type : '', 'fixed'); ?>>
                                        <?php _e('Fixed Amount Discount', 'ultra-gst-invoice'); ?>
                                    </option>
                                    <option value="free_shipping" <?php selected($coupon_data ? $coupon_data->discount_type : '', 'free_shipping'); ?>>
                                        <?php _e('Free Shipping', 'ultra-gst-invoice'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="discount_value"><?php _e('Discount Value', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="discount_value" 
                                       name="discount_value" 
                                       value="<?php echo $coupon_data ? esc_attr($coupon_data->discount_value) : ''; ?>" 
                                       step="0.01" 
                                       min="0" 
                                       class="small-text" 
                                       required>
                                <span id="discount_unit">%</span>
                                <p class="description"><?php _e('Enter the discount value (percentage or fixed amount)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="minimum_amount"><?php _e('Minimum Order Amount', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="minimum_amount" 
                                       name="minimum_amount" 
                                       value="<?php echo $coupon_data ? esc_attr($coupon_data->minimum_amount) : ''; ?>" 
                                       step="0.01" 
                                       min="0" 
                                       class="small-text">
                                <p class="description"><?php _e('Minimum order amount required to use this coupon (optional)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="maximum_discount"><?php _e('Maximum Discount Amount', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="maximum_discount" 
                                       name="maximum_discount" 
                                       value="<?php echo $coupon_data ? esc_attr($coupon_data->maximum_discount) : ''; ?>" 
                                       step="0.01" 
                                       min="0" 
                                       class="small-text">
                                <p class="description"><?php _e('Maximum discount amount for percentage coupons (optional)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="usage_limit"><?php _e('Usage Limit', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="usage_limit" 
                                       name="usage_limit" 
                                       value="<?php echo $coupon_data ? esc_attr($coupon_data->usage_limit) : ''; ?>" 
                                       min="1" 
                                       class="small-text">
                                <p class="description"><?php _e('Maximum number of times this coupon can be used (leave empty for unlimited)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="start_date"><?php _e('Start Date', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="datetime-local" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="<?php echo $coupon_data && $coupon_data->start_date ? date('Y-m-d\TH:i', strtotime($coupon_data->start_date)) : ''; ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('When the coupon becomes active (optional)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="end_date"><?php _e('End Date', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="datetime-local" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="<?php echo $coupon_data && $coupon_data->end_date ? date('Y-m-d\TH:i', strtotime($coupon_data->end_date)) : ''; ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('When the coupon expires (optional)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status"><?php _e('Status', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($coupon_data ? $coupon_data->status : 'active', 'active'); ?>>
                                        <?php _e('Active', 'ultra-gst-invoice'); ?>
                                    </option>
                                    <option value="inactive" <?php selected($coupon_data ? $coupon_data->status : '', 'inactive'); ?>>
                                        <?php _e('Inactive', 'ultra-gst-invoice'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="submit" 
                           class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Update Coupon', 'ultra-gst-invoice') : __('Create Coupon', 'ultra-gst-invoice'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=ugst-coupons'); ?>" class="button">
                        <?php _e('Cancel', 'ultra-gst-invoice'); ?>
                    </a>
                </p>
            </form>
        </div>
        
    <?php elseif ($action === 'bulk_generate'): ?>
        <!-- Bulk Generate Form -->
        <div class="ugst-form-container">
            <h2><?php _e('Bulk Generate Coupons', 'ultra-gst-invoice'); ?></h2>
            
            <form method="post" class="ugst-bulk-coupon-form">
                <?php wp_nonce_field('ugst_coupon_action', 'ugst_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="count"><?php _e('Number of Coupons', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="count" 
                                       name="count" 
                                       value="10" 
                                       min="1" 
                                       max="1000" 
                                       class="small-text" 
                                       required>
                                <p class="description"><?php _e('How many coupons to generate (max 1000)', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="prefix"><?php _e('Code Prefix', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="prefix" 
                                       name="prefix" 
                                       value="BULK" 
                                       class="regular-text" 
                                       style="text-transform: uppercase;">
                                <p class="description"><?php _e('Prefix for generated coupon codes', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="discount_type"><?php _e('Discount Type', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <select id="discount_type" name="discount_type" required>
                                    <option value="percentage"><?php _e('Percentage Discount', 'ultra-gst-invoice'); ?></option>
                                    <option value="fixed"><?php _e('Fixed Amount Discount', 'ultra-gst-invoice'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="discount_value"><?php _e('Discount Value', 'ultra-gst-invoice'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="discount_value" 
                                       name="discount_value" 
                                       value="10" 
                                       step="0.01" 
                                       min="0" 
                                       class="small-text" 
                                       required>
                                <span id="discount_unit">%</span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="expiry_days"><?php _e('Expires After (Days)', 'ultra-gst-invoice'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="expiry_days" 
                                       name="expiry_days" 
                                       value="30" 
                                       min="1" 
                                       class="small-text">
                                <p class="description"><?php _e('Number of days from now when coupons will expire', 'ultra-gst-invoice'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="submit" 
                           class="button-primary" 
                           value="<?php _e('Generate Coupons', 'ultra-gst-invoice'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=ugst-coupons'); ?>" class="button">
                        <?php _e('Cancel', 'ultra-gst-invoice'); ?>
                    </a>
                </p>
            </form>
        </div>
        
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Update discount unit based on type
    $('#discount_type').on('change', function() {
        var type = $(this).val();
        var unit = type === 'percentage' ? '%' : '<?php echo get_option("ugst_currency_symbol", "₹"); ?>';
        $('#discount_unit').text(unit);
        
        if (type === 'free_shipping') {
            $('#discount_value').val(0).prop('readonly', true);
        } else {
            $('#discount_value').prop('readonly', false);
        }
    });
    
    // Auto-generate coupon code
    $('#generate-code').on('click', function(e) {
        e.preventDefault();
        var code = 'COUPON' + Math.random().toString(36).substr(2, 8).toUpperCase();
        $('#code').val(code);
    });
    
    // Coupon code validation
    $('#code').on('input', function() {
        var code = $(this).val().toUpperCase();
        $(this).val(code);
        
        if (code.length >= 3) {
            // Check if code exists
            $.ajax({
                url: ugst_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ugst_check_coupon_code',
                    code: code,
                    nonce: ugst_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.data && response.data.exists) {
                        $('#code').addClass('error');
                        $('#code').after('<span class="error-message"><?php _e("Code already exists", "ultra-gst-invoice"); ?></span>');
                    } else {
                        $('#code').removeClass('error');
                        $('.error-message').remove();
                    }
                }
            });
        }
    });
});
</script>

<style>
.ugst-coupons .wp-heading-inline {
    margin-right: 10px;
}

.ugst-form-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }
.status-expired { background: #fff3cd; color: #856404; }

.expired {
    color: #dc3545;
    font-weight: bold;
}

.column-code { width: 15%; }
.column-description { width: 25%; }
.column-discount { width: 15%; }
.column-usage { width: 10%; }
.column-validity { width: 15%; }
.column-status { width: 10%; }
.column-actions { width: 10%; }

.ugst-coupon-form input[type="text"],
.ugst-coupon-form input[type="number"],
.ugst-coupon-form input[type="datetime-local"],
.ugst-coupon-form select,
.ugst-coupon-form textarea {
    width: 100%;
    max-width: 400px;
}

.ugst-coupon-form .small-text {
    max-width: 100px;
}

.ugst-coupon-form .regular-text {
    max-width: 300px;
}

.ugst-coupon-form .large-text {
    max-width: 500px;
}

#discount_unit {
    margin-left: 5px;
    font-weight: bold;
}

.error {
    border-color: #dc3545 !important;
}

.error-message {
    color: #dc3545;
    font-size: 12px;
    margin-left: 5px;
}

@media (max-width: 768px) {
    .wp-list-table th,
    .wp-list-table td {
        padding: 8px 4px;
        font-size: 12px;
    }
    
    .column-description,
    .column-validity {
        display: none;
    }
    
    .ugst-form-container {
        padding: 15px;
    }
}
</style>