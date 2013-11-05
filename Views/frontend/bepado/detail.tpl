{* Include the stylesheet if we're dealing with an bepado product *}
{block name="frontend_index_header_css_screen" append}
	{if $bepadoProduct}
		<link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
	{/if}
{/block}

{block name='frontend_detail_buy_button'}
	{if $bepadoShopInfo && $bepadoProduct}
		{* Include the basket button *}
		<div class="bepado-detail-product">
			{$smarty.block.parent}

			<strong class="bepado-detail-product-headline">{s name=bepado/detail/marketplace_article}Marktplatz-Artikel von {$bepadoShop->name}{/s}</strong>
			<p class="bepado-detail-product-desc">
				{s name=bepado/detail/dispatch_info}Die Versandkosten für diesen Artikel werden separat berechnet.{/s}
			</p>
		</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_index_header_meta_robots'}{if $bepadoNoIndex}noindex,follow{else}{$smarty.block.parent}{/if}{/block}