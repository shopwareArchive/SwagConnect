//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/panel"}
Ext.define('Shopware.apps.Bepado.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import',

    border: false,
    layout: 'vbox',
    padding: '10px',
    width: '100%',

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
                            html: '<h1 style="color: #486783; font-size: large">Shopware Connect Produkte</h1>',
                            width: 400,
                            height: 30
                        },
                        {
                            xtype: 'container',
                            html: '<h1 style="font-size: large">Meine Produkte</h1>',
                            margin: '0 0 0 60px',
                            width: 400
                        }
                    ]
                },
                {
                    xtype: 'container',
                    layout: 'hbox',
                    width: '100%',
                    items: [
                        {
                            xtype: 'connect-remote-categories'
                        },
                        {
                            xtype: 'container',
                            html: '<div class="import-arrow">&nbsp;</div>',
                            border: false,
                            padding: '2px',
                            margin: '125px 0 0 0',
                            width: 50,
                            height: 50
                        },
                        {
                            xtype: 'connect-own-categories'
                        }
                    ]
                }, {
                    xtype: 'container',
                    layout: 'hbox',
                    items: [
                        Ext.create('Shopware.apps.Bepado.view.import.RemoteProducts', {
                            width: 400,
                            height: 300,
                            margin: '10px 0 0 0'
                        }),
                        Ext.create('Shopware.apps.Bepado.view.import.LocalProducts', {
                            width: 400,
                            height: 300,
                            margin: '10px 0 0 50px'
                        })
                    ]
                }
        ]
        });

        me.callParent(arguments);
    }
});
//{/block}