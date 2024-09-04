<?php

namespace BankartPaymentGateway\Client\Transaction;

use BankartPaymentGateway\Client\Transaction\Base\AbstractTransaction;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileInterface;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileTrait;
use BankartPaymentGateway\Client\Transaction\Base\CustomerInterface;
use BankartPaymentGateway\Client\Transaction\Base\CustomerTrait;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteInterface;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteTrait;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleInterface;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleTrait;
use BankartPaymentGateway\Client\Transaction\Base\ThreeDSecureInterface;
use BankartPaymentGateway\Client\Transaction\Base\ThreeDSecureTrait;

/**
 * Register: Register the customer's payment data for recurring charges.
 *
 * The registered customer payment data will be available for recurring transaction without user interaction.
 *
 * @package BankartPaymentGateway\Client\Transaction
 */
class Register extends AbstractTransaction
               implements AddToCustomerProfileInterface,
                          CustomerInterface,
                          OffsiteInterface,
                          ScheduleInterface,
                          ThreeDSecureInterface
{

    use AddToCustomerProfileTrait;
    use CustomerTrait;
    use OffsiteTrait;
    use ScheduleTrait;
    use ThreeDSecureTrait;

    /** @var string */
    protected $language;

    /** @var string */
    protected $transactionToken;

    /**
     * @var string
     */
    protected $transactionIndicator;

    /**
     * @return string
     */
    public function getTransactionToken()
    {
        return $this->transactionToken;
    }

    /**
     * @param string $transactionToken
     */
    public function setTransactionToken($transactionToken)
    {
        $this->transactionToken = $transactionToken;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    
    /**
     * @return string
     */
    public function getTransactionIndicator() {
        return $this->transactionIndicator;
    }

    /**
     * @param string $transactionIndicator
     */
    public function setTransactionIndicator($transactionIndicator) {
        $this->transactionIndicator = $transactionIndicator;
    }
}