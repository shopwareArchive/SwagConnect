<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;

use Shopware\Connect\Struct\Translation;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;

class PdoProductTranslationsGateway implements ProductTranslationsGateway
{
    const CONNECT_DESCRIPTION = '__attribute_connect_product_description';

    private $db;

    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleTranslation($articleId, $languageId)
    {
        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';

        $translation = $this->db->executeQuery(
            $sql,
            ['article', $articleId, $languageId]
        )->fetchColumn();

        if ($translation === false) {
            return null;
        }

        $translation = unserialize($translation);

        return [
            'title' => $translation['txtArtikel'] ?: '',
            'shortDescription' => array_key_exists('txtshortdescription', $translation) ? $translation['txtshortdescription'] : '',
            'longDescription' => array_key_exists('txtlangbeschreibung', $translation) ? $translation['txtlangbeschreibung'] : '',
            'additionalDescription' => array_key_exists(self::CONNECT_DESCRIPTION, $translation) ? $translation[self::CONNECT_DESCRIPTION] : '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslations($articleId, $languageIds)
    {
        if (is_array($languageIds) === false || count($languageIds) === 0) {
            return [];
        }

        $inQuery = str_repeat('?,', count($languageIds) - 1) . '?';

        $sql = "SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN ($inQuery)
        ";
        $whereClause = ['article', $articleId];
        foreach ($languageIds as $languageId) {
            $whereClause[] = $languageId;
        }

        $translations = $this->db->executeQuery(
            $sql,
            $whereClause
        )->fetchAll();

        $result = [];
        foreach ($translations as $translation) {
            $languageId = $translation['objectlanguage'];
            $data = unserialize($translation['objectdata']);

            $result[$languageId] = [
                'title' => $data['txtArtikel'] ?: '',
                'shortDescription' => array_key_exists('txtshortdescription', $data) ? $data['txtshortdescription'] : '',
                'longDescription' => array_key_exists('txtlangbeschreibung', $data) ? $data['txtlangbeschreibung'] : '',
                'additionalDescription' => array_key_exists(self::CONNECT_DESCRIPTION, $data) ? $data[self::CONNECT_DESCRIPTION] : '',
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguratorGroupTranslation($groupId, $languageId)
    {
        //todo@sb: add test
        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';

        $translation = $this->db->executeQuery(
            $sql,
            ['configuratorgroup', $groupId, $languageId]
        )->fetchColumn();

        if ($translation === false) {
            return null;
        }

        $translation = unserialize($translation);

        return $translation['name'] ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguratorGroupTranslations($groupId, $languageIds)
    {
        //todo@sb: add test
        if (is_array($languageIds) === false || count($languageIds) === 0) {
            return [];
        }

        $inQuery = implode(',', $languageIds);
        $sql = "SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN ($inQuery)
        ";

        $translations = $this->db->executeQuery(
            $sql,
            ['configuratorgroup', $groupId]
        )->fetchAll();

        $result = [];
        foreach ($translations as $translation) {
            $languageId = $translation['objectlanguage'];
            $data = unserialize($translation['objectdata']);
            if (isset($data['name']) && strlen($data['name']) > 0) {
                $result[$languageId] = $data['name'];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguratorOptionTranslation($optionId, $shopId)
    {
        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';

        $translation = $this->db->executeQuery(
            $sql,
            ['configuratoroption', $optionId, $shopId]
        )->fetchColumn();

        if ($translation === false) {
            return null;
        }

        $translation = unserialize($translation);

        return $translation['name'] ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguratorOptionTranslations($optionId, $shopIds)
    {
        if (is_array($shopIds) === false || count($shopIds) === 0) {
            return [];
        }

        $inQuery = implode(',', $shopIds);
        $sql = "SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN ($inQuery)
        ";

        $translations = $this->db->executeQuery(
            $sql,
            ['configuratoroption', $optionId]
        )->fetchAll();

        $result = [];
        foreach ($translations as $translation) {
            $languageId = $translation['objectlanguage'];
            $data = unserialize($translation['objectdata']);
            if (isset($data['name']) && strlen($data['name']) > 0) {
                $result[$languageId] = $data['name'];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupTranslation($translation, $groupId, $shopId)
    {
        $this->db->query(
            'INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ',
            ['configuratorgroup', serialize(['name' => $translation]), $groupId, $shopId]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addOptionTranslation($translation, $optionId, $shopId)
    {
        $this->db->query(
            'INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ',
            ['configuratoroption', serialize(['name' => $translation]), $optionId, $shopId]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addArticleTranslation(Translation $translation, $articleId, $shopId)
    {
        $objectData = ['txtArtikel' => $translation->title];
        if (strlen($translation->longDescription)) {
            $objectData['txtlangbeschreibung'] = $translation->longDescription;
        }
        if (strlen($translation->shortDescription)) {
            $objectData['txtshortdescription'] = $translation->shortDescription;
        }

        if (strlen($translation->additionalDescription)) {
            $objectData[self::CONNECT_DESCRIPTION] = $translation->additionalDescription;
        }

        $this->db->query(
            'INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ',
            ['article', serialize($objectData), $articleId, $shopId]
        );
    }
}
