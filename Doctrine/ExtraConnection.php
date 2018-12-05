<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;

/**
 * Doctrine DBAL-connection wrapper.
 * Adds a new "postCommit" event to the Doctrine event system.
 */
class ExtraConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        parent::commit();

        if ($this->_eventManager->hasListeners(ExtraEvents::postCommit)) {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(ExtraEvents::postCommit, $eventArgs);
        }
    }
}
