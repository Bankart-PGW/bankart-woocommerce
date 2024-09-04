<?php
final class WC_BankartPaymentGateway_PaymentCard_Blocks extends WC_PaymentGateway_Base_Blocks {

    protected $name = BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX . 'payment_cards';
    protected $script_file = 'paymentCardsCheckout.js';

    protected function create_gateway_instance() {
        return new WC_BankartPaymentGateway_PaymentCard();
    }
}