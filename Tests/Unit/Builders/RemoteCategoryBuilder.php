<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Builders;

use Shopware\CustomModels\Connect\RemoteCategory;

trait RemoteCategoryBuilder
{
    /**
     * @var int
     */
    private $remoteCategoryId;

    /**
     * @param int $id
     * @return $this
     */
    public function newRemoteCategory($id)
    {
        $this->remoteCategoryId = $id;

        return $this;
    }

    /**
     * @return RemoteCategory
     */
    public function buildRemoteCategory()
    {
        $remoteCategory = new RemoteCategory();

        $refl = new \ReflectionObject($remoteCategory);
        $idProperty = $refl->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($remoteCategory, $this->remoteCategoryId);

        return $remoteCategory;
    }
}
