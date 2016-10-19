<?php

namespace ShopwarePlugins\Connect\Subscribers;


class Supplier extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Supplier' => 'extentBackendSupplier',
        );
    }

    public function extentBackendSupplier(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
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

    protected function getConnectSuppliers($supplierIds)
    {
        /** @var Doctrine\DBAL\Connection $conn */
        $conn = Shopware()->Models()->getConnection();
        $builder = $conn->createQueryBuilder();
        $builder->select('supplierID')
            ->from('s_articles_supplier_attributes', 'sa')
            ->where('sa.supplierID IN (:supplierIds)')
            ->andWhere('sa.connect_is_remote = 1')
            ->setParameter('supplierIds', $supplierIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return array_map(function($item){
            return $item['supplierID'];
        }, $builder->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
}