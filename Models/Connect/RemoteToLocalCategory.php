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
 * Describes Shopware Connect categories to Local Categories
 *
 * @ORM\Table(name="s_plugin_connect_categories_to_local_categories")
 * @ORM\Entity()
 */
class RemoteToLocalCategory extends ModelEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="remote_category_id", type="integer", nullable=false)
     */
    protected $remoteCategoryId;

    /**
     * @var \Shopware\CustomModels\Connect\RemoteCategory
     *
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\Connect\RemoteCategory", inversedBy="remoteToLocalCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="remote_category_id", referencedColumnName="id")
     * })
     */
    protected $remoteCategory;

    /**
     * @var int
     *
     * @ORM\Column(name="local_category_id", type="integer", nullable=false)
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
     * @var string
     *
     * @ORM\Column(name="stream", type="string", length=255, nullable = true)
     */
    protected $stream;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return RemoteCategory
     */
    public function getRemoteCategory()
    {
        return $this->remoteCategory;
    }

    /**
     * @param RemoteCategory $remoteCategory
     */
    public function setRemoteCategory($remoteCategory)
    {
        $this->remoteCategory = $remoteCategory;
    }

    /**
     * @return Category
     */
    public function getLocalCategory()
    {
        return $this->localCategory;
    }

    /**
     * @param Category $localCategory
     */
    public function setLocalCategory($localCategory)
    {
        $this->localCategory = $localCategory;
    }

    /**
     * @return string
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param string $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
    }
}
