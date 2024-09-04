<?php

final class WC_BankartPaymentGateway_Flik extends WC_BankartPaymentGateway_PaymentCard
{
    public $id = 'flik_payments';

    protected function set_translated_text()
    {
        $this->method_title = __('Flik payments', 'woocommerce-bankart-payment-gateway');
        
        $this->method_description = __('For accepting payments using Bankart Payment Gateway (Slovenian banks)', 'woocommerce-bankart-payment-gateway');
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'title' => [
                'title' => __('Title', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Text displayed to the customer on the payment selection menu', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Flik payments', 'woocommerce-bankart-payment-gateway'),
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-bankart-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description of the payment method displayed to the customer', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Pay securely for your order with your Flik account.', 'woocommerce-bankart-payment-gateway') . $this->method_title,
            ],
            'apiUser' => [
                'title' => __('API User', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your API username credential', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'apiPassword' => [
                'title' => __('API Password', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your API password', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'apiKey' => [
                'title' => __('API Key', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your payment connector API key', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'sharedSecret' => [
                'title' => __('Shared Secret', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your payment connector shared secret', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'transactionRequest' => [
                'title' => __('Transaction type', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the option based on the agreement with your acquiring bank', 'woocommerce-bankart-payment-gateway'),
                'default' => 'debit',
                'options' => [
                    'debit' => __('Debit', 'woocommerce-bankart-payment-gateway'),
                ],
            ],
        ];
    }
}