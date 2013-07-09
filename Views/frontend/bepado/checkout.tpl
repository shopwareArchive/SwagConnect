{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
{/block}

{block name='frontend_checkout_cart_premiums' prepend}
    {include file='frontend/bepado/checkout_cart.tpl'}
{/block}

{block name='frontend_checkout_cart_item_image' prepend}
    {if $shopId && $bepadoShopInfo}
        <span class="checkout_item_bepado"><span>&nbsp;</span></span>
    {/if}
{/block}

{block name='frontend_checkout_cart_item' append}
    {if $shopId}
        <div class="bepado-additional-info-checkout">
            <span class="bepado-label label-separate-dispatch">{s name="frontend_checkout_cart_bepado_dispatch"}Separater Versand{/s}</span>
            {if $bepadoShopInfo}
                <span class="bepado-display display-shop-name">Artikel von {$bepadoShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}
{/block}

{block name='frontend_checkout_cart_cart_head' append}

	{if $bepadoMessages[$shopId]}
		<div class="error" style="margin:0">
			{foreach from=$bepadoMessages[$shopId] item=bepadomessage}
				{$message = $bepadomessage->message}
				{foreach from=$bepadomessage->values key=key item=value}
					{$message = "%{$key}"|str_replace:$value:$message}
				{/foreach}
				{$message}<br>
			{/foreach}
		</div>
	{/if}

    {$smarty.block.parent}
	{if $bepadoContent}
    	{include file='frontend/bepado/shop_header.tpl' hideSinglePrice=false}
	{/if}
{/block}

{block name='frontend_checkout_confirm_item'}
	{if $key eq 0 && $bepadoContent}
    	{include file='frontend/bepado/shop_header.tpl' hideSinglePrice=true}
	{/if}

	{if !$sBasketItem.bepadoShopId || !$bepadoContent}
		{$smarty.block.parent}
	{/if}

	{if $sBasket.content|count -1 eq $key}
		{include file='frontend/bepado/checkout_cart.tpl'}
	{/if}
{/block}