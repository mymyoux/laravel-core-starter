<?php

namespace Core\Events;
use Core\Model\Event;
class EventEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function onEvent($event) {
        if(!isset($event->state))
            $event->state = Event::STATE_CREATED;
        $event->save();
    }

    /**
     * Handle user logout events.
     */
    public function onUserLogout($event) {}

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'Core\Model\Event',
            'Core\Events\EventEventSubscriber@onEvent'
        );
    }

}