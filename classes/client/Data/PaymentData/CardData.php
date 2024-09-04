<?php

namespace BankartPaymentGateway\Client\Data\PaymentData;

/**
 * Class CardData
 *
 * @package BankartPaymentGateway\Client\CustomerProfile\PaymentData
 */
class CardData extends PaymentData {

    /** @var string */
    protected $brand;
    /** @var string */
    protected $cardHolder;
    /** @var string */
    protected $firstSixDigits;
    /** @var string */
    protected $lastFourDigits;
    /** @var int */
    protected $expiryMonth;
    /** @var int */
    protected $expiryYear;

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return CardData
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardHolder()
    {
        return $this->cardHolder;
    }

    /**
     * @param string $cardHolder
     *
     * @return CardData
     */
    public function setCardHolder($cardHolder)
    {
        $this->cardHolder = $cardHolder;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstSixDigits()
    {
        return $this->firstSixDigits;
    }

    /**
     * @param string $firstSixDigits
     *
     * @return CardData
     */
    public function setFirstSixDigits($firstSixDigits)
    {
        $this->firstSixDigits = $firstSixDigits;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastFourDigits()
    {
        return $this->lastFourDigits;
    }

    /**
     * @param string $lastFourDigits
     *
     * @return CardData
     */
    public function setLastFourDigits($lastFourDigits)
    {
        $this->lastFourDigits = $lastFourDigits;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiryMonth()
    {
        return $this->expiryMonth;
    }

    /**
     * @param int $expiryMonth
     *
     * @return CardData
     */
    public function setExpiryMonth($expiryMonth)
    {
        $this->expiryMonth = $expiryMonth;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiryYear()
    {
        return $this->expiryYear;
    }

    /**
     * @param int $expiryYear
     *
     * @return CardData
     */
    public function setExpiryYear($expiryYear)
    {
        $this->expiryYear = $expiryYear;
        return $this;
    }

}