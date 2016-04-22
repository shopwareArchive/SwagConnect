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
        removeProducts: '{s name=import/remove_products}Remove Products{/s}',
        myProductsTitle: '{s name=import/my_products}Meine Produkte{/s}'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: '100%',
                    items: [
                        {
                            xtype: 'container',
                            html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.connectProductsTitle  + '</h1>',
                            width: '47%',
                            height: 30
                        },
                        {
                            xtype: 'container',
                            html: '<h1 style="font-size: large">' + me.snippets.myProductsTitle + '</h1>',
                            margin: '0 0 0 110px',
                            width: '47%'
                        }
                    ]
                },
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: '100%',
                    items: [
                        {
                            xtype: 'connect-remote-categories',
                            width: '47%'
                        },
                        {
                            xtype: 'panel',
                            layout: 'vbox',
                            width: 50,
                            height: 50,
                            margin: '125px 0 0 0',
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
                                }
                            ]
                        },
                        {
                            xtype: 'connect-own-categories',
                            width: '47%'
                        }
                    ]
                }, {
                    xtype: 'container',
                    layout: 'hbox',
                    width: '100%',
                    items: [
                        {
                            xtype: 'container',
                            width: '47%',
                            items: [
                                Ext.create('Shopware.apps.Connect.view.import.RemoteProducts', {
                                    width: '100%',
                                    height: 300,
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
                            width: 50,
                            height: 50,
                            margin: '125px 0 0 0',
                            bodyStyle : 'background: none', // Removes the default white background
                            border: false,
                            items: [
                                {
                                    xtype: 'button',
                                    alias: 'widget.arrow-unassign-categories',
                                    cls: 'import-arrow',
                                    action: 'assignArticlesToCategory',
                                    border: false,
                                    padding: '2px',
                                    width: 50,
                                    height: 50
                                }
                            ]
                        },
                        {
                            xtype: 'panel',
                            width: '47%',
                            bodyStyle : 'background: none; border-style: none;', // Removes the default white background
                            items: [
                                Ext.create('Shopware.apps.Connect.view.import.LocalProducts', {
                                    width: '100%',
                                    height: 300,
                                    margin: '10px 0 0 0'
                                }),
                                {
                                    xtype : 'checkbox',
                                    name : 'attribute[connectAllowed]',
                                    action: 'show-only-connect-products',
                                    margin: '15px 0 0 0px',
                                    checked: true,
                                    boxLabel : me.snippets.showOnlyConnectProductsLabel
                                }
                            ],
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    style:{
                                        background: 'none'
                                    },
                                    dock: 'bottom',
                                    ui: 'shopware-ui',
                                    cls: 'shopware-toolbar',
                                    items: me.getFormButtons()
                                }
                            ]
                        }

                    ]
                }
            ]
        });

        me.callParent(arguments);
    },

    /**
     * Returns form buttons, export and remove
     * @returns Array
     */
    getFormButtons: function () {
        var me = this,
            items = ['->'];

        items.push({
            text: me.snippets.removeProducts,
            action:'unAssignArticlesFromCategory'
        });
        items.push({
            cls: 'primary',
            text: me.snippets.activateProductsLabel,
            action:'activateProducts'
        });

        return items;
    }
});
//{/block}