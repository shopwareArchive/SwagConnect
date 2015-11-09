//{block name="backend/connect/model/log/list"}
Ext.define('Shopware.apps.Connect.model.log.List', {
    extend:'Ext.data.Model',

    fields: [
        //{block name="backend/connect/model/log/list/fields"}{/block}
        { name: 'id', type: 'integer' },
        { name: 'isError', type: 'boolean' },
        { name: 'request', type: 'string', useNull: true },
        { name: 'response', type: 'string', useNull: true },
        { name: 'command', type: 'string', useNull: true },
        { name: 'service', type: 'string', useNull: true },
        { name: 'time', type: 'datetime', useNull: true }
    ]
});
//{/block}