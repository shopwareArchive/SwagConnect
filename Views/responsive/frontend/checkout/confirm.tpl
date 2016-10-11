{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_confirm_footer" prepend}
	{include file="frontend/checkout/checkout_cart.tpl" template="confirm"}
{/block}

{block name="frontend_checkout_confirm_confirm_head" append}
	{assign var="lastProduct" value=$sBasket.content|@end}
	{if counter eq 0 && $connectContent && $showShippingCostsSeparately}
		{include file="frontend/checkout/items/dispatch.tpl" hideSinglePrice=true}
	{/if}
{/block}

{block name="frontend_checkout_confirm_submit"}
	{block name="frontend_checkout_connect_submit_message"}
		{if $connectMessages}
			{* do not show submit button when $connectMessages is not empty *}
		{elseif $phoneMissing}
			{include file="frontend/_includes/messages.tpl" type="error" content="<a href='{url controller=account action=billing sTarget=checkout}'>{s namespace="frontend/checkout/connect" name="frontend_checkout_cart_connect_phone"}You need to leave your phone number in order to purchase these products. Click here in order to change your phone number now.{/s}</a>"}
		{else}
			{$smarty.block.parent}
		{/if}
	{/block}
{/block}