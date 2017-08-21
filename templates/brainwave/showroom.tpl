<!-- showroom.tpl -->
<!-- header.tpl -->{include_tpl('header')}

<div id="showroom">
	<div class="container">
	<div class="row">
		<div class="col-sm-3">
			<div class="portfolio-filter">
				<button class="btn btn-default btn-xs active" data-filter="*">{echo siteinfo('siteinfo_transl-all')}</button>
				{$sub_cats = get_sub_categories($category.id)}
		        {foreach $sub_cats as $sub_cat}
		          <button class="btn btn-default btn-xs" data-filter=".{$sub_cat.url}">{$sub_cat.name}</button>
		        {/foreach}
			</div>
			</div>
			<div class="col-sm-9">
				<ul class="portfolio-list three-column list-unstyled">
					<li class="grid-sizer"></li>
					       {foreach $pages as $page}
        {$item = $CI->load->module('cfcm')->connect_fields($page, 'page')}
        {$c = explode("/", rtrim($page.cat_url, "/"))}

					<li class="portfolio-item {if $c[1]}{$c[1]}{else:}{$c[0]}{/if} work-ajax-link">
						<a href="{site_url($page.full_url)}" class="page-load" data-title="{$page.title}"> 
						<img src="{$item.field_pagephoto}" alt="" width="400" height="320">
							<div class="info-container">
								<div class="title">{$page.title}</div>
								<div class="category">{$page.prev_text}</div>
							</div>
						</a>
					</li>
					<!-- .portfolio-item -->
		{/foreach}
				</ul>
			</div>
		</div>
	</div>
</div>
<!-- contact_form.tpl -->
<a id="sec-instagram" href="https://www.instagram.com/boginsky_luxury_custom/" data-title="" target="_blank"> 
<div class="container">
	<div class="row">
		<div class="col-md-push-3 col-sm-6">{widget('sec-instagram')}</div>
		</div>
	</div>
</div>
</a>
{if strip_tags($category.field_contactform) == 'ON'}
 <!--  contact_form.tpl -->
 <!-- contact-us -->
            <article id="feedback">
                <div class="page-section" data-background-color="#f9f9f9">
                    
                    <!-- форма связи -->
                    <div class="container">
                        <div class="col-sm-12">
                            <h2 class="text-center text-uppercase mb27 mt-xs60 mt-sm20">{echo siteinfo('siteinfo_transl-suborder')}</h2> </div>
                        <div class="col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1" style="margin-top:50px">
                        <!-- header.tpl -->
            {include_tpl('feedback/feedback')}
                    </div>
                  </div>
 			
                </div>
                <!-- .page-section -->
            </article>
{/if} 