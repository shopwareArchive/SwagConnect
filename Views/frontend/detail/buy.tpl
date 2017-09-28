{extends file="parent:frontend/detail/buy.tpl"}

{block name="frontend_detail_buy_button_container"}
	{if $hideConnect}
		{include file="frontend/_includes/messages.tpl" type="error" content="{s name='DetailBuyInfoNotAvailable' namespace='frontend/detail/buy'}{/s}"}
	{elseif $connectProduct}
		{block name="frontend_detail_connect_buy_container"}
			{if $connectShopInfo}
				<div class="connect--buy-container">
				{$smarty.block.parent}

				<span class="connect--detail-product-headline is--strong">{s namespace="frontend/detail/connect" name=connect_detail_marketplace_article}Marktplatz-Artikel von {$connectShop->name}{/s}</span>
				</div>
			{else}
				{$smarty.block.parent}
			{/if}
		{/block}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}