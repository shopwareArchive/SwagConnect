<?php
/**
 * This class is used to revert category changes due to the option "auto-import categories" was enabled
 *
 * - Add missing remote categories in Connect tables
 * - Assign remote products to these categories
 * - Deactivate all auto imported categories
 */

namespace ShopwarePlugins\Connect\Components;


class AutoCategoryFixer
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