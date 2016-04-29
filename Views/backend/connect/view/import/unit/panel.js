//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/unit/panel"}
Ext.define('Shopware.apps.Connect.view.import.unit.Panel', {
    extend: 'Ext.form.Panel',
    alias: 'widget.connect-import-unit',

    border: false,
    layout: 'border',
    bodyPadding: 10,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 170,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=import/unit/save}Save{/s}',
        productImportUnitsTitle: '{s name=import/unit/units_title}Units{/s}',
        hideAssignedUnitsLabel: '{s name=import/unit/hide_assigned_units}hide assigned units{/s}',
        takeOverUnits: '{s name=import/unit/take_over_units}Take over units{/s}',
        takeOverUnitsTooltip: '{s name=import/unit/take_over_units_tooltip}Apply unassigned units of measurement in the shop{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = me.createElements();

        me.callParent(arguments);
    },

    /**
     * Creates the field set items
     * @return Array
     */
    createElements: function () {
        var me = this;

        return [{
            xtype: 'panel',
            padding: '0 20 0 0',
            flex: 1,
            layout: 'vbox',
            border: false,
            bodyStyle : 'background: none; border-style: none;',
            items: [
                {
                    xtype: 'container',
                    html: '<h1 class="shopware-connect-color" style="font-size: large">' + me.snippets.productImportUnitsTitle  + '</h1>',
                    width: 400,
                    height: 30
                },
                Ext.create('Shopware.apps.Connect.view.config.import.UnitsMapping'),
                {
                    xtype: 'checkbox',
                    name: 'hideAssignedUnits',
                    boxLabel: me.snippets.hideAssignedUnitsLabel,
                    labelWidth: me.defaults.labelWidth
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    style: {
                        background: 'none'
                    },
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    items: me.getFormButtons()
                }
            ]
        }];
    },

    /**
     * Returns form buttons, export and remove
     * @returns Array
     */
    getFormButtons: function () {
        var me = this;
        var items = ['->'];
        items.push({
            text: me.snippets.takeOverUnits,
            tooltip: me.snippets.takeOverUnitsTooltip,
            action:'adoptUnits'
        });
        items.push({
            cls: 'primary',
            text: me.snippets.save,
            action:'save-unit'
        });

        return items;
    }
});
//{/block}