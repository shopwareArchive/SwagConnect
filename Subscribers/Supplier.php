<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;

class Supplier implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginPath;
    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param string $pluginPath
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param Connection $connection
     */
    public function __construct($pluginPath, \Shopware_Components_Snippet_Manager $snippetManager, Connection $connection)
    {
        $this->pluginPath = $pluginPath;
        $this->snippetManager = $snippetManager;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Supplier' => 'extentBackendSupplier',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function extentBackendSupplier(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $subject->View()->addTemplateDir($this->pluginPath . 'Views/', 'connect');
                $this->snippetManager->addConfigDir($this->pluginPath . 'Views/');
                $subject->View()->extendsTemplate(
                    'backend/supplier/list.js'
                );
                break;
            case 'getSuppliers':
                $subject->View()->data = $this->markConnectSuppliers(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    /**
     * @param array $suppliers
     * @return array
     */
    protected function markConnectSuppliers($suppliers)
    {
        $supplierIds = array_map(function ($row) {
            return $row['id'];
        }, $suppliers);

        $connectSuppliers = $this->getConnectSuppliers($supplierIds);

        foreach ($suppliers as $index => $supplier) {
            $suppliers[$index]['isConnect'] = in_array($supplier['id'], $connectSuppliers);
        }

        return $suppliers;
    }

    /**
     * @param array $supplierIds
     * @return array
     */
    protected function getConnectSuppliers($supplierIds)
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $builder = $this->connection->createQueryBuilder();
        $builder->select('supplierID')
            ->from('s_articles_supplier_attributes', 'sa')
            ->where('sa.supplierID IN (:supplierIds)')
            ->andWhere('sa.connect_is_remote = 1')
            ->setParameter('supplierIds', $supplierIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return array_map(function ($item) {
            return $item['supplierID'];
        }, $builder->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
}
