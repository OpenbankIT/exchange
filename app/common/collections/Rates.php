<?php

namespace App\Collections;

class Rates extends \Phalcon\Mvc\Collection
{
    
    public $from;
    public $to;
    public $rate;
    public $created;

    public function ensureIndex()
    {
        $config = \Phalcon\DI::getDefault()->get('config');

        // Get the raw \MongoDB Connection
        $connection = $this->getConnection();

        // Get the \MongoCollection connection (with added dynamic collection name (thanks Phalcon))
        $collection = $connection->selectCollection($this->getSource());

        // One index.
        $collection->ensureIndex(
            array('created' => 1),
            array('expireAfterSeconds' => $config->exchange->bitcoind->rate_actual) //if you want to change time, you will need to clear existing index
        );
    }

    public function initialize()
    {
        $this->useImplicitObjectIds(true);
        $this->ensureIndex();
    }
}