<?php

namespace Tests\ShopwarePlugins\Connect;

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
}