<?php
/**
 * Invoice Management Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_Invoice {
    
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_ugst_create_invoice', array($this, 'ajax_create_invoice'));
        add_action('wp_ajax_ugst_update_invoice', array($this, 'ajax_update_invoice'));
        add_action('wp_ajax_ugst_delete_invoice', array($this, 'ajax_delete_invoice'));
        add_action('wp_ajax_ugst_generate_pdf', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_ugst_send_invoice_email', array($this, 'ajax_send_invoice_email'));
        
        // Add hooks for invoice numbering
        add_action('ugst_invoice_created', array($this, 'generate_invoice_number'));
    }
    
    /**
     * Create new invoice
     */
    public function create_invoice($invoice_data, $items = array()) {
        global $wpdb;
        
        // Validate required fields
        if (empty($invoice_data['customer_id']) || empty($items)) {
            return false;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Calculate totals
            $totals = $this->calculate_invoice_totals($items, $invoice_data);
            
            // Prepare invoice data
            $invoice_insert_data = array(
                'invoice_number' => $this->generate_next_invoice_number(),
                'customer_id' => intval($invoice_data['customer_id']),
                'invoice_date' => sanitize_text_field($invoice_data['invoice_date']),
                'due_date' => sanitize_text_field($invoice_data['due_date']),
                'subtotal' => $totals['subtotal'],
                'cgst' => $totals['cgst'],
                'sgst' => $totals['sgst'],
                'igst' => $totals['igst'],
                'total_tax' => $totals['total_tax'],
                'discount' => $totals['discount'],
                'total_amount' => $totals['total_amount'],
                'paid_amount' => 0.00,
                'status' => 'draft',
                'notes' => sanitize_textarea_field($invoice_data['notes'] ?? ''),
                'terms' => sanitize_textarea_field($invoice_data['terms'] ?? ''),
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            );
            
            // Insert invoice
            $result = $wpdb->insert(
                $wpdb->prefix . 'ugst_invoices',
                $invoice_insert_data,
                array('%s', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s')
            );
            
            if (!$result) {
                throw new Exception('Failed to create invoice');
            }
            
            $invoice_id = $wpdb->insert_id;
            
            // Insert invoice items
            foreach ($items as $item) {
                $item_data = array(
                    'invoice_id' => $invoice_id,
                    'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                    'description' => sanitize_text_field($item['description']),
                    'hsn_code' => sanitize_text_field($item['hsn_code'] ?? ''),
                    'quantity' => floatval($item['quantity']),
                    'unit' => sanitize_text_field($item['unit'] ?? 'Nos'),
                    'rate' => floatval($item['rate']),
                    'discount' => floatval($item['discount'] ?? 0),
                    'tax_rate' => floatval($item['tax_rate'] ?? 0),
                    'tax_amount' => floatval($item['tax_amount'] ?? 0),
                    'amount' => floatval($item['amount'])
                );
                
                $wpdb->insert(
                    $wpdb->prefix . 'ugst_invoice_items',
                    $item_data,
                    array('%d', '%d', '%s', '%s', '%f', '%s', '%f', '%f', '%f', '%f', '%f')
                );
                
                // Update product stock if product_id is provided
                if (!empty($item['product_id'])) {
                    $this->update_product_stock($item['product_id'], -$item['quantity']);
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Trigger action hook
            do_action('ugst_invoice_created', $invoice_id, $invoice_data, $items);
            
            return $invoice_id;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Update existing invoice
     */
    public function update_invoice($invoice_id, $invoice_data, $items = array()) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get existing invoice
            $existing_invoice = $this->get_invoice($invoice_id);
            if (!$existing_invoice) {
                throw new Exception('Invoice not found');
            }
            
            // Calculate totals
            $totals = $this->calculate_invoice_totals($items, $invoice_data);
            
            // Prepare update data
            $update_data = array(
                'customer_id' => intval($invoice_data['customer_id']),
                'invoice_date' => sanitize_text_field($invoice_data['invoice_date']),
                'due_date' => sanitize_text_field($invoice_data['due_date']),
                'subtotal' => $totals['subtotal'],
                'cgst' => $totals['cgst'],
                'sgst' => $totals['sgst'],
                'igst' => $totals['igst'],
                'total_tax' => $totals['total_tax'],
                'discount' => $totals['discount'],
                'total_amount' => $totals['total_amount'],
                'notes' => sanitize_textarea_field($invoice_data['notes'] ?? ''),
                'terms' => sanitize_textarea_field($invoice_data['terms'] ?? ''),
                'updated_at' => current_time('mysql')
            );
            
            // Update invoice
            $result = $wpdb->update(
                $wpdb->prefix . 'ugst_invoices',
                $update_data,
                array('id' => $invoice_id),
                array('%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Failed to update invoice');
            }
            
            // Restore stock for existing items
            $existing_items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ugst_invoice_items WHERE invoice_id = %d",
                $invoice_id
            ));
            
            foreach ($existing_items as $item) {
                if ($item->product_id) {
                    $this->update_product_stock($item->product_id, $item->quantity);
                }
            }
            
            // Delete existing items
            $wpdb->delete(
                $wpdb->prefix . 'ugst_invoice_items',
                array('invoice_id' => $invoice_id),
                array('%d')
            );
            
            // Insert new items
            foreach ($items as $item) {
                $item_data = array(
                    'invoice_id' => $invoice_id,
                    'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                    'description' => sanitize_text_field($item['description']),
                    'hsn_code' => sanitize_text_field($item['hsn_code'] ?? ''),
                    'quantity' => floatval($item['quantity']),
                    'unit' => sanitize_text_field($item['unit'] ?? 'Nos'),
                    'rate' => floatval($item['rate']),
                    'discount' => floatval($item['discount'] ?? 0),
                    'tax_rate' => floatval($item['tax_rate'] ?? 0),
                    'tax_amount' => floatval($item['tax_amount'] ?? 0),
                    'amount' => floatval($item['amount'])
                );
                
                $wpdb->insert(
                    $wpdb->prefix . 'ugst_invoice_items',
                    $item_data,
                    array('%d', '%d', '%s', '%s', '%f', '%s', '%f', '%f', '%f', '%f', '%f')
                );
                
                // Update product stock
                if (!empty($item['product_id'])) {
                    $this->update_product_stock($item['product_id'], -$item['quantity']);
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Trigger action hook
            do_action('ugst_invoice_updated', $invoice_id, $invoice_data, $items);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Get invoice by ID
     */
    public function get_invoice($invoice_id) {
        global $wpdb;
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.company_name, c.gstin, c.email, c.phone, c.address, c.city, c.state, c.pincode
            FROM {$wpdb->prefix}ugst_invoices i
            LEFT JOIN {$wpdb->prefix}ugst_customers c ON i.customer_id = c.id
            WHERE i.id = %d",
            $invoice_id
        ));
        
        if ($invoice) {
            // Get invoice items
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ugst_invoice_items WHERE invoice_id = %d ORDER BY id",
                $invoice_id
            ));
            
            $invoice->items = $items;
        }
        
        return $invoice;
    }
    
    /**
     * Get invoices with pagination and filters
     */
    public function get_invoices($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'all',
            'customer_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        $params = array();
        
        if ($args['status'] !== 'all') {
            $where .= " AND i.status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['customer_id']) {
            $where .= " AND i.customer_id = %d";
            $params[] = $args['customer_id'];
        }
        
        if ($args['date_from']) {
            $where .= " AND i.invoice_date >= %s";
            $params[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where .= " AND i.invoice_date <= %s";
            $params[] = $args['date_to'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.company_name, c.gstin
            FROM {$wpdb->prefix}ugst_invoices i
            LEFT JOIN {$wpdb->prefix}ugst_customers c ON i.customer_id = c.id
            {$where}
            ORDER BY i.{$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d",
            array_merge($params, array($args['per_page'], $offset))
        ));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ugst_invoices i {$where}",
            $params
        ));
        
        return array(
            'invoices' => $invoices,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }
    
    /**
     * Delete invoice
     */
    public function delete_invoice($invoice_id) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get invoice items to restore stock
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ugst_invoice_items WHERE invoice_id = %d",
                $invoice_id
            ));
            
            // Restore stock
            foreach ($items as $item) {
                if ($item->product_id) {
                    $this->update_product_stock($item->product_id, $item->quantity);
                }
            }
            
            // Delete invoice items
            $wpdb->delete(
                $wpdb->prefix . 'ugst_invoice_items',
                array('invoice_id' => $invoice_id),
                array('%d')
            );
            
            // Delete payments
            $wpdb->delete(
                $wpdb->prefix . 'ugst_payments',
                array('invoice_id' => $invoice_id),
                array('%d')
            );
            
            // Delete invoice
            $result = $wpdb->delete(
                $wpdb->prefix . 'ugst_invoices',
                array('id' => $invoice_id),
                array('%d')
            );
            
            if (!$result) {
                throw new Exception('Failed to delete invoice');
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Trigger action hook
            do_action('ugst_invoice_deleted', $invoice_id);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Calculate invoice totals
     */
    private function calculate_invoice_totals($items, $invoice_data = array()) {
        $subtotal = 0;
        $total_tax = 0;
        $cgst = 0;
        $sgst = 0;
        $igst = 0;
        $discount = floatval($invoice_data['discount'] ?? 0);
        
        // Get customer state for tax calculation
        $customer_state = '';
        if (!empty($invoice_data['customer_id'])) {
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT state FROM {$wpdb->prefix}ugst_customers WHERE id = %d",
                $invoice_data['customer_id']
            ));
            $customer_state = $customer ? $customer->state : '';
        }
        
        $company_state = get_option('ugst_company_state', '');
        $is_interstate = ($customer_state !== $company_state);
        
        foreach ($items as &$item) {
            $item_amount = ($item['quantity'] * $item['rate']) - floatval($item['discount'] ?? 0);
            $tax_rate = floatval($item['tax_rate'] ?? 0);
            $tax_amount = ($item_amount * $tax_rate) / 100;
            
            $item['amount'] = $item_amount;
            $item['tax_amount'] = $tax_amount;
            
            $subtotal += $item_amount;
            $total_tax += $tax_amount;
            
            // Calculate CGST/SGST or IGST
            if ($is_interstate) {
                $igst += $tax_amount;
            } else {
                $cgst += $tax_amount / 2;
                $sgst += $tax_amount / 2;
            }
        }
        
        $total_amount = $subtotal + $total_tax - $discount;
        
        return array(
            'subtotal' => $subtotal,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total_tax' => $total_tax,
            'discount' => $discount,
            'total_amount' => $total_amount
        );
    }
    
    /**
     * Generate next invoice number
     */
    private function generate_next_invoice_number() {
        $prefix = get_option('ugst_invoice_prefix', 'INV-');
        $start_number = get_option('ugst_invoice_start_number', 1);
        
        global $wpdb;
        $last_number = $wpdb->get_var(
            "SELECT MAX(CAST(SUBSTRING(invoice_number, LENGTH('{$prefix}') + 1) AS UNSIGNED)) 
            FROM {$wpdb->prefix}ugst_invoices 
            WHERE invoice_number LIKE '{$prefix}%'"
        );
        
        $next_number = max($last_number + 1, $start_number);
        
        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Update product stock
     */
    private function update_product_stock($product_id, $quantity_change) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ugst_products 
            SET stock_quantity = stock_quantity + %f 
            WHERE id = %d",
            $quantity_change,
            $product_id
        ));
    }
    
    /**
     * Generate PDF
     */
    public function generate_pdf($invoice_id) {
        $invoice = $this->get_invoice($invoice_id);
        
        if (!$invoice) {
            return false;
        }
        
        // Include PDF library (TCPDF or similar)
        require_once UGST_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';
        
        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Ultra GST Invoice');
        $pdf->SetAuthor(get_option('ugst_company_name', get_bloginfo('name')));
        $pdf->SetTitle('Invoice ' . $invoice->invoice_number);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Generate HTML content
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/invoice-pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Save PDF
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/ugst-invoices/invoices/';
        $pdf_filename = 'invoice-' . $invoice->invoice_number . '.pdf';
        $pdf_path = $pdf_dir . $pdf_filename;
        
        $pdf->Output($pdf_path, 'F');
        
        // Return URL
        return $upload_dir['baseurl'] . '/ugst-invoices/invoices/' . $pdf_filename;
    }
    
    /**
     * Send invoice email
     */
    public function send_email($invoice_id, $email = null) {
        $invoice = $this->get_invoice($invoice_id);
        
        if (!$invoice) {
            return false;
        }
        
        $to = $email ?: $invoice->email;
        
        if (!$to) {
            return false;
        }
        
        // Generate PDF
        $pdf_url = $this->generate_pdf($invoice_id);
        
        // Email subject
        $subject = sprintf(__('Invoice %s from %s', 'ultra-gst-invoice'), 
            $invoice->invoice_number, 
            get_option('ugst_company_name', get_bloginfo('name'))
        );
        
        // Email content
        ob_start();
        include UGST_PLUGIN_DIR . 'templates/email-invoice.php';
        $message = ob_get_clean();
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('ugst_email_from_name', get_bloginfo('name')) . ' <' . get_option('ugst_email_from_email', get_option('admin_email')) . '>'
        );
        
        // Attachments
        $attachments = array();
        if ($pdf_url) {
            $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
            if (file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            // Log email sent
            do_action('ugst_invoice_email_sent', $invoice_id, $to);
        }
        
        return $sent;
    }
    
    /**
     * Update invoice status
     */
    public function update_status($invoice_id, $status) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'ugst_invoices',
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $invoice_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            do_action('ugst_invoice_status_changed', $invoice_id, $status);
        }
        
        return $result !== false;
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_create_invoice() {
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        if (!current_user_can('ugst_create_invoices')) {
            wp_die(__('Insufficient permissions', 'ultra-gst-invoice'));
        }
        
        $invoice_data = $_POST['invoice_data'];
        $items = json_decode(stripslashes($_POST['items']), true);
        
        $invoice_id = $this->create_invoice($invoice_data, $items);
        
        if ($invoice_id) {
            wp_send_json_success(array(
                'message' => __('Invoice created successfully', 'ultra-gst-invoice'),
                'invoice_id' => $invoice_id
            ));
        } else {
            wp_send_json_error(__('Failed to create invoice', 'ultra-gst-invoice'));
        }
    }
    
    public function ajax_generate_pdf() {
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        $pdf_url = $this->generate_pdf($invoice_id);
        
        if ($pdf_url) {
            wp_send_json_success(array('pdf_url' => $pdf_url));
        } else {
            wp_send_json_error(__('Failed to generate PDF', 'ultra-gst-invoice'));
        }
    }
    
    public function ajax_send_invoice_email() {
        if (!wp_verify_nonce($_POST['nonce'], 'ugst_admin_nonce')) {
            wp_die(__('Security check failed', 'ultra-gst-invoice'));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        $email = sanitize_email($_POST['email']);
        
        $sent = $this->send_email($invoice_id, $email);
        
        if ($sent) {
            wp_send_json_success(__('Invoice sent successfully', 'ultra-gst-invoice'));
        } else {
            wp_send_json_error(__('Failed to send invoice', 'ultra-gst-invoice'));
        }
    }
}