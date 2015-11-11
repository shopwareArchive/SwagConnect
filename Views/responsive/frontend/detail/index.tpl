{extends file="parent:frontend/detail/index.tpl"}

{block name="frontend_index_header_meta_robots"}
	{if $connectNoIndex}noindex,follow{else}{$smarty.block.parent}{/if}
{/block}