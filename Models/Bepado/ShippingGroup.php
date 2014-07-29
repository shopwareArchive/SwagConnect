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

namespace Shopware\CustomModels\Bepado;

use \Doctrine\ORM\Mapping as ORM,
    \Shopware\Components\Model\ModelEntity;

/**
 *
 * @ORM\Table(name="bepado_shipping_groups")
 * @ORM\Entity()
 */
class ShippingGroup extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="sg_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string $groupName
     *
     * @ORM\Column(name="sg_group_name", type="string", nullable=true)
     */
    protected $groupName;

    /**
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\Bepado\ShippingRule", mappedBy="group")
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
     * @param \Shopware\CustomModels\Bepado\ShippingGroup $rules
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return \Shopware\CustomModels\Bepado\ShippingGroup
     */
    public function getRules()
    {
        return $this->rules;
    }
} 