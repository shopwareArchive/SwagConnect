//{block name="backend/connect/model/export/stream_list"}
Ext.define('Shopware.apps.Connect.model.export.StreamList', {
    extend:'Ext.data.Model',

    fields: [
        //{block name="backend/connect/model/export/stream_list/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'name',  type: 'string' },
        { name: 'type',  type: 'int' },
        { name: 'exportStatus', type: 'string', useNull: true },
        { name: 'exportMessage', type: 'string', useNull: true }
    ]
});
//{/block}