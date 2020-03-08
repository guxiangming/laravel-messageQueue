<?php

namespace App\Observers;

use App\Events\sendMsg as EventsSendMsg;
use App\Model\hlyun_message_message_receivers;

class sendMsg
{
    /**
     * Handle the hlyun_message_message_receivers "created" event.
     *
     * @param  \App\hlyun_message_message_receivers  $hlyunMessageMessageReceivers
     * @return void
     */
    public function created(hlyun_message_message_receivers $hlyunMessageMessageReceivers)
    {
        \Event::fire(new EventsSendMsg($hlyunMessageMessageReceivers));
    }

    /**
     * Handle the hlyun_message_message_receivers "updated" event.
     *
     * @param  \App\hlyun_message_message_receivers  $hlyunMessageMessageReceivers
     * @return void
     */
    public function updated(hlyun_message_message_receivers $hlyunMessageMessageReceivers)
    {
        //
    }

    /**
     * Handle the hlyun_message_message_receivers "deleted" event.
     *
     * @param  \App\hlyun_message_message_receivers  $hlyunMessageMessageReceivers
     * @return void
     */
    public function deleted(hlyun_message_message_receivers $hlyunMessageMessageReceivers)
    {
        //
    }

    /**
     * Handle the hlyun_message_message_receivers "restored" event.
     *
     * @param  \App\hlyun_message_message_receivers  $hlyunMessageMessageReceivers
     * @return void
     */
    public function restored(hlyun_message_message_receivers $hlyunMessageMessageReceivers)
    {
        //
    }

    /**
     * Handle the hlyun_message_message_receivers "force deleted" event.
     *
     * @param  \App\hlyun_message_message_receivers  $hlyunMessageMessageReceivers
     * @return void
     */
    public function forceDeleted(hlyun_message_message_receivers $hlyunMessageMessageReceivers)
    {
        //
    }
}
