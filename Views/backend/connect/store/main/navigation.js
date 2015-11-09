//{namespace name=backend/connect/view/main}

//{block name="backend/connect/store/main/navigation"}
Ext.define('Shopware.apps.Connect.store.main.Navigation', {
    extend: 'Ext.data.TreeStore',

    autoLoad: false,

    root: {
        expanded: true,
        children: [
            { id: 'home', text: "{s name=navigation/home_page}Home page{/s}", leaf: true, iconCls: 'connect-icon' },
            {
                id: 'config', text: "{s name=navigation/settings/settings}Einstellungen{/s}",
                leaf: false,
                expanded: true,
                children: [
                    {
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
                    }
                ]
            }
        ]
    },

    listeners: {
        beforeappend: {
            element: this,
            fn: function (node, child) {
                var semMenuItems = ['marketplace-attributes', 'mapping-import', 'mapping-export'];
                // hide menu items which depend on marketplace
                if (defaultMarketplace == true && child) {
                    return semMenuItems.indexOf(child.data.id) === -1;
                }
            return true;
            }
        }
    },

    constructor: function (config) {
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}
