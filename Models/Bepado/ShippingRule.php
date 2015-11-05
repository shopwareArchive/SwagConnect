<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
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

use Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="connect_shipping_rules")
 * @ORM\Entity()
 */
class ShippingRule
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="sr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer $groupId
     *
     * @ORM\Column(name="sr_group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var integer $country
     *
     * @ORM\Column(name="sr_country", type="string", nullable=false)
     */
    protected $country;

    /**
     * @var integer $deliveryDays
     *
     * @ORM\Column(name="sr_delivery_days", type="integer", nullable=true)
     */
    protected $deliveryDays;

    /**
     * @var integer $price
     *
     * @ORM\Column(name="sr_price", type="float", nullable=false)
     */
    protected $price;

    /**
     * @var integer $zipPrefix
     *
     * @ORM\Column(name="sr_zip_prefix", type="string", nullable=true)
     */
    protected $zipPrefix;

    /**
     * INVERSE SIDE
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\Connect\ShippingGroup", inversedBy="rules")
     * @ORM\JoinColumn(name="sr_group_id", referencedColumnName="sg_id")
     */
    protected $group;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return int
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param int $deliveryDays
     */
    public function setDeliveryDays($deliveryDays)
    {
        $this->deliveryDays = $deliveryDays;
    }

    /**
     * @return int
     */
    public function getDeliveryDays()
    {
        return $this->deliveryDays;
    }

    /**
     * @param int $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $zipPrefix
     */
    public function setZipPrefix($zipPrefix)
    {
        $this->zipPrefix = $zipPrefix;
    }

    /**
     * @return int
     */
    public function getZipPrefix()
    {
        return $this->zipPrefix;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroup()
    {
        return $this->group;
    }
} 