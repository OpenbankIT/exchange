<?php

namespace App\Collections;

class Cashin extends \Phalcon\Mvc\Collection
{
    
    const STATUS_WAITING = 0;
    const STATUS_PAID = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;
    const STATUS_PARTIAL_PAYMENT = 4;

    const TYPE_BTC = 1;
    const TYPE_WMU = 2;
    const TYPE_PRIVAT24 = 3;
    
    public $status; //status of order 
    
    public $type; //type of payment system

    public $date; //date of create order

    public $amount; //how much smartmoney want to buy
    
    public $asset; //asset of smartmoney want to buy

    public $cost; //how much cost $amount in other asset

    public $get; //how much client paid

    public $from; //wallet from user will sned payment (not always filled)

    public $to; //our wallet for get money from $from

    public $client_to; //client smartmoney wallet for receiving the money

    public $response; //response of payment system
    
    public $fail_description; //response of cashier daemon in case of error

    public $tx; //transaction hash in case of success

    public function initialize()
    {
        //$this->useImplicitObjectIds(false);
        $this->useImplicitObjectIds(true);
    }
}