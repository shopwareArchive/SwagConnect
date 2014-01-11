<?php

namespace   Shopware\CustomModels\Bepado;
use         \Shopware\Components\Model\ModelRepository;

class ConfigRepository extends ModelRepository
{


    /**
     * Read a given config value by name
     *
     * @param $name
     * @param null $default
     * @return null
     */
    public function getConfig($name, $default=null)
    {
        $model = $this->findOneBy(array('name' => $name));

        if ($model) {
            return $model->getValue();
        }

        return $default;

    }

    /**
     * Set a given config value
     *
     * @param $name
     * @param $value
     */
    public function setConfig($name, $value)
    {
        $model = $this->findOneBy(array('name' => $name));

        if (!$model) {
            $model = new Config();
            $this->getEntityManager()->persist($model);
        }

        $model->setName($name);
        $model->setValue($value);
    }

}
