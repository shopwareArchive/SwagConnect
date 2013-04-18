//{block name="backend/bepado/model/export/list"}
Ext.define('Shopware.apps.Bepado.model.export.List', {
    extend:'Shopware.apps.Bepado.model.main.Product',

    fields: [
        //{block name="backend/bepado/model/export/list/fields"}{/block}
        { name: 'exportStatus', type: 'string', useNull: true },
        { name: 'exportMessage', type: 'string', useNull: true }
    ]
});
//{/block}