<?php
final class WC_BankartPaymentGateway_Diners_Blocks extends WC_PaymentGateway_Base_Blocks {

    protected $name = BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX . 'diners_cards';
    protected $script_file = 'dinersCheckout.js';

    protected function create_gateway_instance() {
        return new WC_BankartPaymentGateway_Diners();
    }
}