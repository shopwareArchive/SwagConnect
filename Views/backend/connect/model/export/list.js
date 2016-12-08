//{block name="backend/connect/model/export/list"}
Ext.define('Shopware.apps.Connect.model.export.List', {
    extend:'Shopware.apps.Connect.model.main.Product',

    fields: [
        //{block name="backend/connect/model/export/list/fields"}{/block}
        { name: 'customProduct', type: 'int', useNull: true },
        { name: 'exportStatus', type: 'string', useNull: true },
        { name: 'exportMessage', type: 'string', useNull: true },
        { name: 'cronUpdate', type: 'int', useNull: true }
    ]
});
//{/block}