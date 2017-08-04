var mysteep = 50;
var mysteep_cat_for_prod = 1;
var data_offset = 50;
function cats_create(count) {
    $('.action_name').text('Загрузка файла').show();
    $(".parse_yandex_xml__frame").addClass("loading");


    $(".parse_yandex_xml__frame").addClass("loading");
    $.post('/parse_brain/categories_brain/cats_create', {
            'cats': 1
        },
        function (data1) {
            $('.action_name').text('Обновление Категорий').show();
            answers = JSON.parse(data1);

            a = answers;

            if (a[1] == 'r') {
                showMessage(lang('Message'), a[0], a[1]);
            }
            if (a[1] == 'g') {

                showMessage(lang('Message'), a[0], a[1]);
            } else {
                alert('Не все товары созданы, запустите скрипт еще раз');
            }
            $.pjax({
                url: 'cat_prices',
                container: '#mainContent'
            });
        });

}
function prods_create(all_count, cats, prod_offset) {
    $(".parse_yandex_xml__frame").addClass("loading");
    showProcess('Подготовка данных', 1);
    if (prod_offset == 0) {
        $.post('/parse_brain/products/one_cat_count_prods', {
                'catsN': Number(cats),
                'prod_offset': Number(prod_offset)
            },

            function (data1) {
                // answer_count_pr = JSON.parse(data1);
                stop_process();
                // setTimeout(function () {
                if (Number(data1) > 0) {
                    recived_count_prods = Number(data1);
                    if (prod_offset > recived_count_prods) {
                        cats = cats + mysteep_cat_for_prod;
                        prod_offset = 0;

                        prods_create(all_count, cats, prod_offset)
                    } else {
                        start_parse_create(all_count, cats, prod_offset, recived_count_prods);
                    }
                }
                else {
                    $.pjax({
                        url: window.location.pathname,
                        container: '#mainContent'
                    });
                }

                // }, 180000);
            });
    } else {

        if (prod_offset > recived_count_prods) {
            cats = cats + mysteep_cat_for_prod;
            prod_offset = 0;

            prods_create(all_count, cats, prod_offset)
        } else {
            start_parse_create(all_count, cats, prod_offset, recived_count_prods);
        }

    }

}
function start_parse_create(all_count, cats, prod_offset, recived_count_prods) {
    // if (prod_offset > recived_count_prods) {
    //     cats = cats + mysteep_cat_for_prod;
    //     prod_offset = 0;
    //
    //     prods_create(all_count, cats, prod_offset)
    // }
    console.log(cats);
    console.log(all_count);

    $(".parse_yandex_xml__frame").addClass("loading");
    // if (Number(cats) + mysteep_cat_for_prod == '1') {
    //     showProcess('1', 1 / 25);
    // }
    if (Number(cats) / Number(all_count) < 1) {

        showProcess(1 + Number(cats) + ' / ' + Number(all_count), Number(cats) / Number(all_count), Number(all_count));
        numr = Number(prod_offset) + 50;
        if (numr > recived_count_prods) {
            numr = recived_count_prods;
            $(".parse_yandex_xml__frame").addClass("loading");
        }
        showProcess_prods(numr + ' /' + recived_count_prods, numr / recived_count_prods);
    } else {
        showProcess('Подготовка данных', 1);
    }
    $.post('/parse_brain/products/create', {
            'products': Number(cats),
            'prod_offset': Number(prod_offset)
        },

        function (data) {

            // if(Number(cats) + mysteep_cat_for_prod == '1'){
            //     stop_process();
            // }
            // if (Number(cats) / Number(all_count) < 1) {
            //
            //     showProcess(Number(cats) + ' / ' + Number(all_count), Number(cats) / Number(all_count));
            //     showProcess_prods(Number(prod_offset) + ' /' + recived_count_prods, Number(prod_offset) / recived_count_prods);
            // }
            $('.action_name').text('Обновление товаров %').show();
            if (Number(cats) >= Number(all_count)) {

                $.pjax({
                    url: window.location.pathname,
                    container: '#mainContent'
                });
            }
            else {
                prods_create(Number(all_count), Number(cats), Number(prod_offset + data_offset));
            }
        });
}
function showProcess(sucsess, sucsess2, all_count) {

    // $('#url_n, #refreshScript').hide();
    $('.progress_prod').show();
    // $('#runScript').text('Загрузка!');
    $('.bar_prod').text(sucsess);
    if (sucsess2 == 0) {
        sucsess2 = 1 / all_count;
    } else {
        sucsess2 = ((sucsess2 * all_count) + 1) / all_count
    }
    $('.bar_prod').css('width', sucsess2 * 100 + '%');


    $('.bar_prod').css('background-color', 'rgba(0, 137, 255, 0.88)');
    $('.bar_prod').css('text-align', 'center');
    $('.progress_prod').css('margin-top', '10px');
    $('.progress_prod').css('border-radius', '20');

}
function stop_process() {
    $('.progress_prod').hide();
}

