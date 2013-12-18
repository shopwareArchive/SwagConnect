<?php

namespace   Shopware\CustomModels\Bepado;
use         \Shopware\Components\Model\ModelRepository;

class ConfigRepository extends ModelRepository
{

    public function getConfig($name, $default=null)
    {
        $model = $this->findOneBy(array('name' => $name));

        if ($model) {
            return $model->getValue();
        }

        return $default;

    }

    public function setConfig($name, $value)
    {
        $model = $this->findOneBy(array('name' => $name));

        if (!$model) {
            $model = new Config();
            $this->getManager->persist($model);
        }

        $model->setName($name);
        $model->setValue($value);

    }

}
