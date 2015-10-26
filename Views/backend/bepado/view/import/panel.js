//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/panel"}
Ext.define('Shopware.apps.Bepado.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import',

    border: false,
    layout: 'fit',
    padding: '10px',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                    xtype: 'panel',
                    layout: 'vbox',
                    items: [{
                        xtype: 'panel',
                        layout: 'hbox',
                        items: [
                            {
                                xtype: 'connect-remote-categories',
                                padding: '10px'
                            } , {
                                xtype: 'connect-own-categories',
                                padding: '10px'
                            }
                        ]
                    }, {
                        xtype: 'panel',
                        layout: 'hbox',
                        items: [
                            Ext.create('Shopware.apps.Bepado.view.import.RemoteProducts'),
                            Ext.create('Shopware.apps.Bepado.view.import.LocalProducts')
                        ]
                    }]
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}