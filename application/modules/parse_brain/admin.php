<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

use CMSFactory\assetManager;
use CMSFactory\ModuleSettings;

/**
 * Image CMS
 * Sample Module Admin
 */
class Admin extends ShopAdminController
{

    private $uploadDir   = './uploads/';

    private $csvFileName = 'comments.csv';

    public $delimiter   = ';';

    /**
     * Attributes
     *
     * @var array
     */
    public $attributes = '';

    /**
     * Possible attributes
     *
     * @var array
     */
    public $possibleAttributes = [];

    /**
     * CSV enclosure
     *
     * @var string
     */
    public $enclosure = '"';

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('parse_brain');
    }

    public function index() {
        $categiries = ShopCore::app()->SCategoryTree->getTree_();
        assetManager::create()
                                ->setData('settings', ModuleSettings::ofModule('parse_yandex_xml')->get())
                                ->setData('categories', $categiries)
                                ->registerScript('script', true)
                                ->registerStyle('style', true)
                                ->renderAdmin('main');
    }

    public function settings() {

        $settings = ModuleSettings::ofModule('parse_brain')->get();
        //        dd($settings);
        assetManager::create()->setData('settings', $settings)->renderAdmin('settings');
    }

    /**
     * @return array
     */
    public function cat_prices() {
        $tree = SCategoryQuery::create()->getTree(
            0,
            SCategoryQuery::create()
            ->joinWithI18n(MY_Controller::defaultLocale())
        );

        $settings            = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
        $settings_price_type = ModuleSettings::ofModule('parse_brain')->get()['price_type'];
        $cat_brand_rrc       = ModuleSettings::ofModule('parse_brain')->get()['cat_brand'];
        $cats_percents       = ModuleSettings::ofModule('parse_brain')->get()['cats_percents'];
        //        dump($settings_price_type);
        //        dump($cat_brand_rrc);
        //        dump($cats_percents);

        assetManager::create()
                                ->setData('links', ShopCore::app()->SCategoryTree->getTree())
                                ->setData('settings', $settings)
                                ->setData('settings_price_type', $settings_price_type)//            ->setData('cats_percents', $cats_percents)
                                ->setData('tree', $tree->getCollection())
                                ->setData('htmlTree', $this->printCategoryTree($tree))
                                ->renderAdmin('categories');

    }

    private function printCategoryTree($tree = false, $ajax = false) {

        $output = '';
        if (!$ajax) {
            $output .= '<div class="" data-url="/admin/components/run/shop/categories/save_positions">';
        } else {
            $output .= '<div class="frame_level sortable" style="display: block" data-url="/admin/components/run/shop/categories/save_positions">';
        }

        foreach ($tree as $c) {
            $output .= $this->printCategory($c);
        }

        $output .= '</div>';

        return $output;

    }

    /**
     * @param SCategory $category
     *
     * @return string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function printCategory($category) {

        $settings            = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
        $settings_price_type = ModuleSettings::ofModule('parse_brain')->get()['price_type'];

        $cat_brand_rrc = ModuleSettings::ofModule('parse_brain')->get()['cat_brand'];
        $cats_percents = ModuleSettings::ofModule('parse_brain')->get()['cats_percents'];

        $all_brands = SBrandsI18nQuery::create()->orderByName(\Doctrine\Common\Collections\Criteria::ASC)->find();

        $catToDisplay = new stdClass();

        $name                  = $category->getName() ?: lang('Тo translation', 'admin') . ' (' . MY_Controller::getCurrentLocale() . ')';
        $catToDisplay->id      = $category->getId();
        $catToDisplay->parent  = ($category->getSCategory() != null) ? $category->getSCategory()->getName() : '-';
        $catToDisplay->name    = $name;
        $catToDisplay->isBrain = (int) $category->getIsBrain();
        $catToDisplay->url     = $category->getRouteUrl();
        $catToDisplay->active  = $category->getActive();

        $catToDisplay->to_parse   = array_key_exists($category->getId(), $settings) ? true : false;
        $catToDisplay->price_type = array_key_exists($category->getId(), $settings_price_type) ? $settings_price_type[$category->getId()] : '0';

        $level               = substr_count($catToDisplay->url, '/') + 1;
        $catToDisplay->level = $level;

        $catToDisplay->hasChilds     = (bool) $category->hasSubItems();
        $catToDisplay->myProdCnt     = (int) $this->prod_count[$category->getId()];
        $catToDisplay->show_in_menu  = (bool) $category->getShowInMenu();
        $catToDisplay->cat_percent   = array_key_exists($category->getId(), $cats_percents) ? $cats_percents[$category->getId()] : '0';
        $catToDisplay->cat_brand_rrc = array_key_exists($category->getId(), $cat_brand_rrc) ? $cat_brand_rrc[$category->getId()] : '0';

        $output = '<div>';

        //        $this->template->assign('cat', $catToDisplay);
        $output .= assetManager::create()//            ->setData('links', ShopCore::app()->SCategoryTree->getTree())
                                           ->registerScript('script')
                                           ->setData('cat', $catToDisplay)
                                           ->setData('all_brands', $all_brands)
                                           ->fetchAdminTemplate('brain_listItem');

        $output .= '</div>';

        unset($catToDisplay);

        return $output;
    }

    public function ajax_load_parent() {

        $id     = (int) $this->input->post('id');
        $locale = MY_Controller::getCurrentLocale();

        $subCats = SCategoryQuery::create()->getTree($id, SCategoryQuery::create()->joinWithI18n($locale));

        echo $this->printCategoryTree($subCats, true);
    }

    public function save_categiries_settings() {

        if ($this->input->post()) {
            if (ModuleSettings::ofModule('parse_brain')                ->set('categories_to_parce_prc', $this->input->post('categories_to_parce_prc'))
            ) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    public function cat_change_parse() {

        if ($this->input->post()) {

            $settings = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
            if (array_key_exists($this->input->post('id'), $settings)) {
                unset($settings[$this->input->post('id')]);
            } else {
                $settings[$this->input->post('id')] = $this->input->post('id');
            }

            if (ModuleSettings::ofModule('parse_brain')->set('categories_to_parce', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    public function cat_set_price_type() {

        if ($this->input->post('price_type')) {

            $settings = ModuleSettings::ofModule('parse_brain')->get()['price_type'];
            if (array_key_exists($this->input->post('cat_id'), $settings)) {
                unset($settings[$this->input->post('cat_id')]);
                $settings[$this->input->post('cat_id')] = $this->input->post('price_type');
            } else {
                $settings[$this->input->post('cat_id')] = $this->input->post('price_type');
            }

            if (ModuleSettings::ofModule('parse_brain')->set('price_type', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    public function set_cat_brand_id() {
        if ($this->input->post('cat_id')) {
            $settings = ModuleSettings::ofModule('parse_brain')->get()['cat_brand'];
            if (array_key_exists($this->input->post('cat_id'), $settings)) {
                unset($settings[$this->input->post('cat_id')]);
                if ($this->input->post('cat_brand_id') != '0') {
                    $settings[$this->input->post('cat_id')] = $this->input->post('cat_brand_id');
                }
            } else {
                $settings[$this->input->post('cat_id')] = $this->input->post('cat_brand_id');
            }

            if (ModuleSettings::ofModule('parse_brain')->set('cat_brand', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    /**
     * @return array
     */
    public function set_cat_percent() {

        if ($this->input->post('cat')) {
            $settings = ModuleSettings::ofModule('parse_brain')->get()['cats_percents'];
            if (array_key_exists($this->input->post('cat'), $settings)) {
                unset($settings[$this->input->post('cat')]);
                if ($this->input->post('percent') != '0.') {
                    $settings[$this->input->post('cat')] = $this->input->post('percent');
                }
            } else {
                $settings[$this->input->post('cat')] = $this->input->post('percent');
            }

            if (ModuleSettings::ofModule('parse_brain')->set('cats_percents', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    public function save() {

        if ($this->input->post()) {
            if (ModuleSettings::ofModule('parse_brain')->set($this->input->post('settings'))) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();
        }
    }

    /**
     * @return array
     */
    public function prices_ranges() {

        $settings = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];
        //dump($settings);
        assetManager::create()
                                ->setData('prises_range', $settings)
                                ->registerScript('prises_range')
                                ->renderAdmin('price_ranges');

    }

    public function addDiapazon() {
        $settings      = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];
        $variable      = $this->input->post('variable');
        $variableValue = $this->input->post('variableValue');
        $variableRange = $this->input->post('variableRange');

        if ($this->input->post('diapazon_id')) {
            if (array_key_exists($this->input->post('diapazon_id'), $settings)) {
                unset($settings[$this->input->post('diapazon_id')]);
                $settings[$this->input->post('diapazon_id')] = [
                                                                'begin'   => $variable,
                                                                'end'     => $variableValue,
                                                                'percent' => $variableRange,
                                                               ];
            } else {
                $settings[$this->input->post('diapazon_id')] = [
                                                                'begin'   => $variable,
                                                                'end'     => $variableValue,
                                                                'percent' => $variableRange,
                                                               ];
            }

            if (ModuleSettings::ofModule('parse_brain')->set('prises_ranges', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();

            return true;
        }

        return false;
    }

    public function deleteRangeVariable() {
        $settings = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];

        if ($this->input->post('diapazon_id')) {
            if (array_key_exists($this->input->post('diapazon_id'), $settings)) {
                unset($settings[$this->input->post('diapazon_id')]);

            }

            if (ModuleSettings::ofModule('parse_brain')->set('prises_ranges', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();

            return true;
        }

        return false;
    }

    public function updateOneRange() {
        $settings      = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];
        $variable      = $this->input->post('variable');
        $variableValue = $this->input->post('variableValue');
        $variableRange = $this->input->post('rangeValue');

        if ($this->input->post('diapazon_id')) {
            if (array_key_exists($this->input->post('diapazon_id'), $settings)) {
                unset($settings[$this->input->post('diapazon_id')]);
                $settings[$this->input->post('diapazon_id')] = [
                                                                'begin'   => $variable,
                                                                'end'     => $variableValue,
                                                                'percent' => $variableRange,
                                                               ];
            } else {
                $settings[$this->input->post('diapazon_id')] = [
                                                                'begin'   => $variable,
                                                                'end'     => $variableValue,
                                                                'percent' => $variableRange,
                                                               ];
            }

            if (ModuleSettings::ofModule('parse_brain')->set('prises_ranges', $settings)) {
                showMessage(lang('Settings saved', 'parse_brain'), lang('Message', 'parse_brain'));
            }
            $this->cache->delete_all();

            return true;
        }

        return false;
    }

    /*---------------------------------------------------------
    Блок управления загрузкой
    */

    /**
     * @return array
     */
    public function getParsing_types() {

        $settings = ModuleSettings::ofModule('parse_brain')->get();
        //        dump($settings);
        assetManager::create()
                                ->setData('settings', $settings)
                                ->registerScript('parsing_start')
                                ->renderAdmin('parsing_start');

    }

}