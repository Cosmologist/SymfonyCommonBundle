<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\EventDispatcher;

use Cosmologist\Gears\ArrayType;
use Cosmologist\Gears\StringType;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherUtils
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * EventDispatcherUtils constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * The event name will be generated from the FQCN of the event object.
     *
     * @param Event $event The event to pass to the event handlers/listeners
     *
     * @return Event
     * @see self::generateEventName
     *      - "\" the namespace delimiter will be replaced by "."
     *      - FQCN components will begin with a lowercase letter
     *      - if the namespace contains the keyword “Event” or “Events”, then the word “Event” will be deleted from the class name
     *
     */
    public function dispatch(Event $event): Event
    {
        return $this->eventDispatcher->dispatch(self::generateEventName($event), $event);
    }

    /**
     * Generate the event name from the event object FQCN.
     *
     *  - "\" the namespace delimiter will be replaced by "."
     *  - FQCN components will begin with a lowercase letter
     *  - if the namespace contains the keyword “Event” or “Events”, then the word “Event” will be deleted from the class name
     *
     * @param Event $event
     *
     * @return string
     */
    public static function generateEventName(Event $event): string
    {
        $elements = array_map(
            'lcfirst',
            explode('\\', get_class($event))
        );

        $singleKeyword   = 'Event';
        $multipleKeyword = 'Events';

        $namespaceContainsKeyword = in_array($singleKeyword, $elements) || in_array($multipleKeyword, $elements);
        $classElement             = ArrayType::get($elements, -1);

        if ($namespaceContainsKeyword && StringType::endsWith($classElement, $singleKeyword)) {
            ArrayType::set($elements, -1, str_replace($singleKeyword, '', $classElement));
        }

        return implode('.', $elements);
    }
}