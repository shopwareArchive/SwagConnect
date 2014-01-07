//{block name="backend/bepado/model/changed_products/list"}
Ext.define('Shopware.apps.Bepado.model.changed_products.List', {
    extend:'Shopware.apps.Bepado.model.import.List',

    fields: [
        //{block name="backend/bepado/model/changed_products/list/fields"}{/block}
        { name: 'description', type: 'string', useNull: true },
        { name: 'descriptionLong', type: 'string', useNull: true },
        { name: 'images', type: 'string', useNull: true },
        { name: 'lastUpdate', type: 'string', useNull: true },
        { name: 'lastUpdateFlag', type: 'int', useNull: true },

    ]
});
//{/block}