<?php
/**
 * Plugin Name: WooCommerce Bankart Payment Gateway Extension
 * Description: Bankart Payment Gateway for WooCommerce
 * Version: 3.1.1.1
 * Author: Bankart
 * WC requires at least: 8.0
 * WC tested up to: 8.3.1
 * Text Domain: woocommerce-bankart-payment-gateway
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

define('BANKART_PAYMENT_GATEWAY_EXTENSION_URL', 'https://gateway.bankart.si/');
#define('BANKART_PAYMENT_GATEWAY_EXTENSION_URL', 'https://bankart.paymentsandbox.cloud/');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_NAME', 'Bankart Payment Gateway');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_VERSION', '3.1.1.1');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX', 'bankart_payment_gateway_');
define('BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR', plugin_dir_path(__FILE__));

#For HPOS
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

add_action('plugins_loaded', function () {
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-provider.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-paymentcard.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-mcvisa.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-diners.php';
    require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/includes/bankart-payment-gateway-flik.php';


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

//NEW AP
add_action('admin_enqueue_scripts', 'enqueue_admin_validation_script');
function enqueue_admin_validation_script() {
    wp_enqueue_script(
        'custom-admin-validation',
        plugin_dir_url(__DIR__) . "woocommerce-bankart-payment-gateway/assets/js/bankart-custom-admin-validation.js",
        ['jquery'],
        false,
        true
    );

    // Localize the script with translation strings
    wp_localize_script('custom-admin-validation', 'wpTranslations', [
        'minInstalmentError' => __('The minimum instalment amount must be greater than 0.', 'woocommerce-bankart-payment-gateway')
    ]);
}

// Custom function to declare compatibility with cart_checkout_blocks feature 
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', function() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-payment-blocks-base.php';
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-payment-gateway-provider.php';
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-diners-block-checkout.php';
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-flik-block-checkout.php';
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-mc-visa-block-checkout.php';
    require_once plugin_dir_path(__FILE__) . '/classes/includes/bankart-payment-card-block-checkout.php'; 

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of My_Custom_Gateway_Blocks
            foreach (WC_BankartPaymentGateway_Provider::get_payment_methods() as $payment_method) {
                $payment_method_registry->register($payment_method);
            }
        }
    );
});
#END NEW AP