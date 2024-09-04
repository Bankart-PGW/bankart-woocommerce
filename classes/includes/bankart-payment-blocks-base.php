<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
class WC_PaymentGateway_Base_Blocks extends AbstractPaymentMethodType {
    protected $gateway;
    protected $name;
    protected $script_file;

    public function initialize() {
        $this->settings = get_option( "woocommerce_{$this->name}_settings", null );
        $this->gateway = $this->create_gateway_instance();
    }

    public function is_active() {
        return $this->get_setting( 'enabled' ) === 'yes';
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            "wc-{$this->name}-blocks-integration",
            plugin_dir_url(__DIR__) . "includes/block/{$this->script_file}",
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            false,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations("wc-{$this->name}-blocks-integration");
        }
        return ["wc-{$this->name}-blocks-integration"];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }

    protected function create_gateway_instance() {
        // To be implemented in child classes
        return null;
    }
}