<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use PDO;

/**
 * Doctrine DBAL-connection wrapper.
 *
 * Add some useful features (like extra DBAL-events) and methods.
 */
class ExtraConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        parent::beginTransaction();

        if ($this->_eventManager->hasListeners(ExtraEvents::postBeginTransaction)) {
            $this->_eventManager->dispatchEvent(ExtraEvents::postBeginTransaction, new ConnectionEventArgs($this));
        }
    }

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


    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        parent::rollBack();

        if ($this->_eventManager->hasListeners(ExtraEvents::postRollback)) {
            $this->_eventManager->dispatchEvent(ExtraEvents::postRollback, new ConnectionEventArgs($this));
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array,
     * each row in the result set array is indexed by the value of the first column.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     *
     * @return array
     */
    public function fetchAllIndexed($sql, array $params = [], $types = [])
    {
        return $this
            ->executeQuery($sql, $params, $types)
            ->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }
}
