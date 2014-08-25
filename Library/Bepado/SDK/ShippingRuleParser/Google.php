<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingRuleParser;

use Bepado\SDK\ShippingRuleParser;
use Bepado\SDK\Struct\ShippingRules;
use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Exception\ParserException;
use Bepado\SDK\Countries;

class Google extends ShippingRuleParser
{
    /**
     * Tokens
     *
     * @var array
     */
    private $tokens = array(
        'T_WHITESPACE' => '(\\A\\s+)S',
        // ISO 4217 (23.06.2014)
        'T_CURRENCY' => '(\\A(?P<value>AED|AFN|ALL|AMD|ANG|AOA|ARS|AUD|AWG|AZN|BAM|BBD|BDT|BGN|BHD|BIF|BMD|BND|BOB|BOV|BRL|BSD|BTN|BWP|BYR|BZD|CAD|CDF|CHE|CHF|CHW|CLF|CLP|CNY|COP|COU|CRC|CUC|CUP|CVE|CZK|DJF|DKK|DOP|DZD|EGP|ERN|ETB|EUR|FJD|FKP|GBP|GEL|GHS|GIP|GMD|GNF|GTQ|GYD|HKD|HNL|HRK|HTG|HUF|IDR|ILS|INR|IQD|IRR|ISK|JMD|JOD|JPY|KES|KGS|KHR|KMF|KPW|KRW|KWD|KYD|KZT|LAK|LBP|LKR|LRD|LSL|LTL|LYD|MAD|MDL|MGA|MKD|MMK|MNT|MOP|MRO|MUR|MVR|MWK|MXN|MXV|MYR|MZN|NAD|NGN|NIO|NOK|NPR|NZD|OMR|PAB|PEN|PGK|PHP|PKR|PLN|PYG|QAR|RON|RSD|RUB|RWF|SAR|SBD|SCR|SDG|SSP|SEK|SGD|SHP|SLL|SOS|SRD|STD|SVC|SYP|SZL|THB|TJS|TMT|TND|TOP|TRY|TTD|TWD|TZS|UAH|UGX|USD|UYI|UYU|UZS|VEF|VND|VUV|WST|XAF|XCD|XOF|XPF|YER|ZAR|ZMW|ZWL)(?![A-Z]))S',
        // ISO 3166-1 (23.06.2014)
        'T_COUNTRY' => '(\\A(?P<value>AF|EG|AX|AL|DZ|AS|VI|AD|AO|AI|AQ|AG|GQ|AR|AM|AW|AC|AZ|ET|AU|BS|BH|BD|BB|BY|BE|BZ|BJ|BM|BT|BO|BQ|BA|BW|BV|BR|VG|IO|BN|BG|BF|BU|BI|EA|CL|CN|CP|CK|CR|CI|CW|DK|DD|DE|DG|DM|DO|DJ|EC|SV|ER|EE|CE|EU|FK|FO|FJ|FI|FR|FX|GF|PF|TF|GA|GM|GE|GH|GI|GD|GR|GL|GP|GU|GT|GG|GN|GW|GY|HT|HM|HN|HK|IN|ID|IM|IQ|IR|IE|IS|IL|IT|JM|JP|YE|JE|JO|YU|KY|KH|CM|CA|IC|CV|KZ|QA|KE|KG|KI|CC|CO|KM|CD|CG|KP|KR|HR|CU|KW|LA|LS|LV|LB|LR|LY|LI|LT|LU|MO|MG|MW|MY|MV|ML|MT|MA|MH|MQ|MR|MU|YT|MK|MX|FM|MD|MC|MN|ME|MS|MZ|MM|NA|NR|NP|NC|NZ|NT|NI|NL|AN|NE|NG|NU|MP|NF|NO|OM|AT|TL|PK|PS|PW|PA|PG|PY|PE|PH|PN|PL|PT|PR|RE|RW|RO|RU|SB|BL|MF|ZM|WS|SM|ST|SA|SE|CH|SN|RS|CS|SC|SL|ZW|SG|SX|SK|SI|SO|ES|LK|SH|KN|LC|PM|VC|ZA|SD|GS|SS|SR|SJ|SZ|SY|TJ|TW|TZ|TH|TG|TK|TO|TT|TA|TD|CZ|CS|TN|TR|TM|TC|TV|SU|UG|UA|HU|UM|UY|UZ|VU|VA|VE|AE|US|GB|UK|VN|WF|CX|EH|ZR|CF|CY)(?![A-Z]))S',
        'T_PRICE' => '(\\A(?P<value>\\d+\\.\\d+))S',
        'T_ZIP' => '(\\A(?P<value>\\d{1,5}\\*?))S',
        'T_REGION' => '(\\A(?P<value>[A-Z]{2,3}))S',
        'T_ELEMENT_SEPARATOR' => '(\\A:)S',
        'T_RULE_SEPARATOR' => '(\\A,)S',
        'T_DELIVERY_NAME' => '(\\A(?P<value>[^,:[]+))S',
        'T_DELIVERY_TIME' => '(\\A\\[(?P<value>\\d+[DH])\\])S',
        'T_STRING' => '(\\A(?P<value>[^,:]+))S',
    );

