<div id="client-comments" class="col-sm-12 owl-carousel default-carousel animation-fade text-gray">
  {foreach $recent_news as $item}
  <div class="carousel-item">
    <div class="col-sm-6">
      <div class="client-comment left">{$item.prev_text}</div>
    </div>
    <div class="col-sm-6">
      <div class="client-comment right">{$item.full_text}</div>
    </div>
  </div>
  {/foreach}
</div>