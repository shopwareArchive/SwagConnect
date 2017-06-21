<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Translations;

class TranslationService implements TranslationServiceInterface
{
    /** @var \PDO|\Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;
    protected $countryCode;

    protected $translations = [];

    public function __construct($db, $language)
    {
        $this->db = $db;
        $this->countryCode = $language;
    }

    public function get($topic, $value)
    {
        switch ($topic) {
            case 'countries':
                return $this->getTranslatedCountryNames($value);
            default:
                return $value;
        }
    }

    /**
     * Translate the iso3 country name to either an english or a german string
     *
     * @param $countries
     *
     * @return mixed
     */
    private function getTranslatedCountryNames($countries)
    {
        if (in_array($this->countryCode, ['DEU', 'AUT'])) {
            $select = 'countryname';
        } else {
            $select = 'countryen';
        }

        $translatedCountries = $this->db->fetchAssoc(
            "SELECT iso3, {$select} as `name` FROM s_core_countries WHERE iso3 IN ({$this->db->quote($countries)})"
        );

        foreach ($countries as &$country) {
            $translation = $translatedCountries[$country]['name'];
            if (!empty($translation)) {
                $country = $translation;
            }
        }

        return $countries;
    }
}
