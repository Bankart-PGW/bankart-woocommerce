<?php

final class WC_BankartPaymentGateway_Provider
{
    public static function paymentMethods()
    {
        return [
            'WC_BankartPaymentGateway_PaymentCard',
            'WC_BankartPaymentGateway_MC_VISA',
            'WC_BankartPaymentGateway_Diners'
        ];
    }

    public static function autoloadClient()
    {
        require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/vendor/autoload.php';
    }
}
