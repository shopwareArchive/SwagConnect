//{block name="backend/bepado/model/import/list"}
Ext.define('Shopware.apps.Bepado.model.import.List', {
    extend:'Shopware.apps.Bepado.model.main.Product',

    fields: [
        //{block name="backend/bepado/model/import/list/fields"}{/block}
        { name: 'bepadoShopId', type: 'string', useNull: true },
        { name: 'bepadoSourceId', type: 'string', useNull: true }
    ]
});
//{/block}