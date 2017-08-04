<!-- Шаблон category.tpl -->

<!-- header.tpl -->
{include_tpl('header')}

<div class="page-section" data-background-color="#f8f8f8">
	<div class="container">
		<div class="row">
			{foreach $pages as $page}
			{$page = $CI->load->module('cfcm')->connect_fields($page, 'page')}

			<div class="col-sm-4 latest-post">
				<div class="post-img"> 
					<div class="img-news" style="background-image:url({$page.field_pagephoto})">
					</div>
				</div>
				<h3 class="text-uppercase mb5">{$page.title}</h3>
				<div class="post-meta mb17">{date('Y', $page.publish_date)} |
{date('d.m', $page.publish_date)}</div>
				<p class="excerpt">{strip_tags($page.prev_text)}</p> 
				<a href="{site_url($page.full_url)}" class="btn btn-primary btn-xs">Read More <i class="fa fa-angle-right"></i></a>
			</div>
			<!-- .latest-post -->
			{/foreach}   
			<div class="col-sm-12">{$pagination}</div>
		</div>
	</div>
</div>
