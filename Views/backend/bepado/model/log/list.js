//{block name="backend/bepado/model/log/list"}
Ext.define('Shopware.apps.Bepado.model.log.List', {
    extend:'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/log/list/fields"}{/block}
        { name: 'id', type: 'integer' },
        { name: 'isException', type: 'boolean' },
        { name: 'request', type: 'string', useNull: true },
        { name: 'response', type: 'string', useNull: true },
        { name: 'command', type: 'string', useNull: true },
        { name: 'service', type: 'string', useNull: true },
        { name: 'time', type: 'datetime', useNull: true }
    ]
});
//{/block}