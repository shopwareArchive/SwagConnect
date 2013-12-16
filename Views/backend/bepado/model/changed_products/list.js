//{block name="backend/bepado/model/changed_products/list"}
Ext.define('Shopware.apps.Bepado.model.changed_products.List', {
    extend:'Shopware.apps.Bepado.model.import.List',

    fields: [
        //{block name="backend/bepado/model/changed_products/list/fields"}{/block}
        { name: 'bepadoLastUpdate', type: 'string', useNull: true },
        { name: 'bepadoLastUpdateFlag', type: 'int', useNull: true },

    ]
});
//{/block}