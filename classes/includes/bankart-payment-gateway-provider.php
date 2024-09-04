<?php

final class WC_BankartPaymentGateway_Provider
{
    public static function paymentMethods()
    {
        return [
            'WC_BankartPaymentGateway_PaymentCard',
            'WC_BankartPaymentGateway_MC_VISA',
            'WC_BankartPaymentGateway_Diners',
			'WC_BankartPaymentGateway_Flik',            
        ];
    }

    public static function autoloadClient()
    {
        require_once BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'classes/vendor/autoload.php';
    }

    //NEW AP
    public static function get_payment_methods() {
        return [
            new WC_BankartPaymentGateway_PaymentCard_Blocks(),
            new WC_BankartPaymentGateway_Diners_Blocks(),
            new WC_BankartPaymentGateway_Flik_Blocks(),
            new WC_BankartPaymentGateway_MC_VISA_Blocks(),
        ];
    }
    //NEW END  AP
}
