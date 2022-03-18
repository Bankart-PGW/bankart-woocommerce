<?php

namespace BankartPaymentGateway\Client\Transaction\Base;
use BankartPaymentGateway\Client\Data\Item;

/**
 * Interface ItemsInterface
 *
 * @package BankartPaymentGateway\Client\Transaction\Base
 */
interface ItemsInterface {

    /**
     * @param Item[] $items
     * @return void
     */
    public function setItems($items);

    /**
     * @return Item[]
     */
    public function getItems();

    /**
     * @param Item $item
     * @return void
     */
    public function addItem($item);

}
