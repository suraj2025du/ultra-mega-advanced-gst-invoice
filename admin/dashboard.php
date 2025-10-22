<?php
/**
 * Admin Dashboard Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard statistics
global $wpdb;

$today = date('Y-m-d');
$this_month = date('Y-m');
$this_year = date('Y');

// Today's stats
$today_sales = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}ugst_invoices WHERE DATE(created_at) = %s AND status IN ('paid', 'completed')",
    $today
));

$today_invoices = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE DATE(created_at) = %s",
    $today
));

// This month's stats
$month_sales = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}ugst_invoices WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s AND status IN ('paid', 'completed')",
    $this_month
));

$month_invoices = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
    $this_month
));

// General stats
$total_customers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ugst_customers WHERE status = 'active'");
$pending_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE status = 'pending'");
$overdue_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices WHERE status = 'pending' AND due_date < CURDATE()");
$low_stock_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ugst_products WHERE stock_quantity <= min_stock_level AND status = 'active'");

// Recent invoices
$recent_invoices = $wpdb->get_results(
    "SELECT i.*, c.company_name 
    FROM {$wpdb->prefix}ugst_invoices i 
    LEFT JOIN {$wpdb->prefix}ugst_customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC 
    LIMIT 10"
);

// Top customers
$top_customers = $wpdb->get_results(
    "SELECT c.company_name, c.email, SUM(i.total_amount) as total_amount, COUNT(i.id) as invoice_count
    FROM {$wpdb->prefix}ugst_customers c
    LEFT JOIN {$wpdb->prefix}ugst_invoices i ON c.id = i.customer_id
    WHERE i.status IN ('paid', 'completed')
    GROUP BY c.id
    ORDER BY total_amount DESC
    LIMIT 5"
);

// Monthly sales data for chart
$monthly_sales = $wpdb->get_results(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total
    FROM {$wpdb->prefix}ugst_invoices 
    WHERE status IN ('paid', 'completed') AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC"
);

$currency_symbol = get_option('ugst_currency_symbol', '₹');
?>

<div class="wrap ugst-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('GST Invoice Dashboard', 'ultra-gst-invoice'); ?>
    </h1>
    
    <div class="ugst-dashboard-widgets">
        <!-- Stats Cards -->
        <div class="ugst-stats-row">
            <div class="ugst-stat-card today-sales">
                <div class="stat-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $currency_symbol . number_format($today_sales ?: 0, 2); ?></h3>
                    <p><?php _e("Today's Sales", 'ultra-gst-invoice'); ?></p>
                    <small><?php echo sprintf(__('%d invoices', 'ultra-gst-invoice'), $today_invoices); ?></small>
                </div>
            </div>
            
            <div class="ugst-stat-card month-sales">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $currency_symbol . number_format($month_sales ?: 0, 2); ?></h3>
                    <p><?php _e('This Month Sales', 'ultra-gst-invoice'); ?></p>
                    <small><?php echo sprintf(__('%d invoices', 'ultra-gst-invoice'), $month_invoices); ?></small>
                </div>
            </div>
            
            <div class="ugst-stat-card total-customers">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_customers); ?></h3>
                    <p><?php _e('Total Customers', 'ultra-gst-invoice'); ?></p>
                    <small><a href="<?php echo admin_url('admin.php?page=ugst-customers'); ?>"><?php _e('Manage Customers', 'ultra-gst-invoice'); ?></a></small>
                </div>
            </div>
            
            <div class="ugst-stat-card pending-invoices">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($pending_invoices); ?></h3>
                    <p><?php _e('Pending Invoices', 'ultra-gst-invoice'); ?></p>
                    <small>
                        <?php if ($overdue_invoices > 0): ?>
                            <span class="overdue"><?php echo sprintf(__('%d overdue', 'ultra-gst-invoice'), $overdue_invoices); ?></span>
                        <?php else: ?>
                            <?php _e('All up to date', 'ultra-gst-invoice'); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="ugst-quick-actions">
            <h2><?php _e('Quick Actions', 'ultra-gst-invoice'); ?></h2>
            <div class="quick-action-buttons">
                <a href="<?php echo admin_url('admin.php?page=ugst-invoices&action=add'); ?>" class="button button-primary button-large">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create Invoice', 'ultra-gst-invoice'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ugst-customers&action=add'); ?>" class="button button-secondary button-large">
                    <span class="dashicons dashicons-businessman"></span>
                    <?php _e('Add Customer', 'ultra-gst-invoice'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ugst-products&action=add'); ?>" class="button button-secondary button-large">
                    <span class="dashicons dashicons-products"></span>
                    <?php _e('Add Product', 'ultra-gst-invoice'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ugst-reports'); ?>" class="button button-secondary button-large">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('View Reports', 'ultra-gst-invoice'); ?>
                </a>
            </div>
        </div>
        
        <!-- Charts and Analytics -->
        <div class="ugst-dashboard-row">
            <div class="ugst-dashboard-col-8">
                <div class="ugst-widget">
                    <h3><?php _e('Sales Overview (Last 12 Months)', 'ultra-gst-invoice'); ?></h3>
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="ugst-dashboard-col-4">
                <div class="ugst-widget">
                    <h3><?php _e('Top Customers', 'ultra-gst-invoice'); ?></h3>
                    <div class="top-customers-list">
                        <?php if (!empty($top_customers)): ?>
                            <?php foreach ($top_customers as $customer): ?>
                                <div class="customer-item">
                                    <div class="customer-info">
                                        <strong><?php echo esc_html($customer->company_name); ?></strong>
                                        <small><?php echo esc_html($customer->email); ?></small>
                                    </div>
                                    <div class="customer-stats">
                                        <span class="amount"><?php echo $currency_symbol . number_format($customer->total_amount, 2); ?></span>
                                        <small><?php echo sprintf(__('%d invoices', 'ultra-gst-invoice'), $customer->invoice_count); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php _e('No customer data available', 'ultra-gst-invoice'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="ugst-dashboard-row">
            <div class="ugst-dashboard-col-6">
                <div class="ugst-widget">
                    <h3><?php _e('Recent Invoices', 'ultra-gst-invoice'); ?></h3>
                    <div class="recent-invoices-table">
                        <?php if (!empty($recent_invoices)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Invoice #', 'ultra-gst-invoice'); ?></th>
                                        <th><?php _e('Customer', 'ultra-gst-invoice'); ?></th>
                                        <th><?php _e('Amount', 'ultra-gst-invoice'); ?></th>
                                        <th><?php _e('Status', 'ultra-gst-invoice'); ?></th>
                                        <th><?php _e('Date', 'ultra-gst-invoice'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_invoices as $invoice): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=ugst-invoices&action=view&id=' . $invoice->id); ?>">
                                                    <?php echo esc_html($invoice->invoice_number); ?>
                                                </a>
                                            </td>
                                            <td><?php echo esc_html($invoice->company_name); ?></td>
                                            <td><?php echo $currency_symbol . number_format($invoice->total_amount, 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo esc_attr($invoice->status); ?>">
                                                    <?php echo ucfirst($invoice->status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date_i18n(get_option('date_format'), strtotime($invoice->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('No recent invoices found', 'ultra-gst-invoice'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="ugst-dashboard-col-6">
                <div class="ugst-widget">
                    <h3><?php _e('System Status', 'ultra-gst-invoice'); ?></h3>
                    <div class="system-status">
                        <div class="status-item">
                            <span class="status-label"><?php _e('Plugin Version:', 'ultra-gst-invoice'); ?></span>
                            <span class="status-value"><?php echo UGST_VERSION; ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Database Status:', 'ultra-gst-invoice'); ?></span>
                            <span class="status-value status-good"><?php _e('Connected', 'ultra-gst-invoice'); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Low Stock Products:', 'ultra-gst-invoice'); ?></span>
                            <span class="status-value <?php echo $low_stock_products > 0 ? 'status-warning' : 'status-good'; ?>">
                                <?php echo $low_stock_products; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Overdue Invoices:', 'ultra-gst-invoice'); ?></span>
                            <span class="status-value <?php echo $overdue_invoices > 0 ? 'status-error' : 'status-good'; ?>">
                                <?php echo $overdue_invoices; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($low_stock_products > 0 || $overdue_invoices > 0): ?>
                        <div class="system-alerts">
                            <h4><?php _e('Alerts', 'ultra-gst-invoice'); ?></h4>
                            <?php if ($low_stock_products > 0): ?>
                                <div class="alert alert-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php echo sprintf(__('%d products are running low on stock', 'ultra-gst-invoice'), $low_stock_products); ?>
                                    <a href="<?php echo admin_url('admin.php?page=ugst-inventory'); ?>"><?php _e('View Inventory', 'ultra-gst-invoice'); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($overdue_invoices > 0): ?>
                                <div class="alert alert-error">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo sprintf(__('%d invoices are overdue', 'ultra-gst-invoice'), $overdue_invoices); ?>
                                    <a href="<?php echo admin_url('admin.php?page=ugst-invoices&status=overdue'); ?>"><?php _e('View Overdue', 'ultra-gst-invoice'); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Sales Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesData = <?php echo json_encode($monthly_sales); ?>;
    
    var labels = [];
    var data = [];
    
    salesData.forEach(function(item) {
        labels.push(item.month);
        data.push(parseFloat(item.total));
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '<?php _e("Sales Amount", "ultra-gst-invoice"); ?>',
                data: data,
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?php echo $currency_symbol; ?>' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '<?php echo $currency_symbol; ?>' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Auto-refresh dashboard stats every 5 minutes
    setInterval(function() {
        $.ajax({
            url: ugst_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ugst_admin_action',
                ugst_action: 'get_dashboard_stats',
                nonce: ugst_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update stats cards
                    $('.today-sales h3').text('<?php echo $currency_symbol; ?>' + parseFloat(response.data.today_sales || 0).toLocaleString());
                    $('.month-sales h3').text('<?php echo $currency_symbol; ?>' + parseFloat(response.data.month_sales || 0).toLocaleString());
                    $('.total-customers h3').text(parseInt(response.data.total_customers || 0).toLocaleString());
                    $('.pending-invoices h3').text(parseInt(response.data.pending_invoices || 0).toLocaleString());
                }
            }
        });
    }, 300000); // 5 minutes
});
</script>

<style>
.ugst-dashboard {
    margin: 20px 0;
}

.ugst-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.ugst-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.ugst-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.ugst-stat-card .stat-icon {
    margin-right: 15px;
    font-size: 24px;
    color: #0073aa;
}

.ugst-stat-card .stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.ugst-stat-card .stat-content p {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 14px;
}

.ugst-stat-card .stat-content small {
    color: #999;
    font-size: 12px;
}

.ugst-quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.quick-action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.quick-action-buttons .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ugst-dashboard-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.ugst-dashboard-col-6 {
    grid-column: span 1;
}

.ugst-dashboard-col-8 {
    grid-column: span 2;
}

.ugst-dashboard-col-4 {
    grid-column: span 1;
}

.ugst-widget {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    height: fit-content;
}

.ugst-widget h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.top-customers-list .customer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.top-customers-list .customer-item:last-child {
    border-bottom: none;
}

.customer-info strong {
    display: block;
    color: #333;
}

.customer-info small {
    color: #666;
}

.customer-stats {
    text-align: right;
}

.customer-stats .amount {
    display: block;
    font-weight: bold;
    color: #0073aa;
}

.customer-stats small {
    color: #999;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-draft { background: #f0f0f0; color: #666; }
.status-pending { background: #fff3cd; color: #856404; }
.status-paid { background: #d4edda; color: #155724; }
.status-completed { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.system-status .status-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.system-status .status-item:last-child {
    border-bottom: none;
}

.status-good { color: #28a745; }
.status-warning { color: #ffc107; }
.status-error { color: #dc3545; }

.system-alerts {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.alert {
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert a {
    margin-left: auto;
    text-decoration: none;
}

#salesChart {
    max-height: 300px;
}

@media (max-width: 768px) {
    .ugst-stats-row {
        grid-template-columns: 1fr;
    }
    
    .ugst-dashboard-row {
        grid-template-columns: 1fr;
    }
    
    .quick-action-buttons {
        flex-direction: column;
    }
    
    .quick-action-buttons .button {
        justify-content: center;
    }
}
</style>