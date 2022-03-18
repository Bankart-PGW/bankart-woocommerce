<?php

namespace BankartPaymentGateway\Client\Transaction;

use BankartPaymentGateway\Client\Transaction\Base\AbstractTransaction;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileInterface;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileTrait;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteInterface;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteTrait;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleInterface;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleTrait;

/**
 * Register: Register the customer's payment data for recurring charges.
 *
 * The registered customer payment data will be available for recurring transaction without user interaction.
 *
 * @package BankartPaymentGateway\Client\Transaction
 */
class Register extends AbstractTransaction implements OffsiteInterface, ScheduleInterface, AddToCustomerProfileInterface {
    use OffsiteTrait;
    use ScheduleTrait;
    use AddToCustomerProfileTrait;
}
