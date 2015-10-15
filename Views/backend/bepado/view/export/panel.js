//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/export/panel"}
Ext.define('Shopware.apps.Bepado.view.export.Panel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-export',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'bepado-export-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'bepado-export-list',
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