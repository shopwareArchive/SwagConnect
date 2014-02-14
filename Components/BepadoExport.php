<?php

namespace Shopware\Bepado\Components;

use Bepado\SDK\SDK;
use Shopware\Components\Model\ModelManager;

class BepadoExport
{

    /** @var  Helper */
    protected $helper;
    /** @var  SDK */
    protected $sdk;
    /** @var  ModelManager */
    protected $manager;

    public function __construct(Helper $helper, SDK $sdk, ModelManager $manager)
    {
        $this->helper = $helper;
        $this->sdk = $sdk;
        $this->manager = $manager;
    }

    /**
     * Helper function to mark a given array of product ids for bepado update
     *
     * @param array $ids
     * @return array
     */
    public function export(array $ids)
    {

        $errors = array();

        foreach($ids as $id) {
            $model = $this->helper->getArticleModelById($id);
            $prefix = $model && $model->getName() ? $model->getName() . ': ' : '';

            if($model === null) {
                continue;
            }
            $bepadoAttribute = $this->helper->getOrCreateBepadoAttributeByModel($model);

            $status = $bepadoAttribute->getExportStatus();
            if(empty($status) || $status == 'delete' || $status == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $bepadoAttribute->setExportStatus(
                $status
            );
            $bepadoAttribute->setExportMessage(null);

            $categories = $this->helper->getRowProductCategoriesById($id);
            // Don't allow vendor categories for export
            $filteredCategories = array_filter($categories, function($category) {
                return strpos($category, '/vendor/') !== 0 && $category != '/vendor';
            });
            $bepadoAttribute->setCategories(
                serialize($filteredCategories)
            );

            if (empty($filteredCategories) && count($filteredCategories) != count($categories)) {
                $removed = implode("\n", array_diff($categories, $filteredCategories));
                $errors[] = " &bull; {$prefix} Ignoring these vendor-categories {$model->getName()}: {$removed}";
            }

            if (!$bepadoAttribute->getId()) {
                $this->manager->persist($bepadoAttribute);
            }
            $this->manager->flush($bepadoAttribute);
            try {
                if($status == 'insert') {
                    $this->sdk->recordInsert($id);
                } else {
                    $this->sdk->recordUpdate($id);
                }
            } catch(\Exception $e) {
                $bepadoAttribute->setExportStatus(
                    'error'
                );
                $bepadoAttribute->setExportMessage(
                    $e->getMessage() . "\n" . $e->getTraceAsString()
                );

                $errors[] = " &bull; " . $prefix . $e->getMessage();
                $this->manager->flush($bepadoAttribute);
            }
        }

        return $errors;
    }
}