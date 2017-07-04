<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Translations;

class LocaleMapper
{
    private static $languageCodes = [
        'aa' => 'aa_DJ', 'af' => 'af_NA', 'am' => 'am_ET', 'ar' => 'ar_AE', 'as' => 'as_IN', 'az' => 'az_AZ',
        'be' => 'be_BY', 'bg' => 'bg_BG', 'bh' => 'bh_BD', 'bn' => 'bn_IN', 'bo' => 'bo_CN', 'ca' => 'ca_ES',
        'cs' => 'cs_CZ', 'cy' => 'cy_GB', 'da' => 'da_DK', 'de' => 'de_DE', 'dz' => 'dz_BT', 'el' => 'el_CY',
        'en' => 'en_GB', 'es' => 'es_ES', 'et' => 'et_EE', 'eu' => 'eu_ES', 'fa' => 'fa_AF', 'fi' => 'fi_FI',
        'fo' => 'fo_FO', 'fr' => 'fr_FR', 'ga' => 'ga_IE', 'gl' => 'gl_ES', 'gu' => 'gu_IN', 'ha' => 'ha_GH',
        'hi' => 'hi_IN', 'he' => 'he_IL', 'hr' => 'hr_HR', 'hu' => 'hu_HU', 'hy' => 'hy_AM', 'id' => 'id_ID',
        'is' => 'is_IS', 'it' => 'it_IT', 'ja' => 'ja_JP', 'ka' => 'ka_GE', 'kk' => 'kk_KZ', 'kl' => 'kl_GL',
        'km' => 'km_KH', 'kn' => 'kn_IN', 'ko' => 'ko_KR', 'ku' => 'ku_IQ', 'ky' => 'ky_KG', 'ln' => 'ln_CD',
        'lo' => 'lo_LA', 'lt' => 'lt_LT', 'lv' => 'lv_LV', 'mk' => 'mk_MK', 'ml' => 'ml_IN', 'mn' => 'mn_MN',
        'mr' => 'mr_IN', 'ms' => 'ms_BN', 'mt' => 'mt_MT', 'my' => 'my_MM', 'ne' => 'ne_IN', 'nl' => 'nl_NL',
        'oc' => 'oc_FR', 'om' => 'om_ET', 'or' => 'or_IN', 'pa' => 'pa_IN', 'pl' => 'pl_PL', 'ps' => 'ps_AF',
        'pt' => 'pt_BR', 'ro' => 'ro_RO', 'ru' => 'ru_RU', 'rw' => 'rw_RW', 'sa' => 'sa_IN', 'sh' => 'sh_BA',
        'si' => 'si_LK', 'sk' => 'sk_SK', 'sl' => 'sl_SI', 'so' => 'so_DJ', 'sq' => 'sq_AL', 'sr' => 'sr_BA',
        'ss' => 'ss_SZ', 'st' => 'st_LS', 'sv' => 'sv_FI', 'sw' => 'sw_KE', 'ta' => 'ta_IN', 'te' => 'te_IN',
        'tg' => 'tg_TJ', 'th' => 'th_TH', 'ti' => 'ti_ER', 'tn' => 'tn_ZA', 'to' => 'to_TO', 'tr' => 'tr_TR',
        'ts' => 'ts_ZA', 'tt' => 'tt_RU', 'ug' => 'ug_CN', 'uk' => 'uk_UA', 'ur' => 'ur_IN', 'uz' => 'uz_AF',
        'vi' => 'vi_VN', 'wo' => 'wo_SN', 'xh' => 'xh_ZA', 'yo' => 'yo_NG', 'zh' => 'zh_CN', 'zu' => 'zu_ZA',
    ];

    /**
     * Returns ISO 639-1 language code by given
     * Shopware locale
     *
     * @param string $locale
     * @return string|null
     */
    public static function getIso639($locale)
    {
        $key = array_search($locale, self::$languageCodes);
        if ($key !== false) {
            return $key;
        }

        return null;
    }

    /**
     * Returns Shopware locale by given
     * ISO 639-1 language code
     *
     * @param string $locale
     * @return string|null
     */
    public static function getShopwareLocale($locale)
    {
        return self::$languageCodes[$locale];
    }
}
