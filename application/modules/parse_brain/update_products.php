<?php

use CMSFactory\ModuleSettings;
use parse_brain\classes\ParentBrain;
use Products\ProductApi;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Update_products extends MY_Controller
{

    private $i             = 0;

    private $time;

    private $arr           = [];

    private $result;

    private $xml_prop;

    private $result_create = [];

    private $countProp     = 200;

    private $uploadDir     = './uploads/';

    private $csvFileName   = 'comments.csv';

    //    public $auth_session;

    public function __construct() {

        parent::__construct();

        $lang = new MY_Lang();
        $lang->load('parse_brain');
        $this->load->helper('translit');
        $this->time         = time();
        $this->settings     = ModuleSettings::ofModule('parse_brain')->get();
        $this->auth_session = $this->get_auth_brain();

        // совйства из брейна иногда битіе (без имени) , напр  http://prnt.sc/emu06y

        $this->existing_categories = $this->get_exist_category();

        //       $a = [ "bonus" => 0,
        //             "stocks" => [
        //                 0 => 1,
        //                 1 => 19,
        //                2 => 121,
        //                3 => 1722,
        //                4 => 245,
        //                ]];
        //       dump($a);
        //dump(!empty($a['stocks']) ? max($a['stocks']) : 0);
        //        $cat_price_types = ModuleSettings::ofModule('parse_brain')->get()['price_type'];
        //        $prises_ranges = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];
        //        $cat_brand_rrc = ModuleSettings::ofModule('parse_brain')->get()['cat_brand'];
        //        $cats_percents = ModuleSettings::ofModule('parse_brain')->get()['cats_percents'];
        //        dump($cat_price_types,$prises_ranges,$cat_brand_rrc,$cats_percents);
        ////
        //        $pr_data_brain = (array)\parse_brain\classes\ParentBrain::getInstance()->curl_to_send(null, 'http://api.brain.com.ua/product/product_code/U0041991/' . $this->auth_session . '?', 'get');
        //        dd($pr_data_brain);
    }

    /**
     * @return CI_User_agent
     */
    public function get_auth_brain() {
        return ParentBrain::getInstance()->curl_to_send(
            [
             'login'    => $this->settings['login'],
             'password' => md5($this->settings['password']),
            ],
            'http://api.brain.com.ua/auth',
            'post'
        );
    }

    public function get_exist_category() {
        $pr_cats = $this->db->select('id,external_id')->get('shop_category')->result_array();
        foreach ($pr_cats as $cat_key => $cat) {
            $exist_cats[$cat['external_id']] = $cat['id'];
            unset($cat_key, $cat);
        }

        return $exist_cats;
    }

    public function start_update() {
        $exist_cats                   = $this->existing_categories;
        $cats_in_shop                 = array_flip($exist_cats);
        $module_settings_to_parse_cat = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];

        $aa = 0;

        /*Метод для получения списка идентификаторов товаров, которые были изменены */
        $prods_modified = ParentBrain::getInstance()
                                                          ->curl_to_send(null, 'http://api.brain.com.ua/modified_products/new/' . $this->auth_session . '?limit=10000', 'get');

        $prods_modified_ar = (array) $prods_modified;
        unset($prods_modified);

        foreach ($prods_modified_ar['productIDs'] as $mod_pr_key => $mod_pr_val) {
            $prods_modified_array[$mod_pr_val] = $mod_pr_val;
            unset($mod_pr_key, $mod_pr_val);
        }

        $modified_prods = $this->rpod_full_data($prods_modified_array);

        if (count($modified_prods) > 0) {
            $this->products_create($modified_prods);
        } else {
            return false;
        }

    }

    public function rpod_full_data($products) {

        $some_count_prop = 0;
        foreach ($products as $key_pr_id => $product) {

            $some_count_prop++;

            $pr_data_brain = (array) ParentBrain::getInstance()
                                               ->curl_to_send(null, 'http://api.brain.com.ua/product/productID/' . $product . '/' . $this->auth_session . '?', 'get');

            $pr_data[$product] = ['0' => $pr_data_brain];
            unset($key_pr_id, $product);
        }
        unset($products);

        return $pr_data;
    }

    public function products_create($prods) {
        /*
        в схеме пропела добавить для варианта
        <column name="retail_price_uah" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="recommendable_price" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="product_code_brain"  type="varchar" size="255" required="false"/>
        * */

        //        $pr_cats = $this->db->select('id,external_id')
        //            ->get('shop_category')->result_array();
        //        foreach ($pr_cats as $cat_key => $cat) {
        //            $exist_cats[$cat['external_id']] = $cat['id'];
        //            unset($cat_key, $cat);
        //        }

        $exist_cats   = $this->existing_categories;
        $cats_in_shop = array_flip($exist_cats);

        $pr_brands = $this->db->select('id, external_id')->get('shop_brands')->result_array();
        foreach ($pr_brands as $br_key => $brand) {
            $exist_brands[$brand['external_id']] = $brand['id'];
            unset($br_key, $brand);
        }

        $module_settings_to_parse_cat = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];

        $this->process_prods_one_cat($prods, $exist_prods_ids, $exist_brands, $exist_cats);

    }

    /**
     * @param $products_cat
     * @param $exist_prods_ids
     * @param $exist_brands
     * @param $exist_cats
     *
     * @return CI_User_agent
     */
    public function process_prods_one_cat($products_cat, $exist_prods_ids, $exist_brands, $exist_cats) {
        /*
        Фикс пустых значений свойств для товара после загрузки товаров
        */
        $this->db->query('DELETE FROM  shop_product_properties_data WHERE  value_id IS NULL');

        /*
        Фикс пустых значений свойств для товара после загрузки товаров
        */
        $locale_shop = \MY_Controller::getDefaultLanguage()['identif'];
        $def_cur     = SCurrenciesQuery::create()->findByMain('1')->getFirst()->id;

        $cat_price_types = ModuleSettings::ofModule('parse_brain')->get()['price_type'];
        $prises_ranges   = ModuleSettings::ofModule('parse_brain')->get()['prises_ranges'];
        $cat_brand_rrc   = ModuleSettings::ofModule('parse_brain')->get()['cat_brand'];
        $cats_percents   = ModuleSettings::ofModule('parse_brain')->get()['cats_percents'];

        $prod_exist_ids = $this->db->select('id, external_id')->get('shop_products')->result_array();

        foreach ($prod_exist_ids as $prod_key => $prod) {
            $exist_prods_ids[$prod['external_id']] = $prod['id'];
            unset($prod_key, $prod);
        }
        unset($prod_vars_count);

        $prod_vars_count              = $this->db->query('SELECT id FROM shop_product_variants ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;
        $count_for_ext_prods          = 0;
        $count_for_new_prod           = 0;
        $my_some_counter              = 0;
        $module_settings_to_parse_cat = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
        foreach ($products_cat as $pr_key => $product_obj) {
            $my_some_counter++;

            $product = $product_obj['0'];
            unset($product_obj);
            /*Создание продуктов при запросе на получения обновленных товаров*/

            dump(!array_key_exists($product['productID'], $exist_prods_ids));
            if (!array_key_exists($product['productID'], $exist_prods_ids)) {
                //                $pr_cat_id = key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1;
                //                if(!key_exists($pr_cat_id, $module_settings_to_parse_cat)){
                //                    continue;
                //                }
                //                $count_for_new_prod++;
                //
                //                $prod_vars_count++;
                //
                //                $pic_data = file_get_contents($product['large_image']);
                //                $picture = $product['id'] . pathinfo($product['large_image'], PATHINFO_BASENAME);
                //                file_put_contents('./uploads/shop/products/origin/' . $product['id'] . pathinfo($product['large_image'], PATHINFO_BASENAME), $pic_data);
                //
                //                $data_new_prod[$prod_vars_count] = [
                //                    'id' => $prod_vars_count,
                //                    'active' => 1,
                //                    'hot' => $product['is_new'] != '0' ? 1 : 0,
                //                    'archive' => $product['is_archive'] != '0' ? 1 : 0,
                //                    'external_id' => $product['productID'],
                //                    'category_id' => key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,//$pr_cat && $pr_cat !=null?$pr_cat:1,
                //                    'brand_id' => key_exists($product['vendorID'], $exist_brands) ? $exist_brands[$product['vendorID']] : 0,//$pr_brand && $pr_brand !=null?$pr_brand:null,//$product['vendorID']
                //                    'enable_comments' => 1,
                //                    'created' => time(),
                //                    'updated' => time(),
                //                    'is_brain' => 1
                //                ];
                //                $data_new_prod_for_urls[$prod_vars_count] = [
                //                    'id' => $prod_vars_count,
                //                    'name' => $product['name'],
                //                    'category_id' => key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                //                ];
                //                $data_new_prod_cats[$prod_vars_count] = [
                //                    'product_id' => $prod_vars_count,
                //                    'category_id' => key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                //                ];
                //                $data_new_prod_i18[] = [
                //                    'id' => $prod_vars_count,
                //                    'name' => $product['name'],
                //                    'locale' => 'ru',
                //                    'full_description' => $product['brief_description'],
                //                    'short_description' => $product['brief_description'],
                //
                //                ];
                //
                //
                //
                //                $pr_price = $cat_price_types[$pr_cat_id];
                //                if ($pr_price == 'rrc' && $pr_price != null) {
                //                    $price_in_main = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                //                    $price = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                //                } elseif ($pr_price == 'retail_price_uah' && $pr_price != null) {
                //                    $price_in_main = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0;
                //                    $price = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0;
                //                } elseif ($pr_price == 'add_price' || $pr_price == null) {
                //                    if (!$product['recommendable_price'] || $product['recommendable_price'] == '0') {
                //                        foreach ($prises_ranges as $key_r => $val_r) {
                //                            if ($val_r['begin'] < $product['retail_price_uah'] && $val_r['end'] > $product['retail_price_uah']) {
                //                                $price_in_main = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + $val_r['percent'] / 100) : 0;
                //                                $price = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + $val_r['percent'] / 100) : 0;
                //                            }
                //                            unset($key_r, $val_r);
                //                        }
                //                    }
                //
                //                }
                //                $price_in_main = $price_in_main != null ? (double)$price_in_main : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);
                //                $price = $price != null ? (double)$price : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);
                //
                //                $data_new_var[] = [
                //                    'id' => $prod_vars_count,
                //                    'product_id' => $prod_vars_count,
                //                    'price_in_main' => str_replace(",", ".", $price_in_main),
                //                    'price' => str_replace(",", ".", $price),
                //                    'recommendable_price' => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0,
                //                    'retail_price_uah' => $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0,
                //                    'currency' => $def_cur,//$codeAndId[$product['currencyId']],
                //                    'number' => $product['articul'],
                //                    'product_code_brain' => $product['product_code'],
                //                    'stock' => !empty($product['available']) ? max((array)$product['available']) : 0,
                //                    'external_id' => $product['productID'],
                //                    'mainImage' => $picture,
                //                    'is_brain' => 1
                //                ];
                //
                //
                //                $data_new_var_i18[] = [
                //                    'id' => $prod_vars_count,
                //                    'name' => $product['name'],
                //                    'locale' => 'ru',
                //                ];
                //
                //                $cat_all_rpod_full_data[$prod_vars_count] = [
                //                    'product_code_brain' => $product['product_code'],
                //                    'category_id' => key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                //                    'product'=>$product
                //                ];
                //
                //                $this->result['products'][$prod_vars_count] = $prod_vars_count;
                //
            } else {// Обновления продуктов

                $count_for_ext_prods++;
                $data_upd_prod[] = [
                                    'id'          => $exist_prods_ids[$product['productID']],
                                    'hot'         => $product['is_new'] != '0' ? 1 : 0,
                                    'archive'     => $product['is_archive'] != '0' ? 1 : 0,
                                    'external_id' => $product['productID'],
                                    'updated'     => time(),
                                   ];

                $data_upd_prod_i18n[] = [
                                         'id'                => $exist_prods_ids[$product['productID']],
                                         'short_description' => $product['brief_description'],
                    //                    'full_description' => $product['description'],
                                        ];

                $pr_cat_id = array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1;
                $pr_price  = $cat_price_types[$pr_cat_id];

                if ($pr_price == 'rrc' && $pr_price != null) {
                    $price_in_main = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                    $price         = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                } elseif ($pr_price == 'retail_price_uah' && $pr_price != null) {
                    $price_in_main = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0;
                    $price         = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0;
                } elseif ($pr_price == 'add_price' || $pr_price == null) {
                    if (!$product['recommendable_price'] || $product['recommendable_price'] == '0') {
                        foreach ($prises_ranges as $key_r => $val_r) {
                            if ($val_r['begin'] < $product['retail_price_uah'] && $val_r['end'] > $product['retail_price_uah']) {
                                $price_in_main = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + $val_r['percent'] / 100) : 0;
                                $price         = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + $val_r['percent'] / 100) : 0;
                            }
                            unset($key_r, $val_r);
                        }
                    }

                }

                if (array_key_exists($pr_cat_id, $cats_percents)) {
                    $price_in_main = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + ($cats_percents[$pr_cat_id] / 100)) : 0;
                    $price         = $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] * (1 + ($cats_percents[$pr_cat_id] / 100)) : 0;
                }

                if (array_key_exists($product['categoryID'], $cat_brand_rrc) && $cat_brand_rrc[$product['categoryID']] == $pr_brand_id) {
                    $price_in_main = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                    $price         = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                }

                //                $price_in_main = $price_in_main != null ? (double)$price_in_main : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);
                //                $price = $price != null ? (double)$price : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);

                $price_in_main = $price_in_main != null ? (double) $price_in_main : $product['retail_price_uah'];
                $price         = $price_in_main;

                $data_upd_var[] = [
                                   'id'                  => $exist_prods_ids[$product['productID']],
                                   'price_in_main'       => str_replace(',', '.', $price_in_main),
                                   'price'               => str_replace(',', '.', $price),
                                   'recommendable_price' => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0,
                                   'retail_price_uah'    => $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0,
                                   'currency'            => $def_cur,
                    //$codeAndId[$product['currencyId']],
                                   'number'              => $product['articul'],
                                   'product_code_brain'  => $product['product_code'],
                    //                    'stock' => !empty($product['available']) ? max((array)json_decode($product['available'])) : 0,
                                   'stock'               => !empty($product['stocks']) ? max($product['stocks']) : 0,
                    //                        'external_id' => $product['productID'],
                    //                        'mainImage' => $picture1,
                                  ];

            }

            unset($pr_key, $product, $price_in_main, $price);
        }
        //        if ($count_for_new_prod > 0) {
        //
        //            $this->db->insert_batch('shop_products', $data_new_prod);
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_products');
        //                dd($this->db->_error_message());
        //            }
        //            $this->db->insert_batch('shop_products_i18n', $data_new_prod_i18);
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_products_i18n');
        //                dd($this->db->_error_message());
        //            }
        //            $this->db->insert_batch('shop_product_variants', $data_new_var);
        ////            dd($this->db->last_query());
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_product_variants');
        //                dd($this->db->_error_message());
        //            }
        //            $this->db->insert_batch('shop_product_variants_i18n', $data_new_var_i18);
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_product_variants_i18n');
        //                dd($this->db->_error_message());
        //            }
        //            $this->db->insert_batch('shop_product_categories', $data_new_prod_cats);
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_product_categories');
        //                dd($this->db->_error_message());
        //            }
        //
        //
        //            $prod_routes = self::getPathsAndParentsProds($data_new_prod_for_urls);
        //            $route_id = 'route_id = (CASE id';
        //            $ids_prs = '';
        //            foreach ($prod_routes as $key_pr => $val_pr) {
        //                $route_id .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['route_id'] . "' ";
        //                $ids_prs .= '"' . $key_pr . '",';
        //                unset($key_pr, $val_pr);
        //            }
        //            $route_id .= 'END) ';
        //
        //            $ids_prs = rtrim($ids_prs, ',');
        //            $z_pr = "UPDATE shop_products SET " . $route_id . " WHERE id IN (" . $ids_prs . ")";
        //            $this->db->query($z_pr);
        //
        //            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
        //                dump('shop_product1111');
        //                dd($this->db->_error_message());
        //            }
        //
        //
        //            foreach ($data_new_var as $var => $var_data) {
        //                Image::create()
        //                    ->resizeById($var_data['id']);
        ////                    ->resizeByIdAdditional($var_data['id'], TRUE);
        //                unset($var, $var_data);
        //            }
        //
        //
        //
        //            $properties = self::parseParam($cat_all_rpod_full_data);
        //
        //            list($prod_property_new, $prod_property_ex) = $properties;
        //
        //            $this->fill_product_props($prod_property_new, $prod_property_ex);
        //            $this->fill_prod_props_exist_vals_not_ext($prod_property_ex);
        //            $this->fill_prod_props_exist_vals_exist($prod_property_ex);
        //        }
        if ($count_for_ext_prods > 0) {
            $hot     = 'hot = (CASE id';
            $archive = 'archive = (CASE id';
            $updated = 'updated = (CASE id';
            $ids     = '';
            foreach ($data_upd_prod as $key => $val) {
                $hot     .= " WHEN '" . $val['id'] . "' THEN  '" . $val['hot'] . "' ";
                $archive .= " WHEN '" . $val['id'] . "' THEN '" . $val['archive'] . "' ";
                $updated .= " WHEN '" . $val['id'] . "' THEN '" . $val['updated'] . "' ";
                $ids     .= '"' . $val['id'] . '",';
                unset($key, $val);
            }
            $hot     .= 'END),';
            $archive .= 'END) ,';
            $updated .= 'END) ';
            $ids     = rtrim($ids, ',');
            $z       = 'UPDATE shop_products SET ' . $hot . ' ' . $archive . ' ' . $updated . ' WHERE id IN (' . $ids . ')';
            $this->db->query($z);

            $short_description = 'short_description = (CASE id';
            //            $full_description = 'full_description = (CASE id';
            $ids2 = '';
            foreach ($data_upd_prod_i18n as $key => $val) {
                $short_description .= " WHEN '" . $val['id'] . "' THEN  '" . $val['short_description'] . "' ";
                //                $full_description .= " WHEN '" . $val['id'] . "' THEN  '" . $val['full_description'] . "' ";
                $ids2 .= '"' . $val['id'] . '",';
                unset($key, $val);
            }
            $short_description .= 'END) ,';
            //            $full_description .= 'END) ';
            $ids2 = rtrim($ids2, ',');
            //            $z2 = "UPDATE shop_products_i18n SET " . $short_description . ' ' . $full_description . " WHERE id IN (" . $ids2 . ")";
            $z2 = 'UPDATE shop_products_i18n SET ' . $short_description . ' WHERE id IN (' . $ids2 . ')';
            $this->db->query($z2);

            $price_in_main       = 'price_in_main = (CASE id';
            $price               = 'price = (CASE id';
            $recommendable_price = 'recommendable_price = (CASE id';
            $retail_price_uah    = 'retail_price_uah = (CASE id';
            $currency            = 'currency = (CASE id';
            $number              = 'number = (CASE id';
            $product_code_brain  = 'product_code_brain = (CASE id';
            $stock               = 'stock = (CASE id';
            $ids_var             = '';

            foreach ($data_upd_var as $keys => $vals) {

                $price_in_main       .= " WHEN '" . $vals['id'] . "' THEN  '" . $vals['price_in_main'] . "' ";
                $price               .= " WHEN '" . $vals['id'] . "' THEN  '" . $vals['price'] . "' ";
                $recommendable_price .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['recommendable_price'] . "' ";
                $retail_price_uah    .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['retail_price_uah'] . "' ";
                $currency            .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['currency'] . "' ";
                $number              .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['number'] . "' ";
                $product_code_brain  .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['product_code_brain'] . "' ";
                $stock               .= " WHEN '" . $vals['id'] . "' THEN '" . $vals['stock'] . "' ";
                $ids_var             .= '"' . $vals['id'] . '",';
                unset($keys, $vals);
            }
            $price_in_main       .= 'END),';
            $price               .= 'END),';
            $recommendable_price .= 'END) ,';
            $retail_price_uah    .= 'END) ,';
            $currency            .= 'END) ,';
            $number              .= 'END) ,';
            $product_code_brain  .= 'END) ,';
            $stock               .= 'END) ';
            $ids_var             = rtrim($ids_var, ',');
            $zz                  = 'UPDATE shop_product_variants SET ' . $price_in_main . ' ' . $price . ' ' . $recommendable_price . ' ' . $retail_price_uah . ' ' . $currency . ' ' . $number . ' ' . $product_code_brain . ' ' . $stock . ' WHERE id IN (' . $ids_var . ')';

            $this->db->query($zz);
            unset($count_for_ext_prods, $data_upd_cat_18n);
        }

    }

    private function getPathsAndParentsProds($data_new_prod) {

        // creating id-paths and url-paths of each prod
        foreach ($data_new_prod as $pr_id => $product) {

            $ext_rote = $this->db->where('entity_id', $product['id'])
                ->where('type', 'product')
                ->where('url', translit_url($product['name']) . '-' . $product['id'])
                ->get('route')
                ->result_array();

            if (!$ext_rote || count($ext_rote) == 0 || $ext_rote == null) {
                $parentUrl = $this->db->where('entity_id', $product['category_id'])
                    ->where('type', 'shop_category')
                    ->get('route')
                    ->result_array();

                $parentUrl1 = !empty($parentUrl) ? $parentUrl['0']['parent_url'] . '/' . $parentUrl['0']['url'] : '';

                $route = [
                          'parent_url' => $parentUrl1,
                          'url'        => translit_url($product['name']) . '-' . $product['id'],
                          'entity_id'  => $product['id'],
                          'type'       => 'product',
                         ];

                $this->db->insert('route', $route);
                $newRouteId = $this->db->insert_id();
                //                $this->db->update('shop_category', ['route_id' => $newRouteId], ['id' => $categoryData['id']]);
                $products[$product['id']]['route_id'] = $newRouteId;
            }
            unset($pr_id, $product);
        }
        unset($data_new_prod);

        return $products;
    }

    public function parseParam($rpods_full_data) {
        /*создание свойств, привязка их к категориям*/
        $all_exist_props = $this->db->select('id, csv_name')->get('shop_product_properties');
        if ($all_exist_props && $all_exist_props != null) {
            $all_exist_props = $all_exist_props->result_array();
            foreach ($all_exist_props as $prop_id => $csv) {
                $exist_props[$csv['csv_name']] = $csv['id'];
                unset($prop_id, $csv);
            }
        }
        unset($all_exist_props);
        $all_prop_cats = $this->db->get('shop_product_properties_categories');

        if ($all_prop_cats && $all_prop_cats != null) {
            $all_prop_cats = $all_prop_cats->result_array();
            foreach ($all_prop_cats as $key3 => $prop_cat) {
                $exist_prop_cats[$prop_cat['property_id'] . '-' . $prop_cat['category_id']] = $prop_cat['category_id'];//что бі не пропустить существующих при формировании из одинаковіх ключей
                unset($key3, $prop_cat);
            }
        }

        $props_count = $this->db->query('SELECT id FROM shop_product_properties ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        foreach ($rpods_full_data as $prod_id => $pr_data) {
            $pr_data = $pr_data['product'];
            //dump($pr_data);
            //            $update_product_description[$prod_id] = ['full_description' => $pr_data['description'],
            //                'short_description' => $pr_data['brief_description']];
            $update_product_description[$prod_id] = ['short_description' => $pr_data['brief_description']];

            $options = (array) $pr_data['options'];
            $model1  = [];
            foreach ($options as $opt_key => $property_ob) {
                $property = (array) $property_ob;
                unset($property_ob);
                $by_csv = translit_url(trim($property['name']));

                if (array_key_exists($by_csv, $exist_props)) {
                    $prod_property_ex[$prod_id][$exist_props[$by_csv]]                     = $property;//ИД продукта/ИД свойства /= [имя и значения свойства]
                    $new_prop_catsN[$exist_props[$by_csv] . '-' . $pr_data['category_id']] = !array_key_exists($exist_props[$by_csv] . '-' . $pr_data['category_id'], $exist_prop_cats) /*||
                    $exist_prop_cats[$exist_props[$by_csv]] != $pr_data['category_id']*/ ? [
                                                                                            'property_id' => $exist_props[$by_csv],
                                                                                            'category_id' => $pr_data['category_id'],
                                                                                           ] : false;
                } else {
                    $props_count++;
                    $new_prop_cats[]           = [
                                                  'property_id' => $props_count,
                                                  'category_id' => $pr_data['category_id'],
                                                 ];
                    $data_cr_prop[$by_csv]     = [
                                                  'id'            => $props_count,
                                                  'csv_name'      => $by_csv,
                                                  'active'        => 1,
                                                  'show_on_site'  => 1,
                                                  'multiple'      => 0,
                                                  'main_property' => 1,
                                                 ];
                    $data_cr_prop_18n[$by_csv] = [
                                                  'id'     => $props_count,
                                                  'name'   => (string) trim($property['name']),
                                                  'locale' => 'ru',
                                                 ];

                    $prod_property_new[$prod_id][$by_csv] = $property;//ИД продукта/CSV свойства / имя и значения свойства
                }
                unset($opt_key, $property);
            }
            unset($options, $prod_id, $pr_data);
        }
        if ($props_count > 0) {
            $this->db->insert_batch('shop_product_properties', $data_cr_prop);
            $this->db->insert_batch('shop_product_properties_i18n', $data_cr_prop_18n);
            $this->db->insert_batch('shop_product_properties_categories', $new_prop_catsN);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_properties_categoriesN');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_product_properties_categories', $new_prop_cats);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_properties_categories');
                dd($this->db->_error_message());
            }

            $pr_sh_desc = 'id = (CASE id';
            //            $pr_full_desc = 'id = (CASE id';
            $ids_prs = '';
            foreach ($update_product_description as $key_pr => $val_pr) {
                //                dd($key_pr ,$val_pr);
                $pr_sh_desc .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['short_description'] . "' ";
                //                $pr_full_desc .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['full_description'] . "' ";
                $ids_prs .= '"' . $key_pr . '",';
                unset($key_pr, $val_pr);
            }
            $pr_sh_desc .= 'END), ';
            //            $pr_full_desc .= 'END) ';
            $ids_prs = rtrim($ids_prs, ',');
            //            $desk_pr = "UPDATE shop_products SET " . $pr_sh_desc . ' ' . $pr_full_desc . " WHERE id IN (" . $ids_prs . ")";
            $desk_pr = 'UPDATE shop_products SET ' . $pr_sh_desc . ' WHERE id IN (' . $ids_prs . ')';
            $this->db->query($desk_pr);
        }

        unset($exist_props);

        return [
                $prod_property_new,
                $prod_property_ex,
               ];
    }

    public function fill_product_props($prod_property_new, $prod_property_ex) {
        $props_data_count = $this->db->query('SELECT id FROM shop_product_properties_data ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;
        $props_vals_count = $this->db->query('SELECT id FROM shop_product_property_value_i18n ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $all_prop_vals = $this->db->select('shop_product_property_value.id, value, shop_product_property_value.property_id as property_id, shop_product_property_value_i18n.value as value')
            ->join('shop_product_property_value', 'shop_product_property_value.id=shop_product_property_value_i18n.id')
            ->where('locale', 'ru')
            ->get('shop_product_property_value_i18n');
        if ($all_prop_vals && $all_prop_vals != null) {
            $all_prop_vals = $all_prop_vals->result_array();
            foreach ($all_prop_vals as $key2 => $prop_val) {
                $exist_prop_vals[$prop_val['id']] = $prop_val;
                unset($key2, $prop_val);
            }
        }

        if (count($prod_property_new) > 0) {

            $all_exist_props = $this->db->select('id, csv_name')->get('shop_product_properties');
            if ($all_exist_props && $all_exist_props != null) {
                $all_exist_props = $all_exist_props->result_array();
                foreach ($all_exist_props as $prop_id => $csv1) {
                    $exist_props[$csv1['csv_name']] = $csv1['id'];
                    unset($prop_id, $csv1);
                }
            }

            foreach ($prod_property_new as $prod_id => $props) {

                foreach ($props as $csv => $prop) {
                    $props_data_count++;
                    $props_vals_count++;

                    //                    if (count($exist_prop_vals) > 0) {
                    //                        foreach ($exist_prop_vals as $key_val => $val) {
                    //                            if ($val['property_id'] == $exist_props[$csv] && translit_url(trim($val['value'])) == translit_url(trim($prop['1']))) {
                    //                                $prod_prop_data[] = [
                    //                                    'id' => $props_data_count,
                    //                                    'property_id' => $exist_props[$csv],
                    //                                    'product_id' => $prod_id,
                    //                                    'value_id' => $val['value_id']
                    //                                ];
                    //                            }
                    //                            else {//creating new prop vals
                    //                                $new_val[$exist_props[$csv]][translit_url(trim($prop['1']))] = [
                    //                                    'id' => $props_vals_count,
                    //                                    'property_id' => $exist_props[$csv]
                    //                                ];
                    //                                $new_val_i18[$exist_props[$csv]][translit_url(trim($prop['1']))] = [
                    //                                    'id' => $props_vals_count,
                    //                                    'locale' => 'ru',
                    //                                    'value' => trim($prop['1'])
                    //                                ];//
                    //                                $prod_prop_data[] = [
                    //                                    'id' => $props_data_count,
                    //                                    'property_id' => $exist_props[$csv],
                    //                                    'product_id' => $prod_id,
                    //                                    'value_id' => $props_vals_count
                    //                                ];
                    //                            }//
                    //                            unset($key_val, $val);//                        }
                    //                    }
                    //                    else {
                    //creating new prop vals
                    $new_val[$exist_props[$csv]][translit_url(trim($prop['value']))]     = [
                                                                                            'id'          => $props_vals_count,
                                                                                            'property_id' => $exist_props[$csv],
                                                                                           ];
                    $new_val_i18[$exist_props[$csv]][translit_url(trim($prop['value']))] = [
                                                                                            'id'     => $props_vals_count,
                                                                                            'locale' => 'ru',
                                                                                            'value'  => trim($prop['value']),
                                                                                           ];

                    $prod_prop_data[$props_data_count] = [
                                                          'id'          => $props_data_count,
                                                          'property_id' => $exist_props[$csv],
                                                          'product_id'  => $prod_id,
                                                          'value_id'    => $props_vals_count,
                                                         ];

                    //                    }
                    unset($csv, $prop);
                }
                unset($prod_id, $props);
            }

            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('111');
                dd($this->db->_error_message());
            }

            foreach ($new_val as $key_val => $val_val) {
                //                $this->db->insert_batch('shop_product_properties_data', $prod_prop_data[$key_val]);
                $this->db->insert_batch('shop_product_property_value', $val_val);
                $this->db->insert_batch('shop_product_property_value_i18n', $new_val_i18[$key_val]);
                if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                    dump('222');
                    dd($this->db->_error_message());
                }
                unset($key_val, $val_val);
            }
        }

        unset($exist_props, $exist_prop_vals);
        $this->db->query('DELETE FROM  shop_product_properties_data WHERE  value_id IS NULL');

    }

    public function fill_prod_props_exist_vals_exist($prod_property_ex) {

        $props_data_count = $this->db->query('SELECT id FROM shop_product_properties_data ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;
        $props_vals_count = $this->db->query('SELECT id FROM shop_product_property_value_i18n ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $all_prop_vals = $this->db->select('shop_product_property_value.id, value, shop_product_property_value.property_id as property_id, shop_product_property_value_i18n.value as value')
            ->join('shop_product_property_value', 'shop_product_property_value.id=shop_product_property_value_i18n.id')
            ->where('locale', 'ru')
            ->get('shop_product_property_value_i18n');
        if ($all_prop_vals && $all_prop_vals != null) {
            $all_prop_vals = $all_prop_vals->result_array();
            foreach ($all_prop_vals as $key2 => $prop_val) {
                $exist_prop_vals[$prop_val['id']] = $prop_val;
                unset($key2, $prop_val);
            }
        }
        $all_exist_props = $this->db->select('id, csv_name')->get('shop_product_properties');
        if ($all_exist_props && $all_exist_props != null) {
            $all_exist_props = $all_exist_props->result_array();
            foreach ($all_exist_props as $prop_id => $csv1) {
                $exist_props[$csv1['id']] = $csv1['id'];
                unset($prop_id, $csv1);
            }
        }
        if (count($prod_property_ex) > 0) {
            foreach ($prod_property_ex as $prod_id => $props) {
                $props_data_count++;
                $props_vals_count++;

                foreach ($props as $prop_id => $prop) {

                    $props_data_count++;
                    $props_vals_count++;

                    if (count($exist_prop_vals) > 0) {
                        $data_exist_id = $props_data_count;
                        foreach ($exist_prop_vals as $key_val => $val) {
                            $data_exist_id++;
                            if ($val['property_id'] == $prop_id && translit_url(trim($val['value'])) == translit_url(trim($prop['1']))) {
                                $prod_prop_data_prod_id[$data_exist_id] = [
                                                                           'id'          => $data_exist_id,
                                                                           'property_id' => $prop_id,
                                                                           'product_id'  => $prod_id,
                                                                           'value_id'    => $val['value_id'],
                                                                          ];
                            }
                        }
                    }
                    unset($prop_id, $prop);
                }
                unset($prod_id, $props);
            }

            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data_prod_id);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('ggggg');
                dd($this->db->_error_message());
            }
        }
        unset($exist_props, $exist_prop_vals);

        return ($prod_property_ex);
        $this->db->query('DELETE FROM  shop_product_properties_data WHERE  value_id IS NULL');
    }

    public function fill_prod_props_exist_vals_not_ext($prod_property_ex) {
        if (count($prod_property_ex) > 0) {
            foreach ($prod_property_ex as $prod_id => $props) {
                foreach ($props as $prop_id => $prop) {
                    $property      = SPropertiesQuery::create()->findOneById($prop_id);
                    $propertyValue = SPropertyValueQuery::create()
                                                        ->joinWithI18n('ru')
                                                        ->useI18nQuery('ru')
                                                        ->filterByValue(trim($prop['1']))
                                                        ->endUse()
                                                        ->findOneByPropertyId($prop_id);
                    $id            = false;
                    if (!$property) {
                    } elseif ($propertyValue !== null) {
                        $pr_val_id = $propertyValue->getId();
                    } else {
                        $propertyValue = new SPropertyValue();
                        $propertyValue->setLocale('ru')
                            ->setPropertyId($prop_id)
                            ->setValue(trim($prop['value']))
                            ->save();
                        $pr_val_id = $propertyValue->getId();
                    }
                    ProductApi::getInstance()->setProductPropertyValue($prod_id, $prop_id, $pr_val_id, 'ru');

                    unset($prop_id, $prop);
                }
                unset($prod_id, $props);
            }
        }
        unset($exist_props, $exist_prop_vals);
        $this->db->query('DELETE FROM  shop_product_properties_data WHERE  value_id IS NULL');
    }

}

/*
  Родительский класс для XML обработчиков.
 */