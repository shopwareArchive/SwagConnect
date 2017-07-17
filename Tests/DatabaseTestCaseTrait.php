<?php

namespace ShopwarePlugins\Connect\Tests;

use Doctrine\DBAL\Connection;

trait DatabaseTestCaseTrait
{
    /**
     * @before
     */
    protected function startTransactionBefore()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->beginTransaction();
    }

    /**
     * @after
     */
    protected function rollbackTransactionAfter()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->rollBack();
    }

    /**
     * @param string $file
     */
    public function importFixtures($file)
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery(file_get_contents($file));
    }
}