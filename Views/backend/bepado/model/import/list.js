//{block name="backend/connect/model/import/list"}
Ext.define('Shopware.apps.Connect.model.import.List', {
    extend:'Shopware.apps.Connect.model.main.Product',

    fields: [
        //{block name="backend/connect/model/import/list/fields"}{/block}
        { name: 'shopId', type: 'string', useNull: true },
        { name: 'sourceId', type: 'string', useNull: true },
        { name: 'status', type: 'string', useNull: true }
    ]
});
//{/block}