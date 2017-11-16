<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

class InitResourceDbSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return ['Enlight_Bootstrap_InitResource_Db' => 'overwriteDb'];
    }

    /**
     * @return Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    public function overwriteDb()
    {
        $container = Shopware()->Container();
        $options = $container->getParameter('shopware.db');
        $options = ['dbname' => $options['dbname'], 'username' => null, 'password' => null];
        $db = \Enlight_Components_Db_Adapter_Pdo_Mysql::createFromDbalConnectionAndConfig($container->get('dbal_connection'), $options);

        \Zend_Db_Table_Abstract::setDefaultAdapter($db);

        return $db;
    }
}