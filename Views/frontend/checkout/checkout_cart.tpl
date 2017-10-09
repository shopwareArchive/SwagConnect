{foreach name=basket from=$connectContent item=connectItems key=shopId}
	{block name="frontend_checkout_connect_second_row"}
		<div class="connect--second-cart">
			{block name='frontend_checkout_cart_cart_head'}
                {if isset($template) && $template == 'confirm'}
                    {include file="frontend/checkout/confirm_header.tpl"}
                    {if $showShippingCostsSeparately}
                        {include file="frontend/checkout/items/dispatch.tpl" hideSinglePrice=true}
                    {/if}
                {else}
                    {include file="frontend/checkout/cart_header.tpl"}
                {/if}
			{/block}

			{foreach name=connectItems from=$connectItems item=sBasketItem key=key}
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