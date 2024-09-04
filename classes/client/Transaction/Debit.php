<?php

namespace BankartPaymentGateway\Client\Transaction;

use BankartPaymentGateway\Client\Transaction\Base\AbstractTransactionWithReference;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileInterface;
use BankartPaymentGateway\Client\Transaction\Base\AddToCustomerProfileTrait;
use BankartPaymentGateway\Client\Transaction\Base\AmountableInterface;
use BankartPaymentGateway\Client\Transaction\Base\AmountableTrait;
use BankartPaymentGateway\Client\Transaction\Base\CustomerInterface;
use BankartPaymentGateway\Client\Transaction\Base\CustomerTrait;
use BankartPaymentGateway\Client\Transaction\Base\ItemsInterface;
use BankartPaymentGateway\Client\Transaction\Base\ItemsTrait;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteInterface;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteTrait;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleInterface;
use BankartPaymentGateway\Client\Transaction\Base\ScheduleTrait;
use BankartPaymentGateway\Client\Transaction\Base\ThreeDSecureInterface;
use BankartPaymentGateway\Client\Transaction\Base\ThreeDSecureTrait;

/**
 * Debit: Charge the customer for a certain amount of money. This could be once, but also recurring.
 *
 * @package BankartPaymentGateway\Client\Transaction
 */
class Debit extends AbstractTransactionWithReference
            implements AddToCustomerProfileInterface,
                       AmountableInterface,
                       CustomerInterface,
                       ItemsInterface,
                       OffsiteInterface,
                       ScheduleInterface,
                       ThreeDSecureInterface
{

    use AddToCustomerProfileTrait;
    use AmountableTrait;
    use CustomerTrait;
    use ItemsTrait;
    use OffsiteTrait;
    use ScheduleTrait;
    use ThreeDSecureTrait;

    const TRANSACTION_INDICATOR_SINGLE = 'SINGLE';
    const TRANSACTION_INDICATOR_INITIAL = 'INITIAL';
    const TRANSACTION_INDICATOR_RECURRING = 'RECURRING';
    const TRANSACTION_INDICATOR_CARDONFILE = 'CARDONFILE';
    const TRANSACTION_INDICATOR_CARDONFILE_MERCHANT = 'CARDONFILE_MERCHANT';

    /** @var string */
    protected $transactionToken;

    /** @var bool */
    protected $withRegister = false;

    /** @var string */
    protected $transactionIndicator;

    /** @var string */
    protected $language;

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
     * @return boolean
     */
    public function isWithRegister() {
        return $this->withRegister;
    }

    /**
     * set true if you want to register a user vault together with the debit
     *
     * @param boolean $withRegister
     *
     * @return $this
     */
    public function setWithRegister($withRegister) {
        $this->withRegister = $withRegister;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionIndicator() {
        return $this->transactionIndicator;
    }

    /**
     * @param string $transactionIndicator
     *
     * @return $this
     */
    public function setTransactionIndicator($transactionIndicator) {
        $this->transactionIndicator = $transactionIndicator;
        return $this;
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
}