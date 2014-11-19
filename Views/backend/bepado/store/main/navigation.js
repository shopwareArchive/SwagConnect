//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/store/main/navigation"}
Ext.define('Shopware.apps.Bepado.store.main.Navigation', {
    extend: 'Ext.data.TreeStore',

    autoLoad: false,

    root: {
        expanded: true,
        children: [
            { id: 'home', text: "{s name=navigation/home_page}Home page{/s}", leaf: true, iconCls: 'bepado-icon' },
            {
                id: 'settings-main', text: "{s name=navigation/settings}Settings{/s}",
                leaf: false,
                expanded: true,
                children: [
                    {
                        id: 'config',
                        text: "{s name=navigation/config}Configuration{/s}",
                        leaf: true,
                        iconCls: 'sprite-wrench-screwdriver'
                    },
                    {
                        id: 'config-units',
                        text: "{s name=navigation/config_units}Units{/s}",
                        leaf: true,
                        iconCls: 'sprite-inbox-upload'
                    },
                    {
                        id: 'log',
                        text: "{s name=navigation/log}Log{/s}",
                        leaf: true,
                        iconCls: 'sprite-database'
                    }
                ]
            },
            {
                id: 'import-main', text: "{s name=navigation/config_import}Import{/s}",
                leaf: false,
                expanded: true,
                children: [
                    {
                        id: 'config-import',
                        text: "{s name=navigation/config}Configuration{/s}",
                        leaf: true,
                        iconCls: 'sprite-wrench-screwdriver'
                    },
                    {
                        id: 'mapping-import',
                        text: "{s name=navigation/mapping}Category mapping{/s}",
                        leaf: true,
                        iconCls: 'sprite-sticky-notes-pin'
                    },
                    {
                        id: 'import',
                        text: "{s name=navigation/products}Products{/s}",
                        leaf: true,
                        iconCls: 'sprite-drive-download'
                    },
                    {
                        id: 'changed',
                        text: "{s name=navigation/changed}Changed{/s}",
                        leaf: true,
                        iconCls: 'sprite-clock'
                    }
                ]
            },
            {
                id: 'export-main', text: "{s name=navigation/config_export}Export{/s}", leaf: false,
                expanded: true,
                children: [
                    {
                        id: 'config-export',
                        text: "{s name=navigation/config}Configuration{/s}",
                        leaf: true,
                        iconCls: 'sprite-wrench-screwdriver'
                    },
                    {
                        id: 'mapping-export',
                        text: "{s name=navigation/mapping}Category mapping{/s}",
                        leaf: true,
                        iconCls: 'sprite-sticky-notes-pin'
                    },
                    {
                        id: 'export',
                        text: "{s name=navigation/products}Products{/s}",
                        leaf: true,
                        iconCls: 'sprite-inbox-upload'
                    },
                    {
                        id: 'config-shipping-groups',
                        text: "{s name=navigation/config_shipping_groups}Shipping groups{/s}",
                        leaf: true,
                        iconCls: 'sprite-truck'
                    }
                ]
            }
        ]
    },

    constructor: function (config) {
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}
