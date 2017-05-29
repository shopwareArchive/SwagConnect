<?php

namespace Tests\ShopwarePlugins\Connect\Component;


use ShopwarePlugins\Connect\Components\AutoCategoryFixer;

class AutoCategoryFixerTest extends \PHPUnit_Framework_TestCase
{
    public function testRecreateRemoteCategories()
    {
        $importService = $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\ImportService')
            ->disableOriginalConstructor()
            ->getMock();

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

        $autoCategoryFixer = new AutoCategoryFixer($importService);

        $autoCategoryFixer->recreateRemoteCategories();
    }
}
