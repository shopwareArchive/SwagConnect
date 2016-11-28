//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/price/checkboxcolumn"}
Ext.define('Shopware.apps.Connect.view.export.price.Checkboxcolumn', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.checkboxcolumn',

    constructor: function() {
        this.addEvents(
            /**
             * @event beforecheckchange
             * Fires when before checked state of a row changes.
             * The change may be vetoed by returning `false` from a listener.
             * @param { Ext.ux.CheckColumn } this CheckColumn
             * @param { Number } rowIndex The row index
             * @param { Boolean } checked True if the box is to be checked
             */
            'beforecheckchange',
            /**
             * @event checkchange
             * Fires when the checked state of a row changes
             * @param { Ext.ux.CheckColumn } this
             * @param { Number } rowIndex The row index
             * @param { Boolean } checked True if the box is checked
             */
            'checkchange'
        );
        this.callParent(arguments);
    },

    /**
     * @private
     * Process and refire events routed from the GridView's processEvent method.
     */
    processEvent: function(type, view, cell, recordIndex, cellIndex, e, record, row) {
        var me = this,
            key = type === 'keydown' && e.getKey(),
            mousedown = type == 'mousedown';

        if (mousedown || (key == e.ENTER || key == e.SPACE)) {
            var dataIndex = me.dataIndex,
                checked = !record.get(dataIndex);

            if (!record.get(me.columnType + 'Available')) {
                return e.stopEvent();
            }

            // Allow apps to hook beforecheckchange
            if (me.fireEvent('beforecheckchange', me, recordIndex, checked) !== false) {
                record.set(dataIndex, checked);
                me.fireEvent('checkchange', me, recordIndex, checked);

                // Mousedown on the now nonexistent cell causes the view to blur, so stop it continuing.
                if (mousedown) {
                    e.stopEvent();
                }

                // Selection will not proceed after this because of the DOM update caused by the record modification
                // Invoke the SelectionModel unless configured not to do so
                if (!me.stopSelection) {
                    view.selModel.selectByPosition({
                        row: recordIndex,
                        column: cellIndex
                    });
                }

                // Prevent the view from propagating the event to the selection model - we have done that job.
                return false;
            } else {
                // Prevent the view from propagating the event to the selection model if configured to do so.
                return !me.stopSelection;
            }
        } else {
            return me.callParent(arguments);
        }
    },

    renderer : function(value, metaData, record, rowIndex, columnIndex){
        var me = this,
            readOnly = '',
            columnType = me.columns[columnIndex].columnType;

        var cssPrefix = Ext.baseCSSPrefix,
            checked = '',
            cls = [cssPrefix + 'grid-checkheader', 'connect-checkbox'];

        if (value) {
            checked = 'checked';
            cls.push(cssPrefix + 'grid-checkheader-checked');
        }
        else {
            cls.push(cssPrefix + 'grid-checkheader');
        }

        var configuredProducts = record.get(columnType + 'ConfiguredProducts');
        var totalProducts = record.get('productCount');

        var counterText = Ext.String.format('{s name=config/checkboxes/product_counts}[0] from [1] products{/s}', configuredProducts, totalProducts);

        var productCounter = "<span style='position: absolute; left: 20px'>" + counterText + "</span>";

        if (!record.get(columnType + 'Available')) {
            return '<div style="position: relative"><input type="checkbox" class="' + cls.join(' ') + '" value="1" readonly ' + checked + '/>' + productCounter + '<div class="export-window-wrapper export-window-mask"></div></div>';
        }

        return '<div style="position: relative"><input type="checkbox" class="' + cls.join(' ') + '" value="1" ' + checked + ' ' + readOnly + ' />' + productCounter + '</div>';
    }
});
//{/block}