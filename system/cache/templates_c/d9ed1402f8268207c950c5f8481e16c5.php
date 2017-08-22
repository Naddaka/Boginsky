<!-- Шаблон showroom.tpl -->

<!-- header.tpl -->
<?php $this->include_tpl('header', '/var/www/admin/data/www/boginsky.naddaka.com/templates/brainwave'); ?>
<div id="showroom">
	<div class="container">
	<div class="row">
		<div class="col-sm-3">
			<div class="portfolio-filter">
				<button class="btn btn-default btn-xs active" data-filter="*">Все</button>
				<?php $sub_cats = get_sub_categories( $category['id'] ) ?>
		        <?php if(is_true_array($sub_cats)){ foreach ($sub_cats as $sub_cat){ ?>
		          <button class="btn btn-default btn-xs" data-filter=".<?php echo $sub_cat['url']; ?>"><?php echo $sub_cat['name']; ?></button>
		        <?php }} ?>
				<!-- <button class="btn btn-default btn-xs" data-filter=".branding">Тумбы под умывальник</button>
				<button class="btn btn-default btn-xs" data-filter=".design">Зеркала и зеркальные шкафы</button>
				<button class="btn btn-default btn-xs" data-filter=".photography">Шкафы и пеналы</button>
				<button class="btn btn-default btn-xs" data-filter=".photography">Комплекты</button> -->
			</div>
				<!-- <ul>
					<li>Все</li>
					<li>Тумбы под умывальник</li>
					<li>Зеркала и зеркальные шкафы</li>
					<li>Шкафы и пеналы</li>
					<li>Комплекты</li>
				</ul> -->
			</div>
			<div class="col-sm-9">
				<ul class="portfolio-list three-column list-unstyled">
					<li class="grid-sizer"></li>
					       <?php if(is_true_array($pages)){ foreach ($pages as $page){ ?>
        <?php $item = $CI->load->module('cfcm')->connect_fields($page, 'page')?>
        <?php $c = explode("/", rtrim( $page['cat_url'] , "/")) ?>

					<li class="portfolio-item ["<?php if($c[1]): ?><?php echo $c[1]; ?><?php else:?><?php echo $c[0]; ?><?php endif; ?>"] work-ajax-link">
						<a href="<?php echo site_url ( $page['full_url'] ); ?>" class="page-load" data-title="BrainWave | Creative сhair brand"> <img src="<?php echo $item['field_pagephoto']; ?>)v=1.0.0" alt="" width="400" height="320">
							<div class="info-container">
								<div class="title"><?php echo $page['title']; ?></div>
								<div class="category"><?php echo $page['prev_text']; ?></div>
							</div>
						</a>
					</li>
					<!-- .portfolio-item -->
				</ul>
			</div>
		</div>
	</div>
</div><?php $mabilis_ttl=1502800245; $mabilis_last_modified=1502715635; ///var/www/admin/data/www/boginsky.naddaka.com/templates/brainwave/showroom.tpl ?>