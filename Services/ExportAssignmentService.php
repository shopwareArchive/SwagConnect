<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Services;

use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Components\ConnectExport;

class ExportAssignmentService
{
    /**
     * @var AttributeRepository
     */
    private $attributeRepo;

    /**
     * @var ConnectExport
     */
    private $export;

    public function __construct(AttributeRepository $attributeRepository, ConnectExport $connectExport)
    {
        $this->attributeRepo = $attributeRepository;
        $this->export = $connectExport;
    }

    public function getCountOfAllExportableArticles()
    {
        return $this->attributeRepo->getLocalArticleCount();
    }

    public function exportBatchOfAllProducts($offset, $batchSize)
    {
        $sourceIds = $this->attributeRepo->findAllSourceIds($offset, $batchSize);

        return $this->export->export($sourceIds);
    }
}
