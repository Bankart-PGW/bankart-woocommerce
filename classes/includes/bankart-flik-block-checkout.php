<?php
final class WC_BankartPaymentGateway_Flik_Blocks extends WC_PaymentGateway_Base_Blocks {

    protected $name = BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX . 'flik_payments';
    protected $script_file = 'flikCheckout.js';

    protected function create_gateway_instance() {
        return new WC_BankartPaymentGateway_Flik();
    }
}