    /**
     * Token names
     *
     * @var array
     */
    private $tokenNames = array(
        'T_WHITESPACE' => 'Whitespace',
        'T_CURRENCY' => 'Currency code (ISO 4217) (eg. EUR)',
        'T_COUNTRY' => 'Country Code (ISO 3166-1) (eg. DE)',
        'T_PRICE' => 'Price (english locale) (eg. 1.95)',
        'T_ZIP' => 'Zip code or region (eg. 45886 or 45*)',
        'T_REGION' => 'Region identifier (eg. NRW)',
        'T_ELEMENT_SEPARATOR' => 'Element separator ":"',
        'T_RULE_SEPARATOR' => 'Rule separator ","',
        'T_DELIVERY_NAME' => 'Delivery name (free text)',
        'T_DELIVERY_TIME' => 'Delivery time (eg. [5D] or [48H])',
        'T_STRING' => 'random text',
        'T_EOF' => 'end of input',
    );

    /**
     * Delivery times
     *
     * If you extend this array, also adapt the T_DELIVERY_TIME rule
     *
     * @var array
     */
    private $deliveryTimes = array(
        'D' => 1,
        'H' => 24,
    );

    /**
     * Countries mapping
     *
     * @var Countries
     */
    private $countries;

    /**
     * @param Countries $countries
     * @return void
     */
    public function __construct($countries = null)
    {
        $this->countries = $countries ?: new Countries();
    }

    /**
     * Parse shipping rules out of string
     *
     * @param string $string
     * @return Struct\ShippingRules
     */
    public function parseString($string)
    {
        if (!$string) {
            return null;
        }

        $tokens = $this->tokenize($string);
        return $this->reduceRules($tokens);
    }

    /**
     * Tokenize
     *
     * @param string $string
     * @return array[]
     */
    protected function tokenize($string)
    {
        $tokens = array();
        $offset = 0;
        while (strlen($string)) {
            foreach ($this->tokens as $name => $regularExpression) {
                if (preg_match($regularExpression, $string, $match)) {
                    $tokens[] = (object) array(
                        'type' => $name,
                        'value' => isset($match['value']) ? $match['value'] : null,
                        'position' => $offset,
                    );

                    $string = substr($string, strlen($match[0]));
                    $offset += strlen($match[0]);
                    continue 2;
                }
            }

            throw new ParserException("Cannot parse string at position $offset: $string.");
        }

        $tokens[] = (object) array(
            'type' => 'T_EOF',
            'value' => null,
            'position' => $offset,
        );

        return array_values(
            array_filter(
                $tokens,
                function ($token) {
                    return !in_array($token->type, array('T_WHITESPACE'));
                }
            )
        );
    }

    /**
     * Reduce tokens into shipping rules
     *
     * @param array $tokens
     * @return ShippingRules
     */
    protected function reduceRules(array &$tokens)
    {
        $rules = array();
        while (count($tokens)) {
            $rules[] = $this->reduceRule($tokens);
        }

        return new ShippingRules(array('rules' => $rules));
    }

    /**
     * reduceRule
     *
     * @param array $tokens
     * @return Rule\Product
     */
    protected function reduceRule(array &$tokens)
    {
        $rule = new Rule\Product();

        $country = $this->read($tokens, array('T_COUNTRY'), true);
        $rule->country = $country ? $this->countries->getISO3($country) : null;
        $this->read($tokens, array('T_ELEMENT_SEPARATOR', 'T_COUNTRY'));

        $rule->zipRange = $this->read($tokens, array('T_ZIP'), true);
        $rule->region = $this->read($tokens, array('T_REGION', 'T_COUNTRY'), true);
        $this->read($tokens, array('T_ELEMENT_SEPARATOR', 'T_ZIP', 'T_REGION', 'T_COUNTRY'));

        $rule->service = trim($this->read($tokens, array('T_DELIVERY_NAME', 'T_COUNTRY', 'T_REGION'), true));
        $rule->deliveryWorkDays = $this->convertDeliveryTime($this->read($tokens, array('T_DELIVERY_TIME'), true));
        $this->read($tokens, array('T_ELEMENT_SEPARATOR', 'T_DELIVERY_NAME'));

        $rule->price = (float) $this->read($tokens, array('T_PRICE'));
        $rule->currency = $this->read($tokens, array('T_CURRENCY'));
        $this->read($tokens, array('T_RULE_SEPARATOR', 'T_EOF'));

        return $rule;
    }

    /**
     * Read token from stream
     *
     * @param array &$tokens
     * @param array $types
     * @param $optional = false
     * @return mixed
     */
    protected function read(array &$tokens, array $types, $optional = false)
    {
        if (!isset($tokens[0])) {
            throw new ParserException("Empty token stack – expected one of: " . $this->getTokenNames($types));
        }

        if ($optional &&
            !in_array($tokens[0]->type, $types)) {
            return null;
        }

        $token = array_shift($tokens);
        if (!in_array($token->type, $types)) {
            throw new ParserException(
                sprintf(
                    "Unexpected %s at position %d – expected one of: %s",
                    $this->getTokenNames($token->type),
                    $token->position,
                    $this->getTokenNames($types)
                )
            );
        }

        return $token->value;
    }

    /**
     * Get token names
     *
     * @param array $tokens
     * @return string
     */
    protected function getTokenNames($tokens)
    {
        if (!is_array($tokens)) {
            $tokens = array($tokens);
        }

        $names = array();
        foreach ($tokens as $token) {
            $names[] = $this->tokenNames[$token];
        }

        return implode(', ', $names);
    }

    /**
     * Convert delivery time
     *
     * @param string $definition
     * @return int
     */
    protected function convertDeliveryTime($definition)
    {
        if ($definition === null) {
            return null;
        }

        foreach ($this->deliveryTimes as $type => $factor) {
            if (preg_match('(^(?P<value>\\d+)' . $type . '$)', $definition, $match)) {
                return $match['value'] / $factor;
            }
        }

        throw new ParserException("Unparsable delivery time $definition");
    }
}
