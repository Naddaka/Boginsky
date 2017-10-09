<!-- .page-header-small -->
<div id="linker" class="work-navigation clearfix">
	<div class="row">
		
		<div class="col-xs-5 text-left">
			{if $prev_link}
			<a href="{$prev_link.url}" class="work-prev page-load" data-title="{$prev_link.title}"><i class="fa fa-angle-left"></i>&nbsp;{lang('Prev page', 'linker')}</a> 
			{/if}
		</div>
		
		<div class="col-xs-2 text-center"> <a href="../{$page.cat_url}" class="work-all page-load" data-title="All">{echo siteinfo('siteinfo_transl-all')}</a> </div>
		
		<div class="col-xs-5 text-right">
			{if $next_link}
			<a href="{$next_link.url}" class="work-next page-load" data-title="{$next_link.title}">{lang('Next page', 'linker')}&nbsp;<i class="fa fa-angle-right"></i></a>
			{/if}
		</div>
		
	</div>
</div>