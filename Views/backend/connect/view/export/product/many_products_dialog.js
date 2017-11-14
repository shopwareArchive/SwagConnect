//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/product/many_products_dialog"}
Ext.define('Shopware.apps.Connect.view.export.product.manyProductsDialog', {

    extend: 'Enlight.app.Window',

    alias: 'widget.connect-many-products-dialog',
    border: false,
    autoShow: true,
    layout: 'anchor',
    width: 550,
    height: 170,
    maximizable: false,
    minimizable: false,
    closable: false,
    footerButton: true,
    stateful: true,
    modal: true,
    inProcess: false,

    /**
     * Contains array with source ids which have to exported
     */
    sourceIds: [],

    /**
     * Contains the amount of products export all will export as batches
     */
    count: 0,

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        cancel: '{s name=export/info/cancel}Cancel{/s}',
        next: '{s name=export/info/next}Next{/s}',
        activateCronJob: '{s name=export/info/activate_cron_job}Activate CronJob{/s}',
        message: '{s name=export/info/message}You are exporting a bigger amount of products. If you want to use a CronJob for this, select "Activate CronJob". If you want to use the usual way, select "Next".{/s}'
    },

    bodyPadding: 10,

    initComponent: function () {
        var me = this;

        me.items = me.createItems();
        me.title = '';
        me.callParent(arguments);
    },

    /**
     * Creates the items for the progress window.
     */
    createItems: function () {
        var me = this,
        batchSize = 50;

        me.cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            anchor: '33%',
            cls: 'secondary',
            align: 'right',
            margin: '30 10 0 0',
            handler: function () {
                me.destroy();
            }
        });

        me.cronButton = Ext.create('Ext.button.Button', {
            text: me.snippets.activateCronJob,
            anchor: '33%',
            cls: 'secondary',
            align: 'right',
            margin: '30 10 0 0',
            handler: function () {
                me.fireEvent('cronExportAll', me);
                me.destroy();
            }
        });

        me.nextButton = Ext.create('Ext.button.Button', {
            text: me.snippets.next,
            anchor: '33%',
            cls: 'primary',
            align: 'right',
            margin: '30 -10 0 0',
            handler: function () {
                Ext.create('Shopware.apps.Connect.view.export.product.Progress', {
                    startButtonHandler: function (caller) {
                        caller.fireEvent('exportAll', caller.count, batchSize, caller, 0)
                    },
                    count: me.count,
                    totalTime: me.count / batchSize * 4 / 60
                }).show();
                me.destroy();
            }
        });

        var notice = Ext.create('Ext.container.Container', {
            html: me.snippets.message,
            style: 'color: #999; font-style: italic; margin: 0 0 15px 0; text-align: center;',
            anchor: '100%'
        });

        return [notice, me.cancelButton, me.cronButton, me.nextButton];
    }
});
//{/block}
