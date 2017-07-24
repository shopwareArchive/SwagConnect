<?php

namespace ShopwarePlugins\Connect\Tests;

trait UnitTestCaseTrait
{
    /**
     * Will set mock argument to given variable by reference.
     * After mock call this variable can be used to assert values.
     * Useful when some mock is called with arguments which are not
     * returned as result of his function.
     *
     * @param mixed $arg
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    private function captureArg(&$arg)
    {
        return $this->callback(function ($argToMock) use (&$arg) {
            $arg = $argToMock;
            return true;
        });
    }

    /**
     * Will set mock arguments to given variable by reference.
     * Every argument is value of an indexed array.
     * Useful when same mock method is called multiple times.
     *
     * @param mixed $args
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    private function captureAllArg(&$args)
    {
        return $this->callback(function ($argToMock) use (&$args) {
            $args[] = $argToMock;
            return true;
        });
    }
}