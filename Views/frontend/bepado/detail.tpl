{* Include the stylesheet if we're dealing with an bepado product *}
{block name="frontend_index_header_css_screen" append}
	{if $bepadoProduct}
		<link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
	{/if}
{/block}

{* Article price *}
{block name='frontend_detail_buy_button'}
    {if $hideBepado}
        <div class="space">&nbsp;</div>
        <div class="error bold center">
            {s name="DetailBuyInfoNotAvailable" namespace="frontend/detail/buy"}{/s}
        </div>
	{elseif $bepadoProduct}
		{* Include the basket button *}
		<div class="bepado-detail-product">
			{$smarty.block.parent}

            {if $bepadoShopInfo}
                <strong class="bepado-detail-product-headline">{s namespace="frontend/detail/bepado" name=bepado_detail_marketplace_article}Marktplatz-Artikel von {$bepadoShop->name}{/s}</strong>
            {else}
                <strong class="bepado-detail-product-headline">{s namespace="frontend/detail/bepado" name=bepado_detail_marketplace_article_implicit}Marktplatz-Artikel von {$bepadoShop->id}{/s}</strong>
            {/if}
			{*<p class="bepado-detail-product-desc">*}
				{*{s name=bepado/detail/dispatch_info}Die Versandkosten für diesen Artikel werden separat berechnet.{/s}*}
			{*</p>*}
		</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_index_header_meta_robots'}{if $bepadoNoIndex}noindex,follow{else}{$smarty.block.parent}{/if}{/block}