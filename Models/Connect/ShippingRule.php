<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="connect_shipping_rules")
 * @ORM\Entity()
 */
class ShippingRule
{
    /**
     * @var int
     *
     * @ORM\Column(name="sr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="sr_group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="sr_country", type="string", nullable=false)
     */
    protected $country;

    /**
     * @var int
     *
     * @ORM\Column(name="sr_delivery_days", type="integer", nullable=true)
     */
    protected $deliveryDays;

    /**
     * @var int
     *
     * @ORM\Column(name="sr_price", type="float", nullable=false)
     */
    protected $price;

    /**
     * @var int
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
