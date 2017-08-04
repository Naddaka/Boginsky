function ajaxLoadChildCategoryBrain(el, id) {

    var container = $(el).closest('.row-category');

    if (container.next().attr('class') != 'frame_level sortable ui-sortable')
        $.post('/admin/components/run/parse_brain/ajax_load_parent', {id: id}, function (data) {
            $(data).insertAfter(container);
//            expandCategories($(data).find('.expandButton'))
//             initNiceCheck();
//             share_alt_init();
//             sortInit();

        });
    $('.control-group').on('mousedown', function (e) {
        e.stopPropagation()
    });

}

$('.cat_change_parse').live('click', function () {
    var id = $(this).attr('data-id');
    $.ajax({
        type: "post",
        url: '/admin/components/run/parse_brain/cat_change_parse',
        data: 'id=' + id,
        success: function (data) {
            $('.notifications').append(data);
        }
    });
});


$(document).on('mousedown', '.control-group', function (e) {
    e.stopPropagation();
});

// $(function(){
//     document.addEventListener('mousedown',function (event) {
//         console.log(event.target);
//         if(event.target.closest('.control-group')){
//             event.stopPropagation();
//
//         }
//     },true)
// });

$(document).on('change', '[data-pricetype-select]', function (event) {
    var categoryId = $(this).attr('data-category-id');
    var price_type = $(this).val();
    $.ajax({
        type: "post",
        url: '/admin/components/run/parse_brain/cat_set_price_type',
        data: {
            'cat_id': categoryId,
            'price_type': price_type
        },
        success: function (data) {
            $('.notifications').append(data);
        }
    });

});

$(document).on('change', '[data-cat_brand-select]', function (event) {
    var categoryId = $(this).attr('data-category-id');
    var cat_brand_id = $(this).val();
    $.ajax({
        type: "post",
        url: '/admin/components/run/parse_brain/set_cat_brand_id',
        data: {
            'cat_id': categoryId,
            'cat_brand_id': cat_brand_id
        },
        success: function (data) {
            $('.notifications').append(data);
        }
    });

});

$('button.refresh_percent').live('click', function () {

    var btn = $(this);
    var cat = btn.attr('cat-id');
    var catId = {};
    var percent = btn.parent().find('input').val();

    catId['percent'] = percent;

    if (typeof cat !== 'undefined' && cat !== false)
        catId['cat'] = cat;

    $.ajax({
        type: 'POST',
        data: catId,
        url: base_url + 'admin/components/run/parse_brain/set_cat_percent/' + btn.attr('data-id'),
        success: function (data) {
            $('.notifications').append(data);
        }
    });

    if (!price) {
        btn.parent().find('input').val(0);
    }

});
