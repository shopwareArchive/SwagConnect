{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
{/block}

{block name='frontend_checkout_cart_cart_head' prepend}
    <div class="table_row" style="border: none; min-height:20px">
        <div class="grid_9 box">
            <h3 style="font-weight: bold; margin-left: 12px; display: inline-block;">Artikel von {$sShopname}</h3>
            <hr class="clear">
        </div>
    </div>
{/block}

{block name='frontend_checkout_cart_premiums' prepend}
    {foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}

        <div class="table_row" style="border: none; min-height:20px">
            <div class="grid_9 box">
                <span style="margin-left: 12px; background-color: #DD4800; color: #FFF; font-size: 10px; font-weight: bold; padding: 3px; text-transform: uppercase;">Seperater Versand</span>
                <h3 style="font-weight: bold; margin-left: 12px; display: inline-block;">Artikel von {$bepadoShops[$shopId]->name}</h3>
                <hr class="clear">
            </div>
        </div>

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