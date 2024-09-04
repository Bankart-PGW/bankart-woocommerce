<?php
final class WC_BankartPaymentGateway_MC_VISA_Blocks extends WC_PaymentGateway_Base_Blocks {

    protected $name = BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX . 'mcvisa_cards';
    protected $script_file = 'mcVisaCheckout.js';

    protected function create_gateway_instance() {
        return new WC_BankartPaymentGateway_MC_VISA();
    }
}