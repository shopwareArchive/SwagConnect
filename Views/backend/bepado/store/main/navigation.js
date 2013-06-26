//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/store/main/navigation"}
Ext.define('Shopware.apps.Bepado.store.main.Navigation', {
    extend: 'Ext.data.TreeStore',

    autoLoad: false,

    constructor: function(config) {
        var me = this;
        me.root = {
            expanded: true,
                children: [
                { id: 'config', text: "{s name=navigation/config}Configuration{/s}", leaf: true },
                { id: 'mapping', text: "{s name=navigation/mapping}Category mapping{/s}", leaf: true },
                { id: 'export', text: "{s name=navigation/export}Product export{/s}", leaf: true },
                { id: 'import', text: "{s name=navigation/import}Product import{/s}", leaf: true }
            ]
        };
        me.callParent([config]);
    }
});
//{/block}
