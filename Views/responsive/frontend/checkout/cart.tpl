{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_cart_cart_footer" prepend}
	{include file="frontend/checkout/checkout_cart.tpl"}
{/block}

{block name="frontend_checkout_cart_cart_head" append}
	{if $bepadoShops}
		{include file="frontend/checkout/items/dispatch.tpl"}
	{/if}
{/block}