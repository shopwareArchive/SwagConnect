//{block name="backend/bepado/model/export/list"}
Ext.define('Shopware.apps.Bepado.model.export.List', {
    extend:'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/export/list/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'name',  type: 'string' },
        { name: 'number',  type: 'string' },
        { name: 'supplier',  type: 'string' },
        { name: 'inStock',  type: 'int' },
        { name: 'active', type: 'boolean' },
        { name: 'price', type: 'float', useNull: true },
        { name: 'tax', type: 'float', useNull: true },
        { name: 'status', type: 'string' }
    ]
});
//{/block}