<!-- FOOTER.tpl --> 
<footer>
    {$page = get_page(3)}
{$page = $CI->load->module('cfcm')->connect_fields($page, 'page')}
    
    <div class="site-footer display-h pos-relative{if strip_tags($page.field_textcolor) == 'Black'} text-black{/if}{if strip_tags($page.field_textcolor) == 'White'} text-white{/if}{if strip_tags($page.field_textcolor) == 'Gray'} text-gray{/if}" style="{if $page.field_bgcolor}background-color:{$page.field_bgcolor};{/if}{if strip_tags($page.field_textcolor) == 'Color 1'}color:{echo siteinfo('siteinfo_color')}{/if}{if strip_tags($page.field_textcolor) == 'Color 2'}color:{echo siteinfo('siteinfo_color2')}{/if}">
            <div class="copyright">
                <div class="company text-center text-uppercase">{$page.prev_text}</div>
                <div class="text-center">{$page.full_text}</div>
            </div>
        </div>
        
   </footer>
<!--/site-footer -->