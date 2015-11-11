<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Struct class representing an address
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Address extends Struct
{
    /**
     * @var string
     */
    public $company;

    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $middleName;

    /**
     * @var string
     */
    public $surName;

    /**
     * @var string
     */
    public $street;

    /**
     * @var string
     */
    public $streetNumber;

    /**
     * @var string
     */
    public $doorCode;

    /**
     * @var string
     */
    public $additionalAddressLine;

    /**
     * @var string
     */
    public $zip;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state;

    /**
     * ISO-3 Country Code
     *
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $email;

    /**
     * Restores an address from a previously stored state array.
     *
     * @param array $state
     * @return \Shopware\Connect\Struct\Address
     */
    public static function __set_state(array $state)
    {
        return new Address($state);
    }

    /**
     * Backwards compability wrapper for read access on some properties
     *
     * @deprecated
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property)
        {
            case 'name':
                return $this->firstName . ' ' .
                    ($this->middleName ? $this->middleName . ' ' : '' ) .
                    $this->surName;
            case 'line1':
                return $this->street . ' ' . $this->streetNumber;
            case 'line2':
                return $this->additionalAddressLine;
            default:
                return parent::__get($property);
        }
    }

    /**
     * Backwards compability wrapper for read access on some properties
     *
     * @deprecated
     * @param string $property
     * @return mixed
     */
    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'name':
                if (!preg_match(
                    '(^(?P<firstName>\\S+)\\s+(?:(?P<middleName>.*)\\s+)?(?P<surName>\\S+)$)',
                    $value,
                    $matches
                )) {
                    throw new \DomainException("Invalid name provided");
                }

                $this->firstName = $matches['firstName'];
                $this->middleName = isset($matches['middleName']) ? $matches['middleName'] : '';
                $this->surName = $matches['surName'];
                break;
            case 'line1':
                if (!preg_match('(^(?P<street>.+)\\s+(?P<number>\\d+\\S*)$)', $value, $matches)) {
                    throw new \DomainException("Invalid street provided");
                }

                $this->street = $matches['street'];
                $this->streetNumber = $matches['number'];
                break;
            case 'line2':
                $this->additionalAddressLine = $value;
                break;
            default:
                return parent::__get($property);
        }
    }
}
