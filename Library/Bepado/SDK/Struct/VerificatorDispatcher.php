<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version $Revision$
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
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param Struct $struct
     * @return void
     */
    public function verify(Struct $struct)
    {
        $verificator = $this->getVerificator(get_class($struct));

        if ($verificator === null) {
            throw new \OutOfBoundsException(
                "No verificator available for class " . get_class($struct)
            );
        }

        $verificator->verify($this, $struct);
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
