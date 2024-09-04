<?php

namespace BankartPaymentGateway\Client\Transaction;

use BankartPaymentGateway\Client\Transaction\Base\AbstractTransactionWithReference;
use BankartPaymentGateway\Client\Transaction\Base\AmountableInterface;
use BankartPaymentGateway\Client\Transaction\Base\AmountableTrait;
use BankartPaymentGateway\Client\Transaction\Base\CustomerInterface;
use BankartPaymentGateway\Client\Transaction\Base\CustomerTrait;
use BankartPaymentGateway\Client\Transaction\Base\ItemsInterface;
use BankartPaymentGateway\Client\Transaction\Base\ItemsTrait;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteInterface;
use BankartPaymentGateway\Client\Transaction\Base\OffsiteTrait;

/**
 * Payout: Payout a certain amount of money to the customer. (Debits the merchant's account, Credits the customer's account)
 *
 * @package BankartPaymentGateway\Client\Transaction
 */
class Payout extends AbstractTransactionWithReference
             implements AmountableInterface,
                        CustomerInterface,
                        ItemsInterface,
                        OffsiteInterface
{

    use AmountableTrait;
    use CustomerTrait;
    use ItemsTrait;
    use OffsiteTrait;

    /** @var string */
    protected $transactionToken;

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