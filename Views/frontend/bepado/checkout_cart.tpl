{foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}

    {include file="frontend/checkout/cart_header.tpl"}

    {foreach name=basket from=$bepadoItems item=sBasketItem key=key}
        {block name='frontend_checkout_cart_item'}
            {include file='frontend/checkout/cart_item.tpl'}

			{if $shopId}
				<div class="bepado-additional-info-checkout">
					<span class="bepado-label label-separate-dispatch">{s name="frontend_checkout_cart_bepado_separate_dispatch"}Separater Versand{/s}</span>
					<span class="bepado-display display-shop-name">Artikel von {if $shopId}{*$bepadoShops[$shopId]->name*}Libri.de Internet GmbH{else}{$sShopname}{/if}</span>
				</div>
			{/if}
        {/block}
    {/foreach}
{/foreach}