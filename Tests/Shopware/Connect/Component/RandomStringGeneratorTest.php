<?php

namespace Tests\ShopwarePlugins\Connect\Component;


use ShopwarePlugins\Connect\Components\RandomStringGenerator;

class RandomStringGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ShopwarePlugins\Connect\Components\RandomStringGenerator
     */
    public $randomStringGenerator;

    public function setUp()
    {
        $this->randomStringGenerator = new RandomStringGenerator();
    }


    public function testStringUniqueness()
    {
        $string = 'Lorem ipsum';

        $this->assertNotEmpty($this->randomStringGenerator->generate($string));
        $this->assertNotEquals(
            $this->randomStringGenerator->generate($string),
            $this->randomStringGenerator->generate($string)
        );
    }
}