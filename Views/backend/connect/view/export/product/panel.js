//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/product/panel"}
Ext.define('Shopware.apps.Connect.view.export.product.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.connect-export',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-export-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'connect-export-list',
                region: 'center'
            }],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    items: me.getFormButtons()
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
        var items = ['->'];
        items.push({
            text:'{s name=export/options/delete}Löschen{/s}',
            action:'delete'
        });
        items.push({
            cls: 'primary',
            text:'{s name=export/options/Export}Export{/s}',
            action:'add'
        });

        return items;
    }
});
//{/block}