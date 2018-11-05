/**
 * Extend the article's price fieldset in order to disable it if the price was configured as fixedPrice in the source shop
 */
//{namespace name="backend/connect/view/main"}
//{block name="backend/article/view/detail/prices" append}
Ext.define('Shopware.apps.Article.view.detail.PricesConnect', {
    override: 'Shopware.apps.Article.view.detail.Prices',

    createElements: function() {
        var me = this,
            attributes,
            style,
            label,
            tabPanel;


        tabPanel =  me.callOverridden(arguments);

        if ('{$disableConnectPrice}' != 'true') {
            me.registerCellEditListener();
        }

        style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';
        var fixedPriceMessage = Ext.String.format('{s name="connect/connectFixedPriceMessage"}Für dieses [0]-Produkt wurde die Preisbindung vom Anbieter aktiviert. Aus diesem Grund kann der Preis für dieses Produkt nicht verändert werden.{/s}', marketplaceName);

        me.connectLabel = Ext.create('Ext.form.Label', {
            hidden: true,
            html: '<div title="" class="connect-icon" ' + style + '>&nbsp;</div>' + fixedPriceMessage
        });

        return [
            me.connectLabel,
            tabPanel
        ];

    },

    /**
     * In order to update the record properly, register on cell edit events and set the price record
     */
    registerCellEditListener: function() {
        var me = this;

        Ext.each(me.priceGrids, function(grid) {
            grid.on('edit', function(editor, e) {
                var value = e.value,
                    record = e.record,
                    attributeStore, attribute;

                // Return if another field was edited or we don't have a record
                if (e.field != 'attribute[connectPrice]' || !record) {
                    return;
                }

                // Make sure that we have a proper attributesStore
                if (record.hasOwnProperty('getAttributesStore')) {
                    attributeStore = record.getAttributes()
                }

                // Update the record
                if (attributeStore) {
                    attribute = attributeStore.first();
                    attribute.set('connectPrice', value);
                    attributeStore.removeAll();
                    attributeStore.add(attribute);
                }
            });
        });
    },

    /**
     * @Override preparePriceStore to make sure, that new products have a price attribute as well
     */
    preparePriceStore: function() {
        var me = this,
            record;

        me.callParent(arguments);

        if ('{$disableConnectPrice}' == 'true') {
            return;
        }

        // Get the first price
        record = me.priceStore.first();

        if (!record) {
            return;
        }

        // Make sure that we have a proper attributesStore
        if (!record.hasOwnProperty('getAttributesStore')) {
            record.getAttributesStore = Ext.create('Ext.data.Store', {
                model: 'Shopware.apps.Article.model.PriceAttribute'
            });
            record.getAttributesStore.add(Ext.create('Shopware.apps.Article.model.PriceAttribute', {

            }));

        }
    },

    /**
     * @Override getColumn in order to add the bepdo price column
     *
     * @returns Array
     */
    getColumns: function() {
        var me = this,
            columns = me.callParent(arguments);

        if ('{$disableConnectPrice}' == 'true') {
            return columns;
        }

        columns.splice(-1, 0, {
            xtype: 'numbercolumn',
            header: Ext.String.format("{s name=detail/price/connectPrice}[0] Preis{/s}", marketplaceName),
            renderer: function (value, arg, record) {
                if (value != undefined) {
                    return Ext.util.Format.number(value, '0.00');
                }

                var attributeStore,
                    attribute;

                if (record.hasOwnProperty('getAttributesStore')) {
                    attributeStore = record.getAttributes()
                }

                if (attributeStore) {
                    attribute = attributeStore.first();
                    return Ext.util.Format.number(attribute.get('connectPrice'), '0.00');
                }
            },
            dataIndex: 'attribute[connectPrice]',
            editor: {
                xtype: 'numberfield',
                decimalPrecision: 2,
                minValue: 0
            }
        });
        return columns;

    }
});
//{/block}

