<?php


namespace BankartPaymentGateway\Client\Data\Result;

/**
 * Class ResultData
 *
 * @package BankartPaymentGateway\Client\Data\Result
 */
abstract class ResultData {

    /**
     * @return array
     */
    abstract public function toArray();

}
