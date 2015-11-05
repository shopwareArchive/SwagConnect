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

//{namespace name="backend/connect/view/main"}
//{block name="backend/article/view/detail/window" append}
Ext.define('Shopware.apps.Article.view.detail.Connect', {
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
            title: Ext.String.format('{s name=window/connect_tab}[0]{/s}', marketplaceName),
            contentFn: me.createConnectTab,
            articleChangeFn: me.articleChangeConnect,
            tabConfig: {
                layout: {
                    type: 'fit',
                    align: 'stretch'
                },
                listeners: {
                    activate: function() {
                        me.fireEvent('connectTabActivated', me);
                    },
                    deactivate: function() {
                        me.fireEvent('connectTabDeactivated', me);
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
    articleChangeConnect: function(article, tabConfig) {
        var me = this;

        me.connectStore.getProxy().extraParams.articleId = me.article.get('id');
        me.fireEvent('connectStoreReloadNeeded');
    },

    /**
     * Creates the tab container for the abo commerce plugin.
     * @return Ext.container.Container
     */
    createConnectTab: function(article, stores, eOpts) {
        var me = this,
            tab = eOpts.tab;

        me.connectTab = tab;

        tab.add(me.createConnectComponents());

        var controller = me.subApplication.getController('Connect');

        me.connectStore = Ext.create('Shopware.apps.Article.store.Connect');
        me.connectStore.getProxy().extraParams.articleId = me.article.get('id');

        controller.connectStore = me.connectStore;
        controller.doReloadConnectStore();

        tab.setDisabled(article.get('id') === null)
    },

    createConnectComponents: function() {
        var me = this;

        return Ext.create('Shopware.apps.Article.view.ConnectForm');
    }


});
//{/block}