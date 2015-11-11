//{block name="backend/connect/store/mapping/import"}
Ext.define('Shopware.apps.Connect.store.mapping.Import', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    model: 'Shopware.apps.Connect.model.main.Mapping',
    root: {
        id: 1,
        expanded: true
    },
    proxy : {
        type : 'ajax',
        api : {
            read : '{url action=getImportMappingList}',
            update: '{url action=setImportMappingList targetField=rows}'
        },
        reader : {
            type : 'json',
            root: 'data'
        }
    },
    constructor: function(config) {
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}