function showProcess_prods(sucsess, sucsess2) {

    // $('#url_n, #refreshScript').hide();
    $('.progress_prods_cr').show();
    // $('#runScript').text('Загрузка!');
    $('.bar_prods_cr').text(sucsess);
    $('.bar_prods_cr').css('width', sucsess2 * 100 + '%');
    $('.bar_prods_cr').css('background-color', 'rgba(0, 137, 255, 0.88)');
    $('.bar_prods_cr').css('text-align', 'center');
    $('.progress_prods_cr').css('margin-top', '10px');
    $('.progress_prods_cr').css('border-radius', '20');

}
function stop_process_prods() {
    $('.progress_prods_cr').hide();
}


function addCategoryModalFtp() {
    if ($('#fast_add_formFtp').valid())
        $('#fast_add_formFtp').ajaxSubmit({
            success: function (responseText) {
                responseObj = JSON.parse(responseText);
                $('.modal').modal('hide');
                if (responseObj.success) {
                    // $('#iddCategory').html(responseObj.categories);
                    //$('#iddCategory').find('option:selected').removeAttr('selected');
                    //$('#iddCategory').trigger("chosen:updated");
                    $('select[name="CategoryIdFtp"]').html(responseObj.categories);
                    $('select[name="CategoryIdFtp"]').trigger("chosen:updated");
                    showMessage(lang('Message'), responseObj.message);
                }
                else
                    showMessage(lang('Error'), responseObj.message, 'r');
            }
        });
    return false;
}


function addCategoryModalOasis() {
    if ($('#fast_add_formOasis').valid())
        $('#fast_add_formOasis').ajaxSubmit({
            success: function (responseText) {
                responseObj = JSON.parse(responseText);
                $('.modal').modal('hide');
                if (responseObj.success) {
                    // $('#iddCategory').html(responseObj.categories);
                    //$('#iddCategory').find('option:selected').removeAttr('selected');
                    //$('#iddCategory').trigger("chosen:updated");
                    $('select[name="CategoryIdOasis"]').html(responseObj.categories);
                    $('select[name="CategoryIdOasis"]').trigger("chosen:updated");
                    showMessage(lang('Message'), responseObj.message);
                }
                else
                    showMessage(lang('Error'), responseObj.message, 'r');
            }
        });
    return false;
}

function addCategoryModalMirs() {
    if ($('#fast_add_formMirs').valid())
        $('#fast_add_formMirs').ajaxSubmit({
            success: function (responseText) {
                responseObj = JSON.parse(responseText);
                $('.modal').modal('hide');
                if (responseObj.success) {
                    // $('#iddCategory').html(responseObj.categories);
                    //$('#iddCategory').find('option:selected').removeAttr('selected');
                    //$('#iddCategory').trigger("chosen:updated");
                    $('select[name="CategoryIdMirs"]').html(responseObj.categories);
                    $('select[name="CategoryIdMirs"]').trigger("chosen:updated");
                    showMessage(lang('Message'), responseObj.message);
                }
                else
                    showMessage(lang('Error'), responseObj.message, 'r');
            }
        });
    return false;
}

function addCategoryModalFile() {
    if ($('#fast_add_formFile').valid())
        $('#fast_add_formFile').ajaxSubmit({
            success: function (responseText) {
                responseObj = JSON.parse(responseText);
                $('.modal').modal('hide');
                if (responseObj.success) {
                    // $('#iddCategory').html(responseObj.categories);
                    //$('#iddCategory').find('option:selected').removeAttr('selected');
                    //$('#iddCategory').trigger("chosen:updated");
                    $('select[name="CategoryIdFile"]').html(responseObj.categories);
                    $('select[name="CategoryIdFile"]').trigger("chosen:updated");
                    showMessage(lang('Message'), responseObj.message);
                }
                else
                    showMessage(lang('Error'), responseObj.message, 'r');
            }
        });
    return false;
}

