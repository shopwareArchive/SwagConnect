//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/config/window"}
Ext.define('Shopware.apps.Connect.view.export.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.connect-export-window',
    cls: Ext.baseCSSPrefix + 'connect',

    layout: 'border',
    width: 1000,
    height: '95%',
    title: Ext.String.format('{s name=window/title}[0]{/s}', marketplaceName),
    titleTemplate: Ext.String.format('{s name=window/title_template}[0] - [text]{/s}', marketplaceName),
    snippets: {
        statusCount:  '{s name=export/message/status_count}Sync-Status: [0] from [1] products{/s}'
    },

    /**
     *
     */
    initComponent: function () {
        var me = this;

        me.items = me.getItems();
        me.checkPricing();

        me.callParent(arguments);
    },

    /**
     * Creates the fields sets and the sidebar for the detail page.
     * @return Array
     */
    getItems: function () {
        var me = this;

        return [Ext.create('Shopware.apps.Connect.view.export.TabPanel', {
            region: 'center',
            action: me.action
        })];
    },

    checkPricing: function () {
        var me = this;

        Ext.Ajax.request({
            scope: me,
            url: '{url controller=ConnectConfig action=isPricingMappingAllowed}',
            success: function (result, request) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success === false) {
                    me.body.insertHtml("beforeEnd", me.getHtmlMask());
                    me.fireEvent('showPriceWindow');
                } else if (response.isPricingMappingAllowed == true) {
                    me.body.insertHtml("beforeEnd", me.getHtmlMask());
                    me.fireEvent('showPriceWindow');
                }
            },
            failure: function () {
                me.body.insertHtml("beforeEnd", me.getHtmlMask());
            }
        });
    },

    getHtmlMask: function () {
        return '<div class="export-window-wrapper">' +
            '<div class="export-window-message-wrapper">' +
            '</div>' +
            '<div class="export-window-mask sc-transparency"></div>' +
            '</div>';
    }
});
//{/block}