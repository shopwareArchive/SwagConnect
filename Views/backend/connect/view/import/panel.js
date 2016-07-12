//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/panel"}
Ext.define('Shopware.apps.Connect.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-import',

    border: false,
    layout: 'vbox',
    padding: '10px',
    width: '100%',
    autoScroll: true,

    snippets: {
        connectProductsTitle: '{s name=import/shopware_connect_products}Shopware Connect Produkte{/s}',
        showOnlyConnectProductsLabel: '{s name=import/show_only_connect_products}Nur shopware Connect Produkte anzeigen{/s}',
        hideMappedProducts: '{s name=import/hide_mapped_products}Zugewiesene Produkte und Kategorien ausblenden{/s}',
        myShopTitle: '{s name=import/my_shop}My Shop{/s}',
        myProductsTitle: '{s name=import/my_products}Meine Produkte{/s}'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'container',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    width: '100%',
                    items: [
                        {
                            xtype: 'container',
                            layout: 'vbox',
                            width: '50%',
                            flex: 1,
                            items:[
                                {
                                    xtype: 'container',
                                    html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.connectProductsTitle  + '</h1>',
                                    height: 30
                                }, {
                                    xtype: 'connect-remote-categories',
                                    border: 1,
                                    style: {
                                        borderColor: '#a4b5c0'
                                    },
                                    width: '100%'
                                }, Ext.create('Shopware.apps.Connect.view.import.RemoteProducts', {
                                    height: 300,
                                    width: '100%',
                                    margin: '10px 0 0 0'
                                })
                            ]
                        },
                        {
                            xtype: 'container',
                            layout: 'vbox',
                            width: '50%',
                            flex: 1,
                            items:[
                                {
                                    xtype: 'container',
                                    html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.myShopTitle  + '</h1>',
                                    height: 30
                                }, {
                                    xtype: 'connect-own-categories',
                                    width: '100%'
                                },
                                {
                                    xtype: 'panel',
                                    width: '100%',
                                    bodyStyle : 'background: none; border-style: none;',
                                    items: [
                                        Ext.create('Shopware.apps.Connect.view.import.LocalProducts', {
                                            flex: 1,
                                            width: '100%',
                                            height: 300,
                                            margin: '10px 0 0 0'
                                        })
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }
});
//{/block}