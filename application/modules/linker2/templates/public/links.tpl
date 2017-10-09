<!-- .page-header-small -->
<div id="linker" class="work-navigation clearfix">
	<div class="row">
		
		<div class="col-xs-6 text-left">
			{if $prev_link}
			<a href="{$prev_link.url}" class="work-prev page-load" data-title="{$prev_link.title}"><i class="fa fa-angle-left"></i>&nbsp;{lang('Prev page', 'linker2')}</a> 
			{/if}
		</div>
		
		<div class="col-xs-6 text-right">
			{if $next_link}
			<a href="{$next_link.url}" class="work-next page-load" data-title="{$next_link.title}">{lang('Next page', 'linker2')}&nbsp;<i class="fa fa-angle-right"></i></a>
			{/if}
		</div>
		
	</div>
</div>