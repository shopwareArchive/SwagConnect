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
 * @ORM\Table(name="s_plugin_connect_marketplace_attr")
 * @ORM\Entity(repositoryClass="MarketplaceAttributeRepository")
 */
class MarketplaceAttribute extends ModelEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="marketplace_attribute", type="string", length=255, nullable=false)
     */
    private $marketplaceAttribute;

    /**
     * @var string
     *
     * @ORM\Column(name="local_attribute", type="string", length=255, nullable=false)
     */
    private $localAttribute;

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
    public function getLocalAttribute()
    {
        return $this->localAttribute;
    }

    /**
     * @param string $localAttribute
     */
    public function setLocalAttribute($localAttribute)
    {
        $this->localAttribute = $localAttribute;
    }

    /**
     * @return string
     */
    public function getMarketplaceAttribute()
    {
        return $this->marketplaceAttribute;
    }

    /**
     * @param string $marketplaceAttribute
     */
    public function setMarketplaceAttribute($marketplaceAttribute)
    {
        $this->marketplaceAttribute = $marketplaceAttribute;
    }
}
