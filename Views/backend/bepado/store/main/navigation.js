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
                id: 'config', text: "{s name=navigation/settings/settings}Einstellungen{/s}",
                leaf: false,
                expanded: true,
                children: [
                    {
                        // it will be removed in the constructor
                        // if shop uses default marketplace
                        id: 'marketplace-attributes',
                        text: "{s name=navigation/marketplace_attribute}Marketplace attributes{/s}",
                        leaf: true,
                        iconCls: 'sprite-ui-scroll-pane-detail'
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
                id: 'config-import', text: "{s name=navigation/config_import}Import{/s}",
                leaf: false,
                expanded: true,
                children: [
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
                id: 'config-export', text: "{s name=navigation/config_export}Export{/s}", leaf: false,
                expanded: true,
                children: [
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
        if (defaultMarketplace == true) {
            // remove marketplace attributes menu item
            this.root.children[1].children.splice(0, 1);
        }
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}
