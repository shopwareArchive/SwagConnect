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
     * @ORM\Column(name="category_key", type="string", length=255, unique=true, nullable=false)
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
     * @ORM\Column(name="local_category_id", type="integer", nullable=true)
     */
    protected $localCategoryId;

    /**
     * @var \Shopware\Models\Category\Category
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Category\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="local_category_id", referencedColumnName="id")
     * })
     */
    protected $localCategory;

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
     * @return int
     */
    public function getLocalCategoryId()
    {
        return $this->localCategoryId;
    }

    /**
     * @param int $id
     */
    public function setLocalCategoryId($id)
    {
        $this->localCategoryId = $id;
    }

    /**
     * @return \Shopware\Models\Category\Category
     */
    public function getLocalCategory()
    {
        return $this->localCategory;
    }

    /**
     * @param \Shopware\Models\Category\Category $localCategory
     */
    public function setLocalCategory($localCategory)
    {
        $this->localCategory = $localCategory;
    }
}
