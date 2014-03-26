<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

/**
 * Abstract base class for revision providers
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
abstract class RevisionProvider
{
    /**
     * Get next revision
     *
     * @return string
     */
    abstract public function next();
}
