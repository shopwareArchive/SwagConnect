//{block name="backend/bepado/store/main/mapping"}
Ext.define('Shopware.apps.Bepado.store.main.Mapping', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    model: 'Shopware.apps.Bepado.model.main.Mapping',
    root: {
        id: 1,
        expanded: true
    },
    proxy : {
        type : 'ajax',
        api : {
            read : '{url action=getMappingList}',
            update: '{url action=setMappingList targetField=rows}'
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
