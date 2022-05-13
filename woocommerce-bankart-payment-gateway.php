<?php
/**
 * Plugin Name: WooCommerce Bankart Payment Gateway Extension
 * Description: Bankart Payment Gateway for WooCommerce
 * Version: 1.7.4.1
 * Author: Bankart
 * WC requires at least: 3.6.0
 * WC tested up to: 5.0.0
 * Text Domain: woocommerce-bankart-payment-gateway
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

define('BANKART_PAYMENT_GATEWAY_EXTENSION_URL', 'https://gateway.bankart.si/');
#define('BANKART_PAYMENT_GATEWAY_EXTENSION_URL', 'https://bankart.paymentsandbox.cloud/');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_NAME', 'Bankart Payment Gateway');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_VERSION', '1.7.4');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX', 'bankart_payment_gateway_');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR', plugin_dir_path(__FILE__));

add_action('plugins_loaded', function () {
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-provider.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-paymentcard.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-mcvisa.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-diners.php';

    load_plugin_textdomain('woocommerce-bankart-payment-gateway', FALSE, basename(BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR) . '/languages');

    add_action('init', 'bankart_redirect_clear_cart_url');
    add_action('woocommerce_admin_order_data_after_order_details', 'bankart_display_admin_instalments');
    add_filter('woocommerce_checkout_show_terms', 'bankart_order_pay_remove_terms');
    add_filter('the_content',  'bankart_redirect_show_failed_payment', 0, 1);
    add_filter('woocommerce_payment_gateways', 'bankart_add_gateway', 0);

    function bankart_redirect_clear_cart_url() 
    {
        if (isset( $_GET['clear-cart']) && is_order_received_page()) {
            WC()->cart->empty_cart();
        }
    }

    function bankart_add_gateway($methods)
    {
        foreach (WC_BankartPaymentGateway_Provider::paymentMethods() as $paymentMethod) {
            $methods[] = $paymentMethod;
        }
        return $methods;
    }

    function bankart_redirect_show_failed_payment($content)
    {
        if(is_checkout_pay_page() || is_checkout()) {
            if(!empty($_GET['gateway_return_result']) && $_GET['gateway_return_result'] == 'error') {
                wc_print_notice(__('Payment failed or was declined', 'woocommerce-bankart-payment-gateway'), 'error');
            }
        }
        return $content;
    }

    function bankart_order_pay_remove_terms($show) 
    {
        if(is_checkout_pay_page()) $show = false;
        return $show;
    }

    function bankart_display_admin_instalments($order) 
    {
        if (metadata_exists('post', $order->get_id(), '_bankart_instalments')) {
            echo 
            '<div class="order_data_column">
                <h3>' . esc_html__( 'Extra Details', 'woocommerce' ) . '</h4>
                    <p><strong>' . esc_html__('Number of instalments:', 'woocommerce-bankart-payment-gateway') . '</strong> ' .  get_post_meta($order->get_id(), '_bankart_instalments', true) . '</p>
            </div>';
        }
    }

});
