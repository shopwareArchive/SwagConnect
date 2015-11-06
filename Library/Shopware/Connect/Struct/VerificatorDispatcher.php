<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class VerificatorDispatcher
{
    /**
     * Registered verificators
     *
     * @var array
     */
    protected $verificators = array();

    public function __construct(array $verificators = array())
    {
        foreach ($verificators as $class => $verificator) {
            $this->addVerificator($class, $verificator);
        }
    }

    /**
     * Add verificator
     *
     * @param string $class
     * @param Verificator $verificator
     * @return void
     */
    public function addVerificator($class, Verificator $verificator)
    {
        $this->verificators[$class] = $verificator;
    }

    /**
     * Method to verify a structs integrity
     *
     * Validates structs in the given verification groups,
     * if none specified "default" group is used.
     *
     * Throws a VerificationFailedException if the struct does not verify.
     *
     * @param Struct $struct
     * @param array $groups
     * @return void
     */
    public function verify(Struct $struct, array $groups = null)
    {
        $verificator = $this->getVerificator(get_class($struct));

        if ($verificator === null) {
            throw new \OutOfBoundsException(
                "No verificator available for class " . get_class($struct)
            );
        }

        $verificator->verify($this, $struct, $groups);
    }

    /**
     * @param string $structClass
     *
     * @return Verificator|null
     */
    private function getVerificator($structClass)
    {
        if (isset($this->verificators[$structClass])) {
            return $this->verificators[$structClass];
        }

        if (($parentClass = get_parent_class($structClass)) !== false) {
            return $this->getVerificator($parentClass);
        }

        return null;
    }
}
