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
 * Connect specific attributes for shopware Connect products
 *
 * @ORM\Table(name="s_plugin_connect_items")
 * @ORM\Entity(repositoryClass="AttributeRepository")
 */
class Attribute extends ModelEntity
{
    const STATUS_INSERT = 'insert';
    const STATUS_UPDATE = 'update';
    const STATUS_SYNCED = 'synced';
    const STATUS_ERROR = 'error';
    const STATUS_ERROR_PRICE = 'error-price';
    const STATUS_DELETE = 'delete';
    const STATUS_INACTIVE = 'inactive';

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
     * @ORM\Column(name="article_id", type="integer", nullable=true)
     */
    protected $articleId;


    /**
     * @var int
     *
     * @ORM\Column(name="article_detail_id", type="integer", nullable=true)
     */
    protected $articleDetailId;


    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="string", nullable=true)
     */
    protected $shopId;


    /**
     * @var string
     *
     * @ORM\Column(name="source_id", type="string", nullable=true)
     */
    protected $sourceId;


    /**
     * @var string
     *
     * @ORM\Column(name="export_status", type="string", nullable=true)
     */
    protected $exportStatus;


    /**
     * @var string
     *
     * @ORM\Column(name="export_message", type="text", nullable=true)
     */
    protected $exportMessage;

    /**
     * @var bool
     *
     * @ORM\Column(name="exported", type="boolean", nullable=true)
     */
    protected $exported;


    /**
     * @var string
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    protected $category;


    /**
     * @var float
     *
     * @ORM\Column(name="purchase_price", type="float", nullable=true)
     */
    protected $purchasePrice;


    /**
     * @var int
     *
     * @ORM\Column(name="fixed_price", type="integer", nullable=true)
     */
    protected $fixedPrice;


    /**
     * @var string
     *
     * @ORM\Column(name="update_price", type="string", nullable=true)
     */
    protected $updatePrice;


    /**
     * @var string
     *
     * @ORM\Column(name="update_image", type="string", nullable=true)
     */
    protected $updateImage;


    /**
     * @var string
     *
     * @ORM\Column(name="update_long_description", type="string", nullable=true)
     */
    protected $updateLongDescription;


    /**
     * @var string
     *
     * @ORM\Column(name="update_short_description", type="string", nullable=true)
     */
    protected $updateShortDescription;


    /**
     * @var string
     *
     * @ORM\Column(name="update_additional_description", type="string", nullable=true)
     */
    protected $updateAdditionalDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="update_name", type="string", nullable=true)
     */
    protected $updateName;

    /**
     * @var string
     *
     * @ORM\Column(name="update_main_image", type="string", nullable=true)
     */
    protected $updateMainImage;


    /**
     * @var string
     *
     * @ORM\Column(name="last_update", type="text", nullable=true)
     */
    protected $lastUpdate;


    /**
     * @var int
     *
     * @ORM\Column(name="last_update_flag", type="integer", nullable=true)
     */
    protected $lastUpdateFlag;


    /**
     * @var \Shopware\Models\Article\Article
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     * })
     */
    protected $article;


    /**
     * @var \Shopware\Models\Article\Detail
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Detail")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="article_detail_id", referencedColumnName="id")
     * })
     */
    protected $articleDetail;

    /**
    * @var int
    * @ORM\Column(name="group_id", type="integer", nullable=true)
    */
    protected $groupId;

    /**
    * @var bool
    * @ORM\Column(name="is_main_variant", type="boolean", nullable=true)
    */
    protected $isMainVariant;

    /**
     * @var string
     *
     * @ORM\Column(name="purchase_price_hash", type="string", nullable=false)
     */
    protected $purchasePriceHash;

    /**
     * @var int
     * @ORM\Column(name="offer_valid_until", type="integer", nullable=false)
     */
    protected $offerValidUntil;

    /**
     * @var string
     * @ORM\Column(name="stream", type="string", nullable=false)
     */
    protected $stream;

    /**
     * @var bool
     * @ORM\Column(name="cron_update", type="boolean", nullable=true)
     */
    protected $cronUpdate;

    /**
     * Used to store change revision for fromShop products
     *
     * @var string
     * @ORM\Column(name="revision", type="decimal", precision=20, scale=10, nullable=true)
     */
    protected $revision;

    /**
     * @param \Shopware\Models\Article\Article $article
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * @return \Shopware\Models\Article\Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param \Shopware\Models\Article\Detail $articleDetail
     */
    public function setArticleDetail($articleDetail)
    {
        $this->articleDetail = $articleDetail;
    }

    /**
     * @return \Shopware\Models\Article\Detail
     */
    public function getArticleDetail()
    {
        return $this->articleDetail;
    }

    /**
     * @return int
     */
    public function getArticleDetailId()
    {
        return $this->articleDetailId;
    }

    /**
     * @return int
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * @param array $categories
     */
    public function setCategory($categories)
    {
        if (is_string($categories)) {
            $categories = [$categories];
        }
        $this->category = json_encode($categories);
    }

    /**
     * @return array
     */
    public function getCategory()
    {
        return json_decode($this->category, true) ?: [];
    }

    /**
     * @param string $exportMessage
     */
    public function setExportMessage($exportMessage)
    {
        $this->exportMessage = $exportMessage;
    }

    /**
     * @return string
     */
    public function getExportMessage()
    {
        return $this->exportMessage;
    }

    /**
     * @param string $exportStatus
     */
    public function setExportStatus($exportStatus)
    {
        $this->exportStatus = $exportStatus;
    }

    /**
     * @return string
     */
    public function getExportStatus()
    {
        return $this->exportStatus;
    }

    /**
     * @return bool
     */
    public function isExported()
    {
        return $this->exported;
    }

    /**
     * @param bool $exported
     */
    public function setExported($exported)
    {
        $this->exported = $exported;
    }

    /**
     * @param int $fixedPrice
     */
    public function setFixedPrice($fixedPrice)
    {
        $this->fixedPrice = $fixedPrice;
    }

    /**
     * @return int
     */
    public function getFixedPrice()
    {
        return $this->fixedPrice;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $lastUpdate
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return string
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param int $lastUpdateFlag
     */
    public function setLastUpdateFlag($lastUpdateFlag)
    {
        $this->lastUpdateFlag = $lastUpdateFlag;
    }

    /**
     * Helper to inverse a given flag
     *
     * @param $flagToFlip
     */
    public function flipLastUpdateFlag($flagToFlip)
    {
        $this->lastUpdateFlag = $this->lastUpdateFlag ^ $flagToFlip;
    }

    /**
     * @return int
     */
    public function getLastUpdateFlag()
    {
        return $this->lastUpdateFlag;
    }

    /**
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * @param string $shopId
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param string $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }

    /**
     * @return string
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param string $updateImage
     */
    public function setUpdateImage($updateImage)
    {
        $this->updateImage = $updateImage;
    }

    /**
     * @return string
     */
    public function getUpdateImage()
    {
        return $this->updateImage;
    }

    /**
     * @param string $updateLongDescription
     */
    public function setUpdateLongDescription($updateLongDescription)
    {
        $this->updateLongDescription = $updateLongDescription;
    }

    /**
     * @return string
     */
    public function getUpdateLongDescription()
    {
        return $this->updateLongDescription;
    }

    /**
     * @return string
     */
    public function getUpdateAdditionalDescription()
    {
        return $this->updateAdditionalDescription;
    }

    /**
     * @param string $updateAdditionalDescription
     */
    public function setUpdateAdditionalDescription($updateAdditionalDescription)
    {
        $this->updateAdditionalDescription = $updateAdditionalDescription;
    }

    /**
     * @param string $updateName
     */
    public function setUpdateName($updateName)
    {
        $this->updateName = $updateName;
    }

    /**
     * @return string
     */
    public function getUpdateName()
    {
        return $this->updateName;
    }

    /**
     * @param string $updatePrice
     */
    public function setUpdatePrice($updatePrice)
    {
        $this->updatePrice = $updatePrice;
    }

    /**
     * @return string
     */
    public function getUpdatePrice()
    {
        return $this->updatePrice;
    }

    /**
     * @param string $updateShortDescription
     */
    public function setUpdateShortDescription($updateShortDescription)
    {
        $this->updateShortDescription = $updateShortDescription;
    }

    /**
     * @return string
     */
    public function getUpdateShortDescription()
    {
        return $this->updateShortDescription;
    }

    /**
     * @param string $updateMainImage
     */
    public function setUpdateMainImage($updateMainImage)
    {
        $this->updateMainImage = $updateMainImage;
    }

    /**
     * @return string
     */
    public function getUpdateMainImage()
    {
        return $this->updateMainImage;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param mixed $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return bool
     */
    public function isIsMainVariant()
    {
        return $this->isMainVariant;
    }

    /**
     * @param bool $isMainVariant
     */
    public function setIsMainVariant($isMainVariant)
    {
        $this->isMainVariant = $isMainVariant;
    }

    /**
     * @return int
     */
    public function getOfferValidUntil()
    {
        return $this->offerValidUntil;
    }

    /**
     * @param int $offerValidUntil
     */
    public function setOfferValidUntil($offerValidUntil)
    {
        $this->offerValidUntil = $offerValidUntil;
    }

    /**
     * @return string
     */
    public function getPurchasePriceHash()
    {
        return $this->purchasePriceHash;
    }

    /**
     * @param string $purchasePriceHash
     */
    public function setPurchasePriceHash($purchasePriceHash)
    {
        $this->purchasePriceHash = $purchasePriceHash;
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

    /**
     * @return bool
     */
    public function isCronUpdate()
    {
        return $this->cronUpdate;
    }

    /**
     * @param bool $cronUpdate
     */
    public function setCronUpdate($cronUpdate)
    {
        $this->cronUpdate = $cronUpdate;
    }

    /**
     * @return string
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @param string $revision
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;
    }
}
