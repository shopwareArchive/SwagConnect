<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelRepository;

/**
 * Class RemoteCategoryRepository
 * @package Shopware\CustomModels\Connect
 */
class RemoteCategoryRepository extends ModelRepository
{
    public function findAllCategoriesWithoutAssignments()
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
    }
}
