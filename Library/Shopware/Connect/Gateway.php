<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Abstract base class to store SDK related data
 *
 * You may create custom extensions of this class, if the default data stores
 * do not work for you.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
abstract class Gateway implements
    Gateway\ChangeGateway,
    Gateway\ProductGateway,
    Gateway\RevisionGateway,
    Gateway\ShopConfiguration,
    Gateway\ReservationGateway,
    Gateway\ShippingCosts
{
    /**
     * Is a feature enabled?
     *
     * @param string $feature
     * @return bool
     */
    public function isFeatureEnabled($feature)
    {
        $features = $this->getConfig('_features_');

        if ($features === null) {
            return false;
        }

        $features = unserialize($features);
        return array_key_exists($feature, $features) && $features[$feature] === true;
    }

    /**
     * Set the shop features
     *
     * @param array $features
     */
    public function setFeatures(array $features)
    {
        $this->setConfig('_features_', serialize($features));
    }

    /**
     * @return array
     */
    public function getFeatures()
    {
        $features = $this->getConfig('_features_');

        if ($features) {
            return unserialize($features);
        }

        return array();
    }
}
