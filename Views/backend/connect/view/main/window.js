//{namespace name=backend/connect/view/main}

/**
 * todo@all: Documentation
 */
//{block name="backend/connect/view/main/window"}
Ext.define('Shopware.apps.Connect.view.main.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.connect-window',
    cls: Ext.baseCSSPrefix + 'connect',

    layout: 'border',
    width: 1000,
    height: '95%',
    title: Ext.String.format('{s name=window/title}[0]{/s}', marketplaceName),

    titleTemplate: Ext.String.format('{s name=window/title_template}[0] - [text]{/s}', marketplaceName),

    /**
     *
     */
    initComponent: function() {
        var me = this;

        switch (me.action) {
            case 'Register':
                me.setSize(810, 630);
                me.maximizable = false;
                me.minimizable = false;
                me.resizable = false;
                break;
        }

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
            title = Ext.String.format('{s name=window/title}[0]{/s}', marketplaceName);
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

        switch (me.action){
            case 'Settings':
                return [
                    Ext.create('Shopware.apps.Connect.view.main.TabPanel', {
                        region: 'center',
                        action : me.action
                    })
                ];
            case 'Register':
                return [ Ext.create('Shopware.apps.Connect.view.register.panel', {
                    region: 'center',
                    action : me.action,
                    width: 200
                })];
            case 'Export':
                return [ Ext.create('Shopware.apps.Connect.view.export.TabPanel', {
                    region: 'center',
                    action : me.action
                })];
            default:
                return [
                    Ext.create('Shopware.apps.Connect.view.main.Panel', {
                        region: 'center',
                        action : me.action
                    })
                ];
        }
    }
});
//{/block}