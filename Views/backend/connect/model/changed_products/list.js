//{block name="backend/connect/model/changed_products/list"}
Ext.define('Shopware.apps.Connect.model.changed_products.List', {
    extend:'Shopware.apps.Connect.model.import.List',

    fields: [
        //{block name="backend/connect/model/changed_products/list/fields"}{/block}
        { name: 'description', type: 'string', useNull: true },
        { name: 'descriptionLong', type: 'string', useNull: true },
        { name: 'additionalDescription', type: 'string', useNull: true },
        { name: 'images', type: 'string', useNull: true },
        { name: 'lastUpdate', type: 'string', useNull: true },
        { name: 'lastUpdateFlag', type: 'int', useNull: true }

    ]
});
//{/block}