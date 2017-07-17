<?php
/**
* (c) shopware AG <info@shopware.com>
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ShopwarePlugins\Connect\Components;


interface VariantRegenerator
{
    /**
     * @param int $articleId
     * @param array $sourceIds
     */
    public function setInitialSourceIds($articleId, array $sourceIds);

    /**
     * @param int $articleId
     * @param array $sourceIds
     */
    public function setCurrentSourceIds($articleId, array $sourceIds);

    /**
     * @param int $articleId
     */
    public function generateChanges($articleId);
}