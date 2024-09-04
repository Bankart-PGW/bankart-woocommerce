<?php

final class WC_BankartPaymentGateway_MC_VISA extends WC_BankartPaymentGateway_PaymentCard
{
    public $id = 'mcvisa_cards';

    protected function set_translated_text()
    {
        $this->method_title = __('Maestro Mastercard VISA cards', 'woocommerce-bankart-payment-gateway');
        
        $this->method_description = __('For accepting Maestro, Mastercard and VISA card payments using Bankart Payment Gateway (Slovenian banks)', 'woocommerce-bankart-payment-gateway');
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'title' => [
                'title' => __('Title', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Text displayed to the customer on the payment selection menu', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Maestro/Mastercard/VISA', 'woocommerce-bankart-payment-gateway'),
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-bankart-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description of the payment method displayed to the customer', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Pay securely for your order with your debit or credit card.', 'woocommerce-bankart-payment-gateway') . $this->method_title,
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
            'integrationKey' => [
                'title' => __('Integration Key', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Integration key for Payment.js fields', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'transactionRequest' => [
                'title' => __('Transaction type', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the option based on the agreement with your acquiring bank', 'woocommerce-bankart-payment-gateway'),
                'default' => 'debit',
                'options' => [
                    'debit' => __('Debit', 'woocommerce-bankart-payment-gateway'),
                    'preauthorize' => __('Preauthorize/Capture/Void', 'woocommerce-bankart-payment-gateway'),
                ],
            ],
            'preauthorizeSuccess' => [
                'title' => __('Preauthorize approved', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the order status for successful preauthorization', 'woocommerce-bankart-payment-gateway'),
                'default' => 'on-hold',
                'options' => [
                    'on-hold' => _x( 'On hold', 'Order status', 'woocommerce' ),
                    'processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
                ],
            ],
            'max_instalments' => [
                'title' => __('Installments', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the option based on the agreement with your acquiring bank', 'woocommerce-bankart-payment-gateway'),
                'default' => '1',
                'options' => [
                    '1' => __('Instalments disabled', 'woocommerce-bankart-payment-gateway'),
                    '12' => __('Up to 12 instalments', 'woocommerce-bankart-payment-gateway'),
                    '24' => __('Up to 24 instalments', 'woocommerce-bankart-payment-gateway'),
					'36' => __('Up to 36 instalments', 'woocommerce-bankart-payment-gateway'),
					'60' => __('Up to 60 instalments', 'woocommerce-bankart-payment-gateway'),
                ],
            ],
            'instalments-description' => [
                'title' => __('Describe instalment option', 'woocommerce-bankart-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description of the instalments option displayed to the customer', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Select the number of instalments, if your payment card supports selecting instalments at the point of sale', 'woocommerce-bankart-payment-gateway'),
            ],
            'min_instalment' => [
                'title' => __('Minimum instalment amount', 'woocommerce-bankart-payment-gateway'),
                'type' => 'number',
                'description' => __('Based on this amount the maximum number of allowed instalments will be calculated', 'woocommerce-bankart-payment-gateway'),
                'default' => '50',
            ],
			'force_challenge' => [
                'title' => __('Merchant prefers challange', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Challaenge requested: 3DS Requestor preference', 'woocommerce-bankart-payment-gateway'),
				'default' => '0',
				'options' => [
                    '0' => __('No', 'woocommerce-bankart-payment-gateway'),
                    '1' => __('Yes', 'woocommerce-bankart-payment-gateway'),
				],
            ],
        ];
    }
}