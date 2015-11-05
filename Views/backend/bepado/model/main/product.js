//{block name="backend/connect/model/main/product"}
Ext.define('Shopware.apps.Connect.model.main.Product', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/connect/model/main/product/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'name',  type: 'string' },
        { name: 'number',  type: 'string' },
        { name: 'supplier',  type: 'string' },
        { name: 'inStock',  type: 'int' },
        { name: 'category',  type: 'string' },
        { name: 'active', type: 'boolean' },
        { name: 'price', type: 'float', useNull: true },
        { name: 'tax', type: 'float', useNull: true }
    ]
});
//{/block}