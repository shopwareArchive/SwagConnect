//{namespace name="backend/connect/view/main"}
//{block name="backend/article/controller/connect"}
Ext.define('Shopware.apps.Article.controller.Connect', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    refs: [
          { ref: 'connectForm', selector: 'article-connect-form' },
          { ref: 'articleDetailWindow', selector: 'article-detail-window' },
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
                connectTabActivated: me.onConnectStoreReloadNeeded,
                connectStoreReloadNeeded: me.onConnectStoreReloadNeeded,
                saveArticle: me.onSaveArticle
            }
        });

        mainWindow.on('storesLoaded', me.onStoresLoaded, me);

        me.callParent(arguments);
    },

    enableTab: function() {
        var me = this,
            detailWindow = me.getArticleDetailWindow();

        detailWindow.connectTab.setDisabled(false);
    },

    onStoresLoaded: function() {
        var me = this;
        

    },

    onSaveArticle: function(win, article, options) {
        var me = this,
            connectForm = me.getConnectForm();

        if (!me.subApplication.article || !me.subApplication.article.get('id')) {
            return;
        }

        connectForm.getForm().updateRecord(me.record);


        me.record.save({
                failure: function(record, operation) {
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;

                    Shopware.Notification.createGrowlMessage('{s name=connect/error}error{/s}', message, 'connect');
                }
        });
    },

    /**
     * Callback function for the "connectStoreReloadNeeded" event
     */
    onConnectStoreReloadNeeded: function() {
        var me = this;

        me.doReloadConnectStore();
    },

    /**
     * Actually do reload the connect store
     */
    doReloadConnectStore: function() {
        var me = this,
            connectForm = me.getConnectForm();

        if (!me.subApplication.article || !me.subApplication.article.get('id')) {
            return;
        }

        me.connectStore.load({
            callback: function(records, operation) {
                if (!operation.wasSuccessful()) {
                    return;
                }
                if (records.length > 0) {
                    me.record = records[0];
                    connectForm.loadRecord(me.record);

                    me.manageFields();
                }
            }
        });


    },

    /**
     * Manage some fields which needs to be disabled for remote products
     */
    manageFields: function() {
        var me = this,
            connectForm = me.getConnectForm(),
            settingsFieldSet = me.subApplication.articleWindow.down('article-settings-field-set'),
            pricesFieldSet = me.subApplication.articleWindow.down('article-prices-field-set'),
            basePriceColumn = me.subApplication.articleWindow.down('article-prices-field-set numbercolumn[dataIndex=basePrice]'),
            shippingField, inStockField,
            isPriceLocked;


        // Unit fields
        var contentLabel = me.getArticleDetailWindow().snippets.basePrice.content;
        var contentField = me.getArticleDetailWindow().down('numberfield[fieldLabel=' + contentLabel + ']');
        var basicUnitLabel = me.getArticleDetailWindow().snippets.basePrice.basicUnit;
        var basicField = me.getArticleDetailWindow().down('numberfield[fieldLabel=' + basicUnitLabel + ']');
        me.getArticleDetailWindow().unitComboBox.setReadOnly(me.record.get('shopId') > 0);
        contentField.setReadOnly(me.record.get('shopId') > 0);
        basicField.setReadOnly(me.record.get('shopId') > 0);

        if (me.record.get('shopId') > 0) {
            connectForm.connectFixedPrice.setReadOnly(true);
        } else if (isFixedPriceAllowed == false) {
            connectForm.connectFixedPriceFieldset.setVisible(false);
        }

        if (settingsFieldSet) {
            shippingField = settingsFieldSet.down('checkboxfield[fieldLabel=' + settingsFieldSet.snippets.shippingFree.field + ']');
            inStockField = settingsFieldSet.down('numberfield[fieldLabel=' + settingsFieldSet.snippets.stock + ']');

            if (shippingField) {
                shippingField.setReadOnly(me.record.get('shopId') > 0);
            }
            if (inStockField) {
                inStockField.setReadOnly(me.record.get('shopId') > 0);
            }
        }

        if (pricesFieldSet) {
            isPriceLocked = me.record.get('fixedPrice') && me.record.get('shopId') > 0;
            pricesFieldSet.tabPanel.setDisabled(isPriceLocked);
            pricesFieldSet.connectLabel.setVisible(isPriceLocked);
        }


        if (basePriceColumn) {
            if (me.record.get('shopId') > 0) {
                basePriceColumn.setEditor(false);
            }
        }
    }


});
//{/block}
