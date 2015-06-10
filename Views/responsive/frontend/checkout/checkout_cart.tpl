{foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}
	{block name="frontend_checkout_bepado_second_row"}
		<div class="bepado--second-cart">
			{block name='frontend_checkout_cart_cart_head'}
                {if isset($template) && $template == 'confirm'}
                    {include file="frontend/checkout/confirm_header.tpl"}
                    {include file="frontend/checkout/items/dispatch.tpl" hideSinglePrice=true}
                {else}
                    {include file="frontend/checkout/cart_header.tpl"}
                {/if}
			{/block}

			{foreach name=bepadoItems from=$bepadoItems item=sBasketItem key=key}
				{block name='frontend_checkout_cart_item'}
                    {if isset($template) && $template == 'confirm'}
                        {include file='frontend/checkout/confirm_item.tpl'}
                    {else}
                        {include file='frontend/checkout/cart_item.tpl'}
                    {/if}
				{/block}
			{/foreach}
		</div>
	{/block}
{/foreach}