<?php

namespace BankartPaymentGateway\Client\Transaction;

use BankartPaymentGateway\Client\Transaction\Base\AbstractTransactionWithReference;
use BankartPaymentGateway\Client\Transaction\Base\AmountableInterface;
use BankartPaymentGateway\Client\Transaction\Base\AmountableTrait;
use BankartPaymentGateway\Client\Transaction\Base\ItemsInterface;
use BankartPaymentGateway\Client\Transaction\Base\ItemsTrait;

/**
 * Capture: Charge a previously preauthorized transaction.
 *
 * @package BankartPaymentGateway\Client\Transaction
 */
class Capture extends AbstractTransactionWithReference implements AmountableInterface, ItemsInterface {
    use AmountableTrait;
    use ItemsTrait;
}