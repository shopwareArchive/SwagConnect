<?php

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Implements the \Enlight_Event_Subscriber "interface"
 *
 * Class SubscribeManager
 * @package ShopwarePlugins\Connect\Components\Subscribers
 */
abstract class SubscribeManager extends \Enlight_Event_Subscriber
{

    protected $debug = true;

    protected $listeners = array();

    /**
     * Creates the event handlers for the listener.
     *
     * Will perform some additional checks to prevent some common mistakes
     */
    public function __construct()
    {
        foreach ($this->getSubscribedEvents() as $event => $listener)  {
            if (!method_exists($this, $listener)) {
                throw new \RuntimeException("{$listener} not implemented");
            }
            $handler = new \Enlight_Event_Handler_Default(
                $event,
                array($this, $listener)
            );
            $this->listeners[] = $handler;
        }
    }

    abstract public function getSubscribedEvents();

    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * Registers a listener to an event.
     *
     * @param   \Enlight_Event_Handler $handler
     * @return  \Enlight_Event_Subscriber
     */
    public function registerListener(\Enlight_Event_Handler $handler)
    {
        $this->listeners[] = $handler;
        return $this;
    }

    /**
     * Removes an event listener from storage.
     *
     * @param   \Enlight_Event_Handler $handler
     * @return  \Enlight_Event_Subscriber
     */
    public function removeListener(\Enlight_Event_Handler $handler)
    {
        $this->listeners = array_diff($this->listeners, array($handler));
        return $this;
    }
}