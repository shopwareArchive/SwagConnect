<?php

namespace ShopwarePlugins\Connect\Tests;

trait PhpUnitTestCasePolyfillTrait
{
    /**
     * @param mixed $arg
     * @return mixed
     */
    private function captureArg(&$arg)
    {
        return $this->callback(function ($argToMock) use (&$arg) {
            $arg = $argToMock;
            return true;
        });
    }

    /**
     * @param mixed $args
     * @return mixed
     */
    private function captureAllArg(&$args)
    {
        return $this->callback(function ($argToMock) use (&$args) {
            $args[] = $argToMock;
            return true;
        });
    }
}