<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$options = array(
    'woocommerce_bankart_payment_gateway_mcvisa_cards_settings',
    'woocommerce_bankart_payment_gateway_payment_cards_settings',
    'woocommerce_bankart_payment_gateway_diners_cards_settings',
	'woocommerce_bankart_payment_gateway_flik_payments_settings',
);

foreach ($options as $option) {
	if (get_option($option)) delete_option($option);
}
