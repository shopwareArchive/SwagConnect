<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\CustomModels\Connect;

use \Doctrine\ORM\Mapping as ORM;
use \Shopware\Components\Model\ModelEntity;

/**
 * Describes Shopware Connect categories
 *
 * @ORM\Table(name="s_plugin_connect_categories")
 * @ORM\Entity(repositoryClass="RemoteCategoryRepository")
 */
class RemoteCategory extends ModelEntity
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var string $categoryKey
     *
     * @ORM\Column(name="category_key", type="string", length=255, unique=true, nullable=false)
     */
    protected $categoryKey;

    /**
     * @var string $label
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    protected $label;

    /**
     * @var integer
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
     * @return integer
     */
    public function getLocalCategoryId()
    {
        return $this->localCategoryId;
    }

    /**
     * @param integer $id
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