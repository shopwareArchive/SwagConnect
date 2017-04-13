//{block name="backend/article/view/detail/properties" append}
Ext.define('Shopware.apps.Article.view.detail.ConnectProperties', {
    override: 'Shopware.apps.Article.view.detail.Properties',

    /**
     * Creates the form panel at the top of the component containing instructions on how to use the component
     * and the necessary fields
     *
     * @returns { Ext.form.FieldSet }
     */
    createElements: function () {
        var me = this;

        var fieldset = me.callOverridden(arguments);

        me.setComboBox.on('beforeselect', function (combo, record, index, eOpts) {
            if (record.raw['connect']) {
                return false;
            }
        });

        me.groupComboBox.on('beforeselect', function (combo, record, index, eOpts) {
            if (record.raw['connect']) {
                return false;
            }
        });

        me.valueComboBox.on('beforeselect', function (combo, record, index, eOpts) {
            if (record.raw['connect']) {
                return false;
            }
        });

        var style = "padding: 2px 0 6px 20px; margin: 0px 2px 0 0px; width: 20px; height: 20px; display: inline; opacity: 0.4;";

        var tpl = '<tpl for=".">' +
            '<tpl if=" !connect ">' +
            '<div class="x-boundlist-item">{ name }</div>' +
            '<tpl else>' +
            '<div style="color: lightgrey" class="x-boundlist-item"><div class="connect-icon" style="' + style + '"></div>{ name }</div>' +
            '</tpl></tpl>';

        Ext.apply(me.setComboBox, { tpl: tpl });
        Ext.apply(me.groupComboBox, { tpl: tpl });
        Ext.apply(me.valueComboBox, { tpl: tpl });

        return fieldset;
    },

    /**
     * Event listener method which will be called when all necessary stores of the product module are loaded. The method
     * will also be used to change the product in the module using the split view functionality.
     *
     * @param { Ext.data.Model } article
     * @param { Array } stores
     */
    onStoresLoaded: function (article, stores) {
        var me = this;

        me.article = article;
        me.store = Ext.data.StoreManager.lookup('Property');
        me.propertyGrid.reconfigure(me.store);

        me.propertySetStore = Ext.create('Shopware.store.Search', {
            fields: ['id', 'name', 'connect'],
            pageSize: 10,
            configure: function () {
                return { entity: 'Shopware\\Models\\Property\\Group' };
            }
        });
        me.propertyGroupStore = Ext.create('Shopware.store.Search', {
            fields: ['id', 'name', 'connect'],
            pageSize: 10,
            configure: function () {
                return { entity: 'Shopware\\Models\\Property\\Option' };
            }
        });
        me.propertyValueStore = Ext.create('Shopware.store.Search', {
            fields: [
                { name: 'id', type: 'string' },
                { name: 'name', type: 'string', mapping: 'value' },
                { name: 'connect', type: 'boolean' }
            ],
            pageSize: 10,
            configure: function () {
                return { entity: 'Shopware\\Models\\Property\\Value' };
            }
        });


        if (me.article.get('filterGroupId')) {
            me.propertySetStore.load({
                id: me.article.get('filterGroupId')
            });
        }

        me.groupComboBox.bindStore(me.propertyGroupStore);
        me.setComboBox.bindStore(me.propertySetStore);
        me.valueComboBox.bindStore(me.propertyValueStore);

        me.loadRecord(me.article);
    },

    /**
     * Renders the values and wraps them into a "<ul>" list.
     *
     * @param { Array } values
     * @param { String } style
     * @param { Ext.data.Model } model
     * @returns { String }
     */
    valueRenderer: function(values, style, model) {
        var me = this,
            result = [ Ext.String.format('<ul class="[0]item-bubble-list">', Ext.baseCSSPrefix) ];

        Ext.each(values, function(value) {
            if(!value) {
                return;
            }

            if (value.connect) {
                result.push(Ext.String.format(
                    '<li><span class="[0]item-bubble connect' +'" data-value-id="[1]" data-row-id="[2]">[3]</span></li>',
                    Ext.baseCSSPrefix, value.id, model.data.id, value.value
                ));

                // We disable the set combo box if there is a property that came from connect.
                me.setComboBox.disable();
            } else {
                // Use the default rendering
                result.push(Ext.String.format(
                    '<li><span class="[0]item-bubble" data-value-id="[1]" data-row-id="[2]">[3]<span class="cross-btn">x</span></span></li>',
                    Ext.baseCSSPrefix, value.id, model.data.id, value.value
                ));
            }
        });
        result.push('</ul>');

        return result.join(' ');
    },

    /**
     * Event listener method which will be called when the user interacts with a property value in the grid. The method
     * removes the property value from the grid and therefore from product.
     *
     * @param { Ext.EventImpl } event
     * @param { Ext.dom.Element } item
     */
    onDeleteElement: function(comp, record, dom, index, event) {
        var me = this,
            element = Ext.get(event.target);

        if (element.hasCls('connect')) {
            //Connect properties cannot be deleted
            return;
        }

        me.callOverridden(arguments)
    }
});
//{/block}
