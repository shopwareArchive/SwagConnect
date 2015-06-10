{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_meta_robots"}
	{if $bepadoNoIndex}noindex,follow{else}{$smarty.block.parent}{/if}
{/block}