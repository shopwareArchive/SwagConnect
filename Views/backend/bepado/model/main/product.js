//{block name="backend/bepado/model/main/list"}
Ext.define('Shopware.apps.Bepado.model.main.List', {
    extend: 'Ext.data.Model',

    proxy: {
        type: 'ajax',
        api: {
            read : '{url action=getList}',
            destroy: '{url action=deleteListItem}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    },

    fields: [
        //{block name="backend/bepado/model/main/list/fields"}{/block}
        { name: 'id', type: 'int', useNull: true },
        { name: 'groupId', type: 'int', defaultValue: null, useNull: true },
        { name: 'optionId', type: 'int', defaultValue: null, useNull: true },
        { name: 'name' },
        { name: 'position', type: 'int' },
        { name: 'active', type: 'boolean' },
        { name: 'assignment', type: 'int', defaultValue: null, useNull: true },
        { name: 'leaf', convert: function(v, record) { return !!record.data.optionId; } }
    ]
});
//{/block}