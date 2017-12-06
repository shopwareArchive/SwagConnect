<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * Describes Shopware Connect categories
 *
 * @ORM\Table(name="s_plugin_connect_categories")
 * @ORM\Entity(repositoryClass="RemoteCategoryRepository")
 */
class RemoteCategory extends ModelEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_key", type="string", length=255, nullable=false)
     */
    protected $categoryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    protected $label;

    /**
     * @var int
     *
     * @ORM\Column(name="shop_id", type="integer", nullable=true)
     */
    protected $shopId;

    /**
     * @deprecated
     */
    protected $localCategoryId;

    /**
     * @deprecated
     */
    protected $localCategory;

    /**
     * @deprecated
     * hack because doctrine crashes because old cache
     */
    protected $localCategories;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\Connect\RemoteToLocalCategory", mappedBy="remoteCategory")
     */
    protected $remoteToLocalCategories;

    public function __construct()
    {
        $this->remoteToLocalCategories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCategoryKey()
    {
        return $this->categoryKey;
    }

    /**
     * @param string $categoryKey
     */
    public function setCategoryKey($categoryKey)
    {
        $this->categoryKey = $categoryKey;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRemoteToLocalCategories()
    {
        return $this->remoteToLocalCategories;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $remoteToLocalCategories
     */
    public function setRemoteToLocalCategories($remoteToLocalCategories)
    {
        $this->remoteToLocalCategories = $remoteToLocalCategories;
    }

    /**
     * @deprecated
     */
    public function getLocalCategoryId()
    {
        return $this->localCategoryId;
    }

    /**
     * @param int $id
     * @deprecated
     */
    public function setLocalCategoryId($id)
    {
        $this->localCategoryId = $id;
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param int $shopId
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    }
}
