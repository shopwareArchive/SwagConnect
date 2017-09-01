<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Category\Category;

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
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Shopware\Models\Category\Category")
     * @ORM\JoinTable(name="s_plugin_connect_categories_to_local_categories",
     *      joinColumns={@ORM\JoinColumn(name="remote_category_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="local_category_id", referencedColumnName="id")}
     *      )
     */
    protected $localCategories;

    public function __construct()
    {
        $this->localCategories = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return bool
     */
    public function hasLocalCategories()
    {
        return !$this->localCategories->isEmpty();
    }

    /**
     * @return array
     */
    public function getLocalCategories()
    {
        return $this->localCategories->toArray();
    }

    /**
     * @param \Shopware\Models\Category\Category $localCategory
     */
    public function addLocalCategory(Category $localCategory)
    {
        if (!$this->localCategories->contains($localCategory)) {
            $this->localCategories->add($localCategory);
        }
    }

    /**
     * @param \Shopware\Models\Category\Category $localCategory
     */
    public function removeLocalCategory(Category $localCategory)
    {
        $this->localCategories->remove($localCategory);
    }
}
