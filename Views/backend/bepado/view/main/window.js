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

    title: '{s name=window/title}bepado{/s}',

    titleTemplate: '{s name=window/title_template}bepado - [text]{/s}',

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

    /**
     *
     * @param record
     */
    loadTitle: function(record) {
        var me = this, title, data = {};
        if(!record) {
            title = '{s name=window/title}bepado{/s}';
        } else {
            title = me.titleTemplate;
            data = record.data;
            title = new Ext.Template(title).applyTemplate(data);
        }
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
            xtype: 'bepado-panel',
            region: 'center'
        }];
    }
});
//{/block}