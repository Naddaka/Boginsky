<!-- main_menu/level_0/item_last_active.tpl -->
{$category = get_category(1)}
{$item = $CI->load->module('cfcm')->connect_fields($category, 'category')}
	<li><a href="{$link}" class="page-load" {$target} title="{$title}" data-title="{$title}" data-color="{if strip_tags($category.field_menutextcolor) == 'Black'}#000{/if}{if strip_tags($category.field_menutextcolor) == 'White'}#fff{/if}{if strip_tags($category.field_menutextcolor) == 'Gray'}#555{/if}{if strip_tags($category.field_menutextcolor) == 'Color 1'}color:{echo siteinfo('siteinfo_color')}{/if}{if strip_tags($category.field_menutextcolor) == 'Color 2'}color:{echo siteinfo('siteinfo_color2')}{/if}">{$title}</a>{$wrapper}</li>