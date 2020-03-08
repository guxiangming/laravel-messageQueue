<?php

namespace App\MessageQueue\Facades;

use Illuminate\Support\Facades\Facade;

class MessageQueue extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MessageProducer';
    }
}