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
        connectProductsTitle: '{s name=import/connect_products}Products{/s}',
        showOnlyConnectProductsLabel: '{s name=import/show_only_connect_products}Nur shopware Connect Produkte anzeigen{/s}',
        hideMappedProducts: '{s name=import/hide_mapped_products}Zugewiesene Produkte und Kategorien ausblenden{/s}',
        myShopTitle: '{s name=import/your_shop}Your Shop{/s}',
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
                            xtype: 'fieldset',
                            layout: 'vbox',
                            width: '50%',
                            style: 'margin-right: 30px',
                            flex: 1,
                            title: '<div class="connect-icon fieldset-label-icon">' + me.snippets.connectProductsTitle + '</div>',
                            items:[
                                {
                                    xtype: 'connect-remote-categories',
                                    border: 1,
                                    style: {
                                        borderColor: '#a4b5c0',
                                        background: '#fff'
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
                            xtype: 'fieldset',
                            layout: 'vbox',
                            width: '50%',
                            flex: 1,
                            title: me.snippets.myShopTitle,
                            items:[
                                {
                                    xtype: 'connect-own-categories',
                                    style: 'background: #fff',
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