<?php

namespace BankartPaymentGateway\Client\CustomerProfile\PaymentData;

/**
 * Class WalletData
 *
 * @package BankartPaymentGateway\Client\CustomerProfile\PaymentData
 *
 * @property string $walletReferenceId
 * @property string $walletOwner
 * @property string $walletType
 */
class WalletData extends PaymentData {

    const TYPE_PAYPAL = 'paypal';

}
