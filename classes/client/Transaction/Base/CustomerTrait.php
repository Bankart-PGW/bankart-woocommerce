<?php

namespace BankartPaymentGateway\Client\Transaction\Base;

use BankartPaymentGateway\Client\Data\CreditCardCustomer;
use BankartPaymentGateway\Client\Data\Customer;
use BankartPaymentGateway\Client\Data\IbanCustomer;

/**
 * Class ThreeDSecureTrait
 *
 * @package BankartPaymentGateway\Client\Transaction\Base
 */
trait CustomerTrait {

    /** @var Customer */
    protected $customer;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * with backward compatibility for IbanCustomer/CreditCardCustomer
     * @param IbanCustomer|CreditCardCustomer|Customer $customer
     *
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

}