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
//{block name="backend/payment/view/payment/formpanel" append}
Ext.define('Shopware.apps.payment.view.payment.Connect', {
    override: 'Shopware.apps.Payment.view.payment.FormPanel',

    /**
     * This function creates form items
     * @return Array
     */
    getItems: function(){
        var me = this, result;

        result = me.callParent(arguments);

        result.push({
            xtype: 'checkbox',
            fieldLabel: Ext.String.format('{s name=payment/connectAllowed}Freigegeben für [0]{/s}', marketplaceName),
            inputValue: 1,
            uncheckedValue: 0,
            name: 'connectIsAllowed'
        });

        return result;
    }
});
//{/block}
