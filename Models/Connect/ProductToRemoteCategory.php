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

use Doctrine\ORM\Mapping as ORM,
    Shopware\Components\Model\ModelEntity;

/**
 * Contains oneToMany connection
 * One product belongs to many remote categories
 *
 * @ORM\Table(name="s_plugin_connect_product_to_categories")
 * @ORM\Entity(repositoryClass="ProductToRemoteCategoryRepository")
 */
class ProductToRemoteCategory extends ModelEntity
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var integer
     * @ORM\Column(name="connect_category_id", type="integer", nullable=false)
     */
    protected $connectCategoryId;

    /**
     * @var \Shopware\CustomModels\Connect\RemoteCategory
     *
     * @ORM\OneToOne(targetEntity="Shopware\CustomModels\Connect\RemoteCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="connect_category_id", referencedColumnName="id")
     * })
     */
    protected $connectCategory;

    /**
     * @var integer
     * @ORM\Column(name="articleID", type="integer", nullable=false)
     */
    protected $articleId;

    /**
     * @var \Shopware\Models\Article\Article
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="articleID", referencedColumnName="id")
     * })
     */
    protected $article;

    /**
     * @var \Shopware\CustomModels\Connect\Attribute
     *
     * @ORM\OneToOne(targetEntity="Shopware\CustomModels\Connect\Attribute")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="articleID", referencedColumnName="article_id")
     * })
     */
    protected $connectAttribute;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Shopware\Models\Article\Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param \Shopware\Models\Article\Article $article
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * @return int
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * @param int $articleId
     */
    public function setArticleId($articleId)
    {
        $this->articleId = $articleId;
    }

    /**
     * @return int
     */
    public function getConnectCategoryId()
    {
        return $this->connectCategoryId;
    }

    /**
     * @param int $connectCategoryId
     */
    public function setConnectCategoryId($connectCategoryId)
    {
        $this->connectCategoryId = $connectCategoryId;
    }

    /**
     * @return RemoteCategory
     */
    public function getConnectCategory()
    {
        return $this->connectCategory;
    }

    /**
     * @param RemoteCategory $connectCategory
     */
    public function setConnectCategory($connectCategory)
    {
        $this->connectCategory = $connectCategory;
    }
}