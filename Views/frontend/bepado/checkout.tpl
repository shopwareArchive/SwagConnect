{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
{/block}

{block name='frontend_checkout_cart_cart_head' prepend}
{if $bepadoContent || $shopId}
    {include file='frontend/bepado/shop_header.tpl'}
{/if}
{/block}

{block name='frontend_checkout_cart_premiums' prepend}
    {foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}

        {include file='frontend/bepado/shop_header.tpl'}

        {include file="frontend/checkout/cart_header.tpl"}

        {foreach name=basket from=$bepadoItems item=sBasketItem key=key}
            {block name='frontend_checkout_cart_item'}
                {include file='frontend/checkout/cart_item.tpl'}
            {/block}
        {/foreach}
    {/foreach}
{/block}

{block name='frontend_checkout_cart_item_image' prepend}
    {if $shopId}
        <span class="checkout_item_bepado"><span>&nbsp;</span></span>
    {/if}
{/block}