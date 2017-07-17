<?php

namespace Tests\ShopwarePlugins\Connect\Component;


use ShopwarePlugins\Connect\Components\AutoCategoryReverter;
use ShopwarePlugins\Connect\Components\ImportService;

class AutoCategoryReverterTest extends \PHPUnit_Framework_TestCase
{
    public function testRecreateRemoteCategories()
    {
        $importService = $this->createMock(ImportService::class);

        $remoteItems = [
            5 => [
                '/Kleidung' => 'Kleidung',
                '/Kleidung/Hosen' => 'Hosen',
                '/Kleidung/Hosentraeger' => 'Hosentraeger',
            ],
            8 => [
                '/Kleidung/Hosentraeger' => 'Hosentraeger',
                '/Kleidung/Nahrung & Getraenke' => 'Nahrung & Getraenke',
                '/Kleidung/Nahrung & Getraenke/Alkoholische Getränke' => 'Alkoholische Getränke',
            ]
        ];
        $importService->expects($this->once())
            ->method('getArticlesWithAutoImportedCategories')
            ->willReturn($remoteItems);

        $importService->expects($this->once())
            ->method('storeRemoteCategories')
            ->with($remoteItems);

        $remoteCategoryIds = [10, 11, 12];

        $importService->expects($this->once())
            ->method('fetchRemoteCategoriesByArticleIds')
            ->with(array_keys($remoteItems))
            ->willReturn($remoteCategoryIds);

        $importService->expects($this->once())
            ->method('deactivateLocalCategoriesByIds')
            ->with($remoteCategoryIds);

        $importService->expects($this->once())
            ->method('unAssignArticleCategories')
            ->with(array_keys($remoteItems));

        $autoCategoryReverter = new AutoCategoryReverter($importService);

        $autoCategoryReverter->recreateRemoteCategories();
    }
}
