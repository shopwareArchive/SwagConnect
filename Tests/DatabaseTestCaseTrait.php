<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
