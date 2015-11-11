//{block name="backend/connect/model/export/list"}
Ext.define('Shopware.apps.Connect.model.export.List', {
    extend:'Shopware.apps.Connect.model.main.Product',

    fields: [
        //{block name="backend/connect/model/export/list/fields"}{/block}
        { name: 'exportStatus', type: 'string', useNull: true },
        { name: 'exportMessage', type: 'string', useNull: true }
    ]
});
//{/block}