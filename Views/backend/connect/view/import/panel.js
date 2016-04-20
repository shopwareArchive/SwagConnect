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
        connectProductsTitle: '{s name=import/shopware_connect_products}shopware Connect Produkte{/s}',
        showOnlyConnectProductsLabel: '{s name=import/show_only_connect_products}Nur shopware Connect Produkte anzeigen{/s}',
        hideMappedProducts: '{s name=import/hide_mapped_products}zugewiesene Produkte und Kategorien ausblenden{/s}',
        activateProductsLabel: '{s name=import/activate_products}Produkte aktivieren{/s}',
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
                                }),
                                {
                                    xtype : 'checkbox',
                                    name : 'attribute[hideMapped]',
                                    action: 'hide-mapped-products',
                                    margin: '15px 0 0 0',
                                    checked: true,
                                    boxLabel : me.snippets.hideMappedProducts
                                }
                            ]
                        },
                        {
                            xtype: 'panel',
                            layout: 'vbox',
                            flex: 0,
                            width: '50px',
                            margin: '100px 0 0 0',
                            bodyStyle : 'background:none', // Removes the default white background
                            border: false,
                            items: [
                                {
                                    xtype: 'button',
                                    alias: 'widget.arrow-import-categories',
                                    cls: 'import-arrow',
                                    action: 'importRemoteCategory',
                                    border: false,
                                    padding: '2px',
                                    width: 50,
                                    height: 50
                                },
                                {
                                    xtype: 'button',
                                    alias: 'widget.arrow-unassign-categories',
                                    cls: 'unassign-arrow',
                                    action: 'unassignRemoteCategory',
                                    border: false,
                                    padding: '2px',
                                    width: 50,
                                    height: 50
                                }
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
                                    html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.connectProductsTitle  + '</h1>',
                                    height: 30
                                }, {
                                    xtype: 'connect-own-categories',
                                    width: '100%'
                                }, {
                                    xtype: 'container',
                                    width: '100%',
                                    items: [
                                        Ext.create('Shopware.apps.Connect.view.import.LocalProducts', {
                                            flex: 1,
                                            width: '100%',
                                            height: 300,
                                            margin: '10px 0 0 0'
                                        }),
                                        {
                                            xtype : 'checkbox',
                                            name : 'attribute[connectAllowed]',
                                            action: 'show-only-connect-products',
                                            margin: '15px 0 0 0',
                                            checked: true,
                                            boxLabel : me.snippets.showOnlyConnectProductsLabel
                                        },{
                                            xtype: 'button',
                                            cls: 'primary sc-btn-right',
                                            action: 'activateProducts',
                                            text: me.snippets.activateProductsLabel
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ]
        });


        //Ext.applyIf(me, {
        //    items: [
        //        {
        //            xtype: 'container',
        //            layout: 'hbox',
        //            width: '100%',
        //            items: [
        //                {
        //                    xtype: 'container',
        //                    html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.connectProductsTitle  + '</h1>',
        //                    width: '45%',
        //                    height: 30
        //                },
        //                {
        //                    xtype: 'container',
        //                    html: '<h1 style="font-size: large">' + me.snippets.myProductsTitle + '</h1>',
        //                    margin: '0 0 0 110px',
        //                    width: '55%'
        //                }
        //            ]
        //        },
        //        {
        //            xtype: 'container',
        //            layout: 'hbox',
        //            width: '100%',
        //            items: [
        //                {
        //                    xtype: 'connect-remote-categories',
        //                    width: '45%'
        //                },
        //                {
        //                    xtype: 'panel',
        //                    layout: 'vbox',
        //                    width: 50,
        //                    height: 100,
        //                    margin: '100px 0 0 0',
        //                    bodyStyle : 'background:none', // Removes the default white background
        //                    border: false,
        //                    items: [
        //                        {
        //                            xtype: 'button',
        //                            alias: 'widget.arrow-import-categories',
        //                            cls: 'import-arrow',
        //                            action: 'importRemoteCategory',
        //                            border: false,
        //                            padding: '2px',
        //                            width: 50,
        //                            height: 50
        //                        },
        //                        {
        //                            xtype: 'button',
        //                            alias: 'widget.arrow-unassign-categories',
        //                            cls: 'unassign-arrow',
        //                            action: 'unassignRemoteCategory',
        //                            border: false,
        //                            padding: '2px',
        //                            width: 50,
        //                            height: 50
        //                        }
        //                    ]
        //                },
        //                {
        //                    xtype: 'connect-own-categories',
        //                    width: '55%'
        //                }
        //            ]
        //        }, {
        //            xtype: 'container',
        //            layout: 'hbox',
        //            width: '100%',
        //            items: [
        //                Ext.create('Shopware.apps.Connect.view.import.RemoteProducts', {
        //                    width: '45%',
        //                    height: 300,
        //                    margin: '10px 0 0 0'
        //                }),
        //
        //
        //            ]
        //        }
        //    ]
        //});

        me.callParent(arguments);
    }
});
//{/block}