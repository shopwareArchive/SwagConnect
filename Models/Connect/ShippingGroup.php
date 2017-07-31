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
 * @ORM\Table(name="connect_shipping_groups")
 * @ORM\Entity()
 */
class ShippingGroup extends ModelEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="sg_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="sg_group_name", type="string", nullable=true)
     */
    protected $groupName;

    /**
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\Connect\ShippingRule", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $rules;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $groupName
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @param \Shopware\CustomModels\Connect\ShippingGroup $rules
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return \Shopware\CustomModels\Connect\ShippingGroup
     */
    public function getRules()
    {
        return $this->rules;
    }
}
