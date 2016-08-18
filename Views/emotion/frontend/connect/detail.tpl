{* Include the stylesheet if we're dealing with an connect product *}
{block name="frontend_index_header_css_screen" append}
	{if $connectProduct}
		<link rel="stylesheet" href="{link file='frontend/_resources/styles/connect.css'}" />
	{/if}
{/block}

{* Article price *}
{block name='frontend_detail_buy_button'}
    {if $hideConnect}
        <div class="space">&nbsp;</div>
        <div class="error bold center">
            {s name="DetailBuyInfoNotAvailable" namespace="frontend/detail/buy"}{/s}
        </div>
	{elseif $connectProduct}
		{* Include the basket button *}
		<div class="connect-detail-product">
			{$smarty.block.parent}

            {if $connectShopInfo}
                <strong class="connect-detail-product-headline">{s namespace="frontend/detail/connect" name=connect_detail_marketplace_article}Marktplatz-Artikel von {$connectShop->name}{/s}</strong>
            {/if}
			{*<p class="connect-detail-product-desc">*}
				{*{s name=connect/detail/dispatch_info}Die Versandkosten für diesen Artikel werden separat berechnet.{/s}*}
			{*</p>*}
		</div>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name='frontend_index_header_meta_robots'}{if $connectNoIndex}noindex,follow{else}{$smarty.block.parent}{/if}{/block}