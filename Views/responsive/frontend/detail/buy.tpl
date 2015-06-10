{extends file="parent:frontend/detail/buy.tpl"}

{block name="frontend_detail_buy_button_container"}
	{if $hideBepado}
		{include file="frontend/_includes/messages.tpl" type="error" content="{s name='DetailBuyInfoNotAvailable' namespace='frontend/detail/buy'}{/s}"}
	{elseif $bepadoProduct}
		{block name="frontend_detail_bepado_buy_container"}
			<div class="bepado--buy-container">
				{$smarty.block.parent}

				{if $bepadoShopInfo}
					<span class="bepado--detail-product-headline is--strong">{s namespace="frontend/detail/bepado" name=bepado_detail_marketplace_article}Marktplatz-Artikel von {$bepadoShop->name}{/s}</span>
				{else}
					<span class="bepado--detail-product-headline is--strong">{s namespace="frontend/detail/bepado" name=bepado_detail_marketplace_article_implicit}Marktplatz-Artikel von {$bepadoShop->id}{/s}</span>
				{/if}
			</div>
		{/block}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}