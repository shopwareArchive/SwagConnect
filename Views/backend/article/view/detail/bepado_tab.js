/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

//{namespace name="backend/bepado/view/main"}
//{block name="backend/article/view/detail/window" append}
Ext.define('Shopware.apps.Article.view.detail.Bepado', {
    override: 'Shopware.apps.Article.view.detail.Window',

    /**
     * @Override
     * Creates the main tab panel which displays the different tabs for the article sections.
     * To extend the tab panel this function can be override.
     *
     * @return Ext.tab.Panel
     */
    createMainTabPanel: function() {
        var me = this, result;

        result = me.callParent(arguments);

        me.registerAdditionalTab({
            title: Ext.String.format('{s name=window/bepado_tab}[0]{/s}', marketplaceName),
            contentFn: me.createBepadoTab,
            articleChangeFn: me.articleChangeBepado,
            tabConfig: {
                layout: {
                    type: 'fit',
                    align: 'stretch'
                },
                listeners: {
                    activate: function() {
                        me.fireEvent('bepadoTabActivated', me);
                    },
                    deactivate: function() {
                        me.fireEvent('bepadoTabDeactivated', me);
                    }
                }
            },
            scope: me
        });

        return result;
    },

    /**
     * Callback function called when the article changed (splitview).
     *
     * @param article
     * @param tabConfig
     */
    articleChangeBepado: function(article, tabConfig) {
        var me = this;

        me.bepadoStore.getProxy().extraParams.articleId = me.article.get('id');
        me.fireEvent('bepadoStoreReloadNeeded');
    },

    /**
     * Creates the tab container for the abo commerce plugin.
     * @return Ext.container.Container
     */
    createBepadoTab: function(article, stores, eOpts) {
        var me = this,
            tab = eOpts.tab;

        me.bepadoTab = tab;

        tab.add(me.createBepadoComponents());

        var controller = me.subApplication.getController('Bepado');

        me.bepadoStore = Ext.create('Shopware.apps.Article.store.Bepado');
        me.bepadoStore.getProxy().extraParams.articleId = me.article.get('id');

        controller.bepadoStore = me.bepadoStore;
        controller.doReloadBepadoStore();

        tab.setDisabled(article.get('id') === null)
    },

    createBepadoComponents: function() {
        var me = this;

        return Ext.create('Shopware.apps.Article.view.BepadoForm');
    }


});
//{/block}