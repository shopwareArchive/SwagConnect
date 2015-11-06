<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
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

use \Doctrine\ORM\Mapping as ORM,
    \Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_plugin_connect_marketplace_attr")
 * @ORM\Entity(repositoryClass="MarketplaceAttributeRepository")
 */
class MarketplaceAttribute extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $marketplaceAttribute
     *
     * @ORM\Column(name="marketplace_attribute", type="string", length=255, nullable=false)
     */
    private $marketplaceAttribute;

    /**
     * @var string $marketplaceAttribute
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