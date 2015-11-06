<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;
use Shopware\Connect\Exception\VerificationFailedException;

/**
 * Visitor verifying integrity of struct classes
 *
 * Struct classes can be validated using different groups of validation rules.
 * You can pass the groups as a second argument to the {@link
 * VerificatorDispatcher#verify} method. Each Verificator has to implement the
 * "default" group of validation rules, everything else is optional.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
abstract class Verificator
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a VerificationFailedException if the struct does not verify.
     *
     * @param VerificatorDispatcher $dispatcher
     * @param Struct $struct
     * @param array $groups
     * @return void
     * @throws RuntimeException if the struct is not valid
     */
    final public function verify(VerificatorDispatcher $dispatcher, Struct $struct, array $groups = null)
    {
        if ($groups === null) {
            $groups = array('default');
        }

        $verifyMethods = array();

        foreach ($groups as $group) {
            if ($group === 'all') {
                $verifyMethods = array_filter(
                    get_class_methods($this),
                    function ($method) { return strpos($method, 'verify') === 0; }
                );
            } else {
                $method = sprintf('verify%s', ucfirst($group));

                if (!method_exists($this, $method)) {
                    $this->fail(
                        "Cannot verify class '%s' because no validation rules exist for group '%s'.",
                        get_class($struct),
                        $group
                    );
                }

                $verifyMethods[] = $method;
            }
        }

        foreach ($verifyMethods as $verifyMethod) {
            $this->$verifyMethod($dispatcher, $struct);
        }
    }

    /**
     * Fail the verification by throwing a VerificationFailedException
     *
     * @param string $message
     * @param ...mixed $args
     * @throws \Shopware\Connect\Exception\VerificationFailedException
     */
    protected function fail($message)
    {
        $args = func_get_args();

        throw new VerificationFailedException(call_user_func_array('sprintf', $args));
    }

    /**
     * Default verification rules for this data structure.
     */
    abstract protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct);
}
