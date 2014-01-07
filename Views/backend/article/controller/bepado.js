//{namespace name="backend/bepado/view/main"}
//{block name="backend/article/controller/bepado"}
Ext.define('Shopware.apps.Article.controller.Bepado', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    shouldCancel: false,

    refs: [
          { ref: 'bepadoForm', selector: 'article-bepado-form' },
      ],


    /**
     * A template method that is called when your application boots.
     * It is called before the Application's launch function is executed
     * so gives a hook point to run any code before your Viewport is created.
     *
     * @return Ext.window.Window
     */
    init: function () {
        var me = this,
            mainWindow = me.subApplication.articleWindow;

        me.control({
            'article-detail-window': {
                bepadoTabActivated: me.onBepadoStoreReloadNeeded,
                bepadoStoreReloadNeeded: me.onBepadoStoreReloadNeeded,
                saveArticle: me.onSaveArticle
            }
        });

        mainWindow.on('storesLoaded', me.onStoresLoaded, me);

        me.callParent(arguments);
    },


    onStoresLoaded: function() {
        var me = this;
        
        console.log(234);
    },

    onSaveArticle: function(win, article, options) {
        var me = this,
            bepadoForm = me.getBepadoForm();

        bepadoForm.getForm().updateRecord(me.record);


        me.record.save({
                failure: function(record, operation) {
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;

                    Shopware.Notification.createGrowlMessage('{s name=error}error{/s}', message, 'bepado');
                }
        });
    },

    onBepadoStoreReloadNeeded: function() {
        var me = this;

        me.doReloadBepadoStore();
    },

    doReloadBepadoStore: function() {
        var me = this,
            bepadoForm = me.getBepadoForm();

        me.bepadoStore.load({
            callback: function(records, operation) {
                if (!operation.wasSuccessful()) {
                    return;
                }
                if (records.length > 0) {
                    me.record = records[0];
                    bepadoForm.loadRecord(me.record);
                }
            }
        });
    }
});
//{/block}
