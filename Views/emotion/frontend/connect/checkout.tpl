{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/connect.css'}" />
{/block}
{block name="frontend_index_header_javascript" append}
    {if $connectProduct || $hasConnectProduct}
        <script src="{link file='frontend/_resources/javascripts/connect.js'}"></script>
    {/if}
{/block}

{block name='frontend_checkout_cart_premiums' prepend}
    {include file='frontend/connect/checkout_cart.tpl'}
{/block}

{block name='frontend_checkout_confirm_premiums' prepend}
    {include file='frontend/connect/checkout_cart.tpl'}
{/block}

{block name='frontend_checkout_cart_item_image' prepend}
    {if $shopId && $connectShopInfo}
        <span class="checkout_item_connect"><span>&nbsp;</span></span>
    {/if}
{/block}

{block name='frontend_checkout_cart_item' append}
    {if $shopId}
        <div class="connect-additional-info-checkout">
            <span class="connect-label label-separate-dispatch">{s name="frontend_checkout_cart_connect_dispatch"}Separater Versand{/s}</span>
            {if $connectShopInfo}
                <span class="connect-display display-shop-name">Artikel von {$connectShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}
{/block}

{block name='frontend_checkout_error_messages_voucher_error' append}
{* Voucher error *}
{if $phoneMissing}
    {include 'frontend/connect/phone_message.tpl'}
{/if}
{/block}

{*
    Show message during checkout, if product price / availability has changed
*}
{block name='frontend_checkout_cart_error_messages' append}
    {if $connectMessages}
        <div class="doublespace"></div>
        <div class="error" style="margin:0">
            {foreach from=$connectMessages item=connectmessage}
                {$message = $connectmessage->message}
                {foreach from=$connectmessage->values key=key item=value}
                    {$message = "%{$key}"|str_replace:$value:$message}
                {/foreach}
                {$message}<br>
            {/foreach}
            <br>
            <a href="{url}">{s name="frontend_checkout_cart_connect_refresh"}Klicken Sie hier um die Seite zu aktualisieren{/s}</a>
        </div>
        <div class="space"></div>
    {/if}
{/block}

{block name='frontend_checkout_cart_cart_head' append}
    {$smarty.block.parent}

	{if $connectShops and $showShippingCostsSeparately}
    	{include file='frontend/connect/shop_header.tpl' hideSinglePrice=false}
	{/if}
{/block}


{block name='frontend_checkout_confirm_item'}
    {assign var="lastProduct" value=$sBasket.content|@end}

    {if counter eq 0 && $connectContent}
        {include file='frontend/connect/shop_header.tpl' hideSinglePrice=true}
    {/if}

    {if (!$sBasketItem.connectShopId || !$connectContent) && !$shopId}
        {$smarty.block.parent}

        {if $lastProduct.id eq $sBasketItem.id}
            <div class="border-top">
            </div>
        {/if}
    {/if}

    {if $shopId}
        {include file='frontend/checkout/cart_item.tpl'}
        <div class="connect-additional-info-checkout">
            <span class="connect-label label-separate-dispatch">{s name="frontend_checkout_cart_connect_dispatch"}Separater Versand{/s}</span>
            {if $connectShopInfo}
                <span class="connect-display display-shop-name">Artikel von {$connectShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}

    {if $lastProduct.id eq $sBasketItem.id}
        {include file='frontend/connect/checkout_cart.tpl'}
    {/if}

{/block}



{*
    Hide "buy" button on checkout finish if messages where passed.
*}
{block name='frontend_checkout_confirm_submit' prepend}
    {if $connectMessages}
        {include 'frontend/connect/finish_message.tpl'}
    {elseif $phoneMissing}
        {include 'frontend/connect/phone_message.tpl'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
{block name='frontend_checkout_confirm_stockinfo'}
    {if $connectMessages}
        {include 'frontend/connect/finish_message.tpl'}
    {elseif $phoneMissing}
        {include 'frontend/connect/phone_message.tpl'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}