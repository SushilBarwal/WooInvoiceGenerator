<?php
/**
 * Plugin Name: Woo Invoice Plugin
 * Description: Generates and sends custom invoices for completed WooCommerce orders.
 * Version: 1.0
 * Author: Sushil Barwal
 */

// Include TCPDF library
//require_once get_stylesheet_directory() . '/tcpdf/tcpdf.php';
require_once plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';

// Add action to trigger custom invoice email on order completion
add_action('woocommerce_order_status_completed', 'send_custom_invoice_email', 10, 1);

function setCustomFont($pdf, $fontType, $fontWeight) {
    $fontname = '';
    if ($fontType == 'regular') {
        $fontname = 'NotoSansHebrew-Regular';
    } elseif ($fontType == 'medium') {
        $fontname = 'NotoSansHebrew-Medium';
    }

    if ($fontname != '') {
        $fontPath = plugin_dir_path(__FILE__) . 'tcpdf/fonts/Noto_Sans_Hebrew/static/' . $fontname . '.ttf';
        $font = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($font, $fontWeight, 12);
    }
}

function send_custom_invoice_email($order_id) {
    $order = wc_get_order($order_id);
    $completed_order_count = wc_orders_count('completed');
    $invoice_no = $completed_order_count + 78;
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    $fontname = TCPDF_FONTS::addTTFfont(plugin_dir_path(__FILE__) . 'tcpdf/fonts/Noto_Sans_Hebrew/NotoSansHebrew-VariableFont_wdth,wght.ttf', 'TrueTypeUnicode', '', 96);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Invoice ' . $order_id);
    $pdf->SetSubject('Invoice');
    $pdf->SetKeywords('Invoice, Order');

    $pdf->setRTL(true);
    $pdf->SetFont($fontname, '', 12);
    setCustomFont($pdf, 'medium', 'B');

    $pdf->AddPage();

    $html = '<table border="0" style="width: 100%; border-collapse: collapse; font-family: "Noto Sans Hebrew", sans-serif;">';
    $html .= '<tr>';
    $html .= '<td style="border-right: none;">';
    $html .= '<h1>חשבונית מס</h1><br><span>קבלה מקור (מספר) ' . $invoice_no . '</span>';
    $html .= '</td>';
    $html .= '<td style="border-left: none; text-align: left;">';
    $html .= '<img src="/wp-content/uploads/2023/11/dryynk-logo-blue.png" alt="Site Logo" style="width: 150px; height: auto;" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '<p></p>';
    $html .= '<p></p>';

    $client_name = '(' . $order->get_billing_email() . '), ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $pdf->setCellPaddings(0, 0, 0, 0);

    $html .= '<table cellpadding="1" style="width: 100%; font-family: "Noto Sans Hebrew", sans-serif;">';
    $html .= '<tr>';
    $html .= '<th style="width: 20%; padding: 0py;">שם לקוח:</th>';
    $html .= '<td align="right" style="padding: 0pt;">' . $client_name . "</td>";
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th style="width:20%; padding: 0mm;">מס\' הזמנה:</th>';
    $html .= '<td align="right" style="padding: 0mm;">' . $order_id . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th style="width:20%; padding: 10px;">סוג הזמנה:</th>';
    $html .= '<td align="right" style="padding: 10px;">' . $order->get_shipping_method() . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th style="width:20%; padding: 10px;">תאריך הפקה:</th>';
    $html .= '<td align="right" style="padding: 10px;">' . $order->get_date_created()->format('d/m/Y H:i') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th style="width:20%; padding: 10px;">אמצעי תשלום:</th>';
    $html .= '<td align="right" style="padding: 10px;">' . $order->get_payment_method() . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $pdf->setCellPaddings(1, 1, 1, 1);
    $html .= '<table><tr><td></td><td></td></tr></table>';
    $html .= '<table border="" style="width: 100%; font-family: "Noto Sans Hebrew", sans-serif;">';
    $html .= '<thead><tr><th style="width:36%; border-bottom: 1px solid #000;">פריט</th><th align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">מע"מ %</th><th align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">כמות</th><th align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">מחיר יחידה</th><th align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">מחיר</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $subtotal = $order->get_item_subtotal($item, false);
        $tax_rate = 0.17;
        $tax = $subtotal * $tax_rate;
        $subtotal_including_tax = $subtotal;
        $total = $subtotal_including_tax;
        $html .= '<tr>';
        $html .= '<td style="width:36%; padding: 10px; border-bottom: 1px solid #000;">' . $product->get_name() . '</td>';
        $html .= '<td align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">17%</td>';
        $html .= '<td align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">' . $item->get_quantity() . '</td>';
        $html .= '<td align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">' . wc_price($subtotal_including_tax) . '</td>';
        $html .= '<td align="center" style=" padding: 10px;width:16%; border-bottom: 1px solid #000;">' . wc_price($total) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<table><tr><td></td><td></td></tr></table>';
    $html .= '<table style="width: 100%; font-family: "Noto Sans Hebrew", sans-serif;">';
    $html .= '<tr>';
    $html .= '<td style="width:36%; padding: 10px;"><strong>' . $order->get_shipping_method() . '</strong></td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%;">17%</td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%;">' . $item->get_quantity() . '</td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%;">' . wc_price($shipping_total) . '</td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%;">' . wc_price($shipping_total) . '</td>';
    $html .= '</tr>';

    $html .= '<tr style="width: 100%;">';
    $html .= '<td style="width:36%; padding: 10px;"><strong>סה"כ בש"ח (כולל מע״מ)</strong></td>';
    $html .= '<td style=" padding: 10px;width:16%; "></td>';
    $html .= '<td style=" padding: 10px;width:16%; "></td>';
    $html .= '<td style=" padding: 10px;width:16%; "></td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%;">' . $order->get_formatted_order_total() . '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $total = $order->get_total();
    $tax_rate = 0.17;
    $subtotal_excluding_tax = $total / (1 + $tax_rate);
    $tax_amount = $total - $subtotal_excluding_tax;

    $html .= '<table><tr><td></td><td></td></tr></table>';
    $html .= '<table style="width: 100%; font-family: "Noto Sans Hebrew", sans-serif;">';
    $html .= '<thead><tr><th style="width:36%; padding: 10px;border-top: 1px solid #000;"></th><th align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000;">סך נטו</th><th style=" padding: 10px;width:16%; border-top: 1px solid #000;"></th><th align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000; ">מע"מ</th><th align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000;"> סה"כ</th></tr></thead>';
    $html .= '<tr>';
    $html .= '<td style="width:36%; padding: 10px;"><strong>מע"מ 17%</strong></td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000;">' . wc_price($subtotal_excluding_tax) . '</td>';
    $html .= '<td style=" padding: 10px;width:16%; border-top: 1px solid #000;"></td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000;">' . wc_price($tax_amount) . '</td>';
    $html .= '<td align="center" style=" padding: 10px;width:16%; border-top: 1px solid #000;">' . $order->get_formatted_order_total() . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $pdf->setCellPaddings(1, 1, 1, 1);
    $html .= '<p></p>';
    $html .= '<p></p>';
    $html .= '<p></p>';
    $html .= '<p></p>';
    $html .= "<p style='margin-top: 100px; padding: 0;font-family: 'Noto Sans Hebrew', sans-serif; '>אר אנד אר גי אם סי שיווק בַעײמ<br>רח' ינאי 3 / שושן 2 ירושלים<br>Tel: 02-6250006/02-6511819<br>Email: help@dryynk.com<br>חפ 513835835</p>";

    $pdf->writeHTML($html, true, false, true, false, '');

    $file_path = WP_CONTENT_DIR . '/uploads/invoices/invoice_' . $order_id . '.pdf';
    $pdf->Output($file_path, 'F');

    add_filter('woocommerce_email_attachments', function ($attachments, $email_id, $order) use ($file_path) {
        $attachments[] = $file_path;
        return $attachments;
    }, 10, 3);
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// Add a custom metabox
add_action('add_meta_boxes', 'admin_order_custom_metabox');
function admin_order_custom_metabox() {
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'custom',
        'Order PDF Invoice',
        'custom_metabox_content',
        $screen,
        'side',
        'high'
    );
}

// Render custom meta box content
function custom_metabox_content($post) {
    $order = wc_get_order($post->ID);
    if ($order && $order->has_status('completed')) {
        $invoice_url = WP_CONTENT_URL . '/uploads/invoices/invoice_' . $post->ID . '.pdf';
        echo '<p><a href="' . esc_url($invoice_url) . '" target="_blank" class="button button-primary">Download Invoice PDF</a></p>';
    } else {
        echo '<p>Invoice is available after the order is completed.</p>';
    }
}