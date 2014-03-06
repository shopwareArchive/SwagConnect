//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/store/main/navigation"}
Ext.define('Shopware.apps.Bepado.store.main.Navigation', {
    extend: 'Ext.data.TreeStore',

    autoLoad: false,

    root: {
        expanded: true,
            children: [
            { id: 'home', text: "{s name=navigation/home_page}Home page{/s}", leaf: true },
            { id: 'config', text: "{s name=navigation/config}Configuration{/s}", leaf: false,
                expanded: true,
                children: [
                    { id: 'config-import', text: "{s name=navigation/config_import}Import{/s}", leaf: true },
                    { id: 'config-export', text: "{s name=navigation/config_export}Export{/s}", leaf: true }
            ]},
            { id: 'mapping', text: "{s name=navigation/mapping}Category mapping{/s}", leaf: false,
                expanded: true,
                children: [
                    { id: 'mapping-import', text: "{s name=navigation/mapping/import}Import{/s}", leaf: true },
                    { id: 'mapping-export', text: "{s name=navigation/mapping/export}export{/s}", leaf: true },
                ]
            },
            { id: 'products', text: "{s name=navigation/products}Products{/s}", leaf: false,
                expanded: true,
                children: [
                    { id: 'import', text: "{s name=navigation/import}Import{/s}", leaf: true },
                    { id: 'export', text: "{s name=navigation/export}Export{/s}", leaf: true },
                    { id: 'changed', text: "{s name=navigation/changed}Changed{/s}", leaf: true },
                ]
            },
            { id: 'log', text: "{s name=navigation/log}Log{/s}", leaf: true }
        ]
    },

    constructor: function (config) {
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}
