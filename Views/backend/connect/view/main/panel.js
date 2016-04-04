//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/main/panel"}
Ext.define('Shopware.apps.Connect.view.main.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-panel',

    border: false,
    layout: 'card',

    initComponent: function() {
        var me = this,
            item;

        switch (me.action){
            case 'Register':
                //todo put register element here
                break;
            case 'Import':
                item = [{
                    xtype: 'connect-import',
                    itemId: 'import'
                }];
                break;
            case 'Export':
                item = [{
                    xtype: 'connect-export',
                    itemId: 'export'
                }];
                break;
            default:
                Ext.Error.raise('There is no view for this action');
        }

        Ext.applyIf(me, {
            items: item
        });

        me.callParent(arguments);
    }
});
//{/block}