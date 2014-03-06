//{block name="backend/bepado/model/main/product"}
Ext.define('Shopware.apps.Bepado.model.main.Product', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/main/product/fields"}{/block}
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