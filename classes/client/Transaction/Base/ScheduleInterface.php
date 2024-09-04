<?php

namespace BankartPaymentGateway\Client\Transaction\Base;

use BankartPaymentGateway\Client\Schedule\ScheduleData;
use BankartPaymentGateway\Client\Schedule\ScheduleWithTransaction;

interface ScheduleInterface {

    /**
     * @return ScheduleData|ScheduleWithTransaction
     */
    public function getSchedule();

    /**
     * @param ScheduleData|ScheduleWithTransaction $schedule |null
     *
     * @return $this
     */
    public function setSchedule($schedule = null);
}