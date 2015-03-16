{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_confirm_footer" prepend}
	{include file="frontend/checkout/checkout_cart.tpl"}
{/block}

{block name="frontend_checkout_confirm_confirm_head" append}
	{assign var="lastProduct" value=$sBasket.content|@end}
	{if counter eq 0 && $bepadoContent}
		{include file="frontend/checkout/items/dispatch.tpl"}
	{/if}
{/block}

{block name="frontend_checkout_confirm_submit"}
	{block name="frontend_checkout_bepado_submit_message"}
		{if $bepadoMessages}
			{foreach from=$bepadoMessages item=bepadoShop}
				{foreach from=$bepadoShop item=bepadomessage}
					{$message = $bepadomessage->message}
					{foreach from=$bepadomessage->values key=key item=value}
						{$message = "%{$key}"|str_replace:$value:$message}
					{/foreach}
					{$messages[] = $message}
				{/foreach}
			{/foreach}

			{include file="frontend/_includes/messages.tpl" type="error" list=$messages}
		{elseif $phoneMissing}
			{include file="frontend/_includes/messages.tpl" type="error" content="<a href='{url controller=account action=billing sTarget=checkout}'>{s namespace="frontend/checkout/bepado" name="frontend_checkout_cart_bepado_phone"}You need to leave your phone number in order to purchase these products. Click here in order to change your phone number now.{/s}</a>"}
		{else}
			{$smarty.block.parent}
		{/if}
	{/block}
{/block}