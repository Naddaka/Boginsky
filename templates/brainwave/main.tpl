<!--Общий шаблон HEAD и BODY -->
<!DOCTYPE html>
<html lang="{current_language()}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$site_title}</title>
{include_tpl('favicon')}
<meta name="theme-color" content="#ffffff">
<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
{include_tpl('loader')}
<!-- Fonts -->
<link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
<!-- Bootstrap -->
<link href="{$THEME}css/bootstrap.css?v3.3.5" rel="stylesheet" type="text/css" title="">
<!-- Bootstrap -->
<link href="{$THEME}css/animate.css?v1.0.0" rel="stylesheet" type="text/css" title="">
<!-- Font Awesome -->
<link href="{$THEME}css/font-awesome.css?v4.4.0" rel="stylesheet" type="text/css" title="">
<!-- Font Et-line -->
<link href="{$THEME}css/et-line.css?v1.0.0" rel="stylesheet" type="text/css" title="">
<!-- Font Simple line -->
<link href="{$THEME}css/simple-line-icons.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<!-- Font Elegant -->
<link href="{$THEME}css/elegant-font.css?v1.0.0" rel="stylesheet" type="text/css" title="">
<!-- Owl carousel default style -->
<link href="{$THEME}css/owl.carousel.css?v2.0.0" rel="stylesheet" type="text/css" title="">
<!-- Magnific Popup default style -->
<link href="{$THEME}css/magnific-popup.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<!-- Main css file -->
<link href="{$THEME}css/style.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<link href="{$THEME}css/naddaka.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<link href="{$THEME}css/landing.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<link href="{$THEME}css/adaptive.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
<link href="{$THEME}css/project.css?v=1.0.0" rel="stylesheet" type="text/css" title="">
{include_tpl('styles')}
</head>
<body class="load display-h">
    <div class="wrapper">
<!-- Подключение меню (menu.tpl) --> 
        {include_tpl('menu')} 

<!-- Подключение шаблонов категорий (по умолчанию category.tpl) --> 
        {$content}     
        
<!--Подключение пред-подвала (prefooter.tpl) --> 
    {include_tpl('prefooter')}
    </div>
    <div class="page-loader">
        <div class="preloader loading"> <span class="slice"></span> <span class="slice"></span> <span class="slice"></span> <span class="slice"></span> <span class="slice"></span> <span class="slice"></span> </div>
    </div>
    <!--Подключение подвала (footer.tpl) --> 
    {include_tpl('footer')} 
    <!-- jQuery -->
    <script src="{$THEME}js/jquery.min.js?v=1.11.2"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <!-- Map library -->
    <script src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <!-- Zoom library -->
    <script src="{$THEME}js/jquery.elevatezoom.js?v=1.0.0"></script>
    <!-- Scroll library -->
    <script src="{$THEME}js/wow.js?v=1.0.0"></script>
    <script src="{$THEME}js/jquery.inview.js?v=1.0.0"></script>
    <!-- OWL Carousel library -->
    <script src="{$THEME}js/owl.carousel.js?v=2.0.0"></script>
    <!-- OWL Slider library -->
    <script src="{$THEME}js/jquery.superslides.js?v=1.0.0"></script>
    <!-- Magnific Popup library -->
    <script src="{$THEME}js/jquery.magnific-popup.js?v=1.0.0"></script>
    <!-- Isotope library -->
    <script src="{$THEME}js/imagesloaded.pkgd.js?v=2.2.0"></script>
    <!-- Isotope library -->
    <script src="{$THEME}js/isotope.js?v=2.2.0"></script>
    <!-- SmoothScroll library -->
    <script src="{$THEME}js/SmoothScroll.js?v=1.4.0"></script>
    <!-- Text rotator library -->
    <script src="{$THEME}js/morphext.js?v=2.4.4"></script>
    <!-- Video bg library -->
    <script src="{$THEME}js/jquery.backgroundvideo.js?v=1.0.0"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="{$THEME}js/bootstrap.js?v=3.3.5"></script>
    <!-- Pjax Library -->
    <script src="{$THEME}js/jquery.pjax.js?v=1.0.0"></script>
    <!-- Main script -->
    <script src="{$THEME}js/script.js?v=1.0.0"></script>
    {$category = get_category(1)}
{$item = $CI->load->module('cfcm')->connect_fields($category, 'category')}
    {literal}
    <script>
        $('#slides').superslides({
            play: {/literal}{strip_tags($category.field_slidedelay)}{literal},
            animation: '{/literal}{strip_tags($category.field_slideAnimation)}{literal}',
        });
        $( '.content .display-tc' ).css({width: $(window).width(), height: $(window).height()});
    </script>
    <script>
(function(){
var a = document.querySelector('#aside1'), b = null, P = 100;  // если ноль заменить на число, то блок будет прилипать до того, как верхний край окна браузера дойдёт до верхнего края элемента. Может быть отрицательным числом
window.addEventListener('scroll', Ascroll, false);
document.body.addEventListener('scroll', Ascroll, false);
function Ascroll() {
  if (b == null) {
    var Sa = getComputedStyle(a, ''), s = '';
    for (var i = 0; i < Sa.length; i++) {
      if (Sa[i].indexOf('overflow') == 0 || Sa[i].indexOf('padding') == 0 || Sa[i].indexOf('border') == 0 || Sa[i].indexOf('outline') == 0 || Sa[i].indexOf('box-shadow') == 0 || Sa[i].indexOf('background') == 0) {
        s += Sa[i] + ': ' +Sa.getPropertyValue(Sa[i]) + '; '
      }
    }
    b = document.createElement('div');
    b.style.cssText = s + ' box-sizing: border-box; width: ' + a.offsetWidth + 'px;';
    a.insertBefore(b, a.firstChild);
    var l = a.childNodes.length;
    for (var i = 1; i < l; i++) {
      b.appendChild(a.childNodes[1]);
    }
    a.style.height = b.getBoundingClientRect().height + 'px';
    a.style.padding = '0 0 50px 0';
    a.style.border = '0';
  }
  var Ra = a.getBoundingClientRect(),
      R = Math.round(Ra.top + b.getBoundingClientRect().height - document.querySelector('article').getBoundingClientRect().top + 100);  // селектор блока, при достижении верхнего края которого нужно открепить прилипающий элемент (если ноль заменить на число, то блок будет отлипать до того, как нижний край элемента дойдёт до футера
  if ((Ra.top - P) <= 0) {
    if ((Ra.top - P) <= R) {
      b.className = 'stop';
      b.style.top = - R +'px';
    } else {
      b.className = 'sticky2';
      b.style.top = P + 'px';
    }
  } else {
    b.className = '';
    b.style.top = '';
  }
  window.addEventListener('resize', function() {
    a.children[0].style.width = getComputedStyle(a, '').width
  }, false);
}
})()
</script>
    {/literal}
</body>
</html>