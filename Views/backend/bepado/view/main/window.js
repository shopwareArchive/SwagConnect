//{namespace name=backend/bepado/view/main}

/**
 * todo@all: Documentation
 */
//{block name="backend/bepado/view/main/window"}
Ext.define('Shopware.apps.Bepado.view.main.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.bepado-window',
    cls: Ext.baseCSSPrefix + 'bepado',

    layout: 'border',
    width: 1100,
    height:'90%',

    title: '{s name=window/title}Bepado{/s}',

    titleOption: '{s name=window/title_option}Bepado - Option: [name]{/s}',
    titleGroup: '{s name=window/title_group}Bepado - Group: [name]{/s}',
    titleNewOption: '{s name=window/title_new_option}Bepado - New option{/s}',
    titleNewGroup: '{s name=window/title_new_group}Bepado - New group{/s}',
    titleCharge: '{s name=window/title_charge}Bepado - Charge: [name]{/s}',
    titleNewCharge: '{s name=window/title_new_charge}Bepado - New charge{/s}',

    /**
     *
     */
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: me.getItems()
        });

        me.callParent(arguments);
    },

    loadTitle: function(model, record) {
        var me = this, title, data ;
        if(!record) {
            title = '{s name=window/title}Bepado{/s}';
        } else if(model == 'charge.Item') {
            title = record.get('id') ? me.titleCharge : me.titleNewCharge;
            data = record.data;
        } else if(record.get('id')) {
            title = model == 'main.Group' ? me.titleGroup : me.titleOption;
            data = record.data;
        } else {
            title = model == 'main.Group' ? me.titleNewGroup : me.titleNewOption;
            data = {};
        }
        title = new Ext.Template(title).applyTemplate(data);
        me.setTitle(title);
    },

    /**
     * Creates the fields sets and the sidebar for the detail page.
     * @return Array
     */
    getItems: function() {
        var me = this;
        return [{
            xtype: 'bepado-navigation',
            region: 'west'
        }, {
            xtype: 'bepado-config',
            region: 'center'
        }]
    }
});
//{/block}