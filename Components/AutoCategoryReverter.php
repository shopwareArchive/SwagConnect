<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

class AutoCategoryReverter
{
    /**
     * @var \ShopwarePlugins\Connect\Components\ImportService
     */
    private $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function recreateRemoteCategories()
    {
        $remoteItems = $this->importService->getArticlesWithAutoImportedCategories();
        $this->importService->storeRemoteCategories($remoteItems);
        $articleIds = array_keys($remoteItems);
        // fetch categories which are imported via auto import
        $remoteCategoryIds = $this->importService->fetchRemoteCategoriesByArticleIds($articleIds);
        // deactivate all imported categories via auto import
        $this->importService->deactivateLocalCategoriesByIds($remoteCategoryIds);
        // unassign all categories from articles
        // while auto import categories was enabled
        $this->importService->unAssignArticleCategories($articleIds);
    }
}
