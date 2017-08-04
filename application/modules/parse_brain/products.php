<?php

use CMSFactory\ModuleSettings;
use MediaManager\Image;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Products extends MY_Controller
{

    private $i             = 0;

    private $time;

    private $arr           = [];

    private $result_prods;

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

        //        $n = 0;
        //        $products_cat_res = [];
        //
        //        do {
        //
        //            $products_cats_obj = \parse_brain\classes\ParentBrain::getInstance()->curl_to_send(null, 'http://api.brain.com.ua/products/1202/' . $this->auth_session . '?limit=50&offset=' . $n*50, 'get');
        //            $products_cat1 = (array)$products_cats_obj->list;
        //
        //            foreach ($products_cat1 as $key => $arr) {
        //                $products_cat[$arr->productID] = (array)$arr;
        //            }
        //
        //            if (is_array($products_cat1)) {
        //                $products_cat_res = array_merge($products_cat_res, $products_cat);
        //            }
        //
        //            $n++;
        //        } while (count($products_cat1) > 0);

        $this->existing_categories = $this->get_exist_category();

    }

    /**
     * @param $post_string
     * @param $pr_url
     * @param $method
     *
     * @return CI_User_agent
     */
    public function curl_to_send($post_string, $pr_url, $method) {

        $headers = [
                    'Accept: text/xml,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
                    'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
                    'Accept-Encoding: deflate',
                    'Accept-Charset: utf-8;q=0.7,*;q=0.7',
                    'Content-type: application/xml; charset=UTF-8;',
                   ];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pr_url);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response1 = curl_exec($ch); // отдает paynet на запрос в формате XML
        curl_close($ch);
        if (json_decode($response1)->status == 1) {
            return json_decode($response1)->result;
        }

        return false;

    }

    public function get_exist_category() {
        $pr_cats = $this->db->select('id,external_id')->get('shop_category')->result_array();
        foreach ($pr_cats as $cat_key => $cat) {
            $exist_cats[$cat['external_id']] = $cat['id'];
            unset($cat_key, $cat);
        }

        return $exist_cats;
    }

    /**
     * @return CI_User_agent
     */
    public function get_auth_brain() {

        //        return self::curl_to_send(['login' => 'moaykramnicay@gmail.com', 'password' => md5('123456')], 'http://api.brain.com.ua/auth', 'post');
        return $this->curl_to_send(
            [
             'login'    => $this->settings['login'],
             'password' => md5($this->settings['password']),
            ],
            'http://api.brain.com.ua/auth',
            'post'
        );

    }

    /**
     * @return string
     */
    public function create() {
        /*
        в категории, товере, варианте и бренде добавим колонку is_brain  - признак, что из брейна
        * */

        if ($this->input->post('products') >= 0) {

            $categories_for_prods1 = $this->existing_categories;

            foreach ($categories_for_prods1 as $key => $cat) {
                $categories_for_prods[$cat->categoryID] = $cat;
                unset($key, $cat);
            }
            unset($categories_for_prods1);

            try {
                $this->products_create($this->input->post('products'), $this->input->post('prod_offset'));
            } catch (\Exception $e) {
                showMessage($e);

            }

            unset($categories_for_prods);

            showMessage(lang('Создано ', '') . count($this->result['products']) . lang(' товаров  ', ''), 'g');

            //            return json_encode(array(lang('Создано ', '') . count($this->result['products']) . lang(' товаров', '') , 'g', '1'=>$this->input->post('products')));
            return json_encode(
                [
                 $this->input->post('products'),
                 $this->input->post('prod_offset'),
                ]
            );

        }

    }

    public function one_cat_count_prods($count_post, $offset) {

        $count_post   = $this->input->post('catsN');
        $offset       = $this->input->post('prod_offset');
        $exist_cats   = $this->existing_categories;
        $cats_in_shop = array_flip($exist_cats);

        $module_settings_to_parse_cat2 = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
        foreach ($module_settings_to_parse_cat2 as $key => $item) {

            $cat = $this->db->select('id')->where('id', $item)->get('shop_category');

            if (!$cat->num_rows()) {
                unset($module_settings_to_parse_cat2[$item]);
            }
            unset($key, $item);
        }

        $module_settings_to_parse_cat = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];

        if (array_diff($module_settings_to_parse_cat, $module_settings_to_parse_cat2)) {
            ModuleSettings::ofModule('parse_brain')->set('categories_to_parce', $module_settings_to_parse_cat2);

            $module_settings_to_parse_cat = $module_settings_to_parse_cat2;
        }

        $keys    = array_keys($module_settings_to_parse_cat);
        $key     = $keys[$count_post];
        $one_cat = $module_settings_to_parse_cat[$keys[$count_post]];

        if (array_key_exists($one_cat, $cats_in_shop)) {

            $n                = 0;
            $products_cat_res = [];

            do {

                $products_cats_obj = \parse_brain\classes\ParentBrain::getInstance()
                                                                     ->curl_to_send(null, 'http://api.brain.com.ua/products/' . $cats_in_shop[$one_cat] . '/' . $this->auth_session . '?limit=1000&offset=' . $n * 1000, 'get');
                $products_cat1     = (array) $products_cats_obj->list;

                foreach ($products_cat1 as $key => $arr) {
                    $products_cat[$arr->productID] = (array) $arr;
                }

                //                if (is_array($products_cat1)) {
                //                    $products_cat_res = array_merge($products_cat_res, $products_cat);
                //                }

                $n++;
            } while (count($products_cat1) > 0);

            unset($products_cats_obj);
            //            return json_encode(['success' => count($products_cat_res)]);
            //            dd((int)count($products_cat_res));
            return (int) count($products_cat);

        }

    }

    public function products_create($count_post, $offset) {

        /*
         в схеме пропела добавить для варианта
        <column name="retail_price_uah" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="recommendable_price" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="product_code_brain"  type="varchar" size="255" required="false"/>
         * */
        $exist_cats   = $this->existing_categories;
        $cats_in_shop = array_flip($exist_cats);

        $pr_brands = $this->db->select('id, external_id')->get('shop_brands')->result_array();
        foreach ($pr_brands as $br_key => $brand) {
            $exist_brands[$brand['external_id']] = $brand['id'];
            unset($br_key, $brand);
        }
        //        ModuleSettings::ofModule('parse_brain')->set('categories_to_parce','');

        $module_settings_to_parse_cat2 = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];
        foreach ($module_settings_to_parse_cat2 as $key => $item) {

            $cat = $this->db->select('id')->where('id', $item)->get('shop_category');

            if (!$cat->num_rows()) {
                unset($module_settings_to_parse_cat2[$item]);
            }

        }

        $module_settings_to_parse_cat = ModuleSettings::ofModule('parse_brain')->get()['categories_to_parce'];

        if (array_diff($module_settings_to_parse_cat, $module_settings_to_parse_cat2)) {
            ModuleSettings::ofModule('parse_brain')->set('categories_to_parce', $module_settings_to_parse_cat2);

            $module_settings_to_parse_cat = $module_settings_to_parse_cat2;
        }

        $keys    = array_keys($module_settings_to_parse_cat);
        $key     = $keys[$count_post];
        $one_cat = $module_settings_to_parse_cat[$keys[$count_post]];

        //        $aa = -1;
        //        foreach ($module_settings_to_parse_cat as $key => $one_cat) {
        //            $aa++;

        //            if ($aa == $count_post) {

        if (array_key_exists($one_cat, $cats_in_shop)) {

            $products_cats_obj = \parse_brain\classes\ParentBrain::getInstance()
                                                                 ->curl_to_send(null, 'http://api.brain.com.ua/products/' . $cats_in_shop[$one_cat] . '/' . $this->auth_session . '?limit=50&offset=' . $offset, 'get');
            //dump($products_cats_obj);
            $products_cat = (array) $products_cats_obj->list;

            unset($products_cats_obj);

            if (count($products_cat) == 0) {
                return false;
            }
            $this->result_prods['prods'] = count($products_cat);

            $this->process_prods_one_cat($products_cat, $exist_prods_ids, $exist_brands, $exist_cats);

            $prod_exist_ids1 = $this->db->select('id, external_id')->get('shop_products')->result_array();

            foreach ($prod_exist_ids1 as $prod_key => $prod) {
                $exist_prods_ids1[$prod['external_id']] = $prod['id'];
                unset($prod_key, $prod);
            }

            $prods_pics_fr_cat = \parse_brain\classes\ParentBrain::getInstance()
                                                                 ->curl_to_send(null, 'http://api.brain.com.ua/products_pictures/' . $cats_in_shop[$one_cat] . '/' . $this->auth_session, 'get');
            //Метод возвращает список адресов картинок товаров указанной категории.
            $prods_pics_fr_cat_arr = (array) $prods_pics_fr_cat->list;
            unset($prods_pics_fr_cat);

            foreach ($prods_pics_fr_cat_arr as $key_data => $pr_data_pic) {
                $pr_data_pics = (array) $pr_data_pic;
                unset($pr_data_pic);

                if (array_key_exists($pr_data_pics['productID'], $exist_prods_ids1)) {
                    $k = 0;
                    foreach ($pr_data_pics['pictures'] as $key_pic => $pic_datas) {
                        $k++;
                        $pic_data = (array) $pic_datas;
                        unset($pic_datas);

                        if (!file_exists('./uploads/shop/products/origin/additional/' . $exist_prods_ids1[$pr_data_pics['productID']] . pathinfo($pic_data['large_image'], PATHINFO_BASENAME))) {
                            $img_data = file_get_contents($pic_data['large_image']);
                            file_put_contents('./uploads/shop/products/origin/additional/' . $exist_prods_ids1[$pr_data_pics['productID']] . pathinfo($pic_data['large_image'], PATHINFO_BASENAME), $img_data);

                            file_put_contents('./uploads/shop/products/additional/' . $exist_prods_ids1[$pr_data_pics['productID']] . pathinfo($pic_data['large_image'], PATHINFO_BASENAME), $img_data);
                            file_put_contents('./uploads/shop/products/additional/' . 'thumb_' . $exist_prods_ids1[$pr_data_pics['productID']] . pathinfo($pic_data['large_image'], PATHINFO_BASENAME), $img_data);

                            $add_images[$exist_prods_ids1[$pr_data_pics['productID']]]                    = pathinfo($pic_data['large_image'], PATHINFO_BASENAME);
                            $data_new_prod_image[$exist_prods_ids1[$pr_data_pics['productID']]][$key_pic] = [
                                                                                                             'product_id' => $exist_prods_ids1[$pr_data_pics['productID']],
                                                                                                             'image_name' => $exist_prods_ids1[$pr_data_pics['productID']] . pathinfo($pic_data['large_image'], PATHINFO_BASENAME),
                                                                                                             'position'   => $k + 1,
                                                                                                            ];
                        }

                        unset($key_pic, $pic_datas);
                    }
                }

                unset($key_data, $pr_data_pic);
            }

            foreach ($data_new_prod_image as $some_pic_key => $datapict) {
                $this->db->insert_batch('shop_product_images', $datapict);
            }

            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_images');
                dd($this->db->_error_message());
            }
            unset($data_new_prod_image, $prods_pics_fr_cat_arr, $products_cats, $prod_vars_count);
            //                    foreach($add_images as $key=>$image){
            //                        Image::create()
            //                            ->resizeByIdAdditional($key, TRUE);
            //                        unset($key, $image);
            //                    }

            $this->db->query('DELETE FROM  shop_product_properties_data WHERE  value_id IS NULL');
        }

        unset($key, $one_cat);
        //            }

        //        }

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

            $product = (array) $product_obj;
            unset($product_obj);
            $prod_vars_count++;
            if (!array_key_exists($product['productID'], $exist_prods_ids)) {
                $count_for_new_prod++;

                $prod_vars_count++;

                $pic_data = file_get_contents($product['large_image']);
                $picture  = $product['id'] . pathinfo($product['large_image'], PATHINFO_BASENAME);
                file_put_contents('./uploads/shop/products/origin/' . $product['id'] . pathinfo($product['large_image'], PATHINFO_BASENAME), $pic_data);
                $pr_brand_id = array_key_exists($product['vendorID'], $exist_brands) ? $exist_brands[$product['vendorID']] : null;//$pr_brand && $pr_brand !=null?$pr_brand:null,//$product['vendorID']

                $data_new_prod[$prod_vars_count]          = [
                                                             'id'              => $prod_vars_count,
                                                             'active'          => 1,
                                                             'hot'             => $product['is_new'] != '0' ? 1 : 0,
                                                             'archive'         => $product['is_archive'] != '0' ? 1 : 0,
                                                             'external_id'     => $product['productID'],
                                                             'category_id'     => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                    //$pr_cat && $pr_cat !=null?$pr_cat:1,
                                                             'brand_id'        => $pr_brand_id,
                                                             'enable_comments' => 1,
                                                             'created'         => time(),
                                                             'updated'         => time(),
                                                             'is_brain'        => 1,
                                                            ];
                $data_new_prod_for_urls[$prod_vars_count] = [
                                                             'id'          => $prod_vars_count,
                                                             'name'        => $product['name'],
                                                             'category_id' => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                                                            ];
                $data_new_prod_cats[$prod_vars_count]     = [
                                                             'product_id'  => $prod_vars_count,
                                                             'category_id' => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                                                            ];
                $data_new_prod_i18[]                      = [
                                                             'id'     => $prod_vars_count,
                                                             'name'   => $product['name'],
                                                             'locale' => 'ru',
                    //                    'full_description' => $product['brief_description'],
                    //                    'short_description' => $product['brief_description'],

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

                if (array_key_exists($pr_cat_id, $cat_brand_rrc) && $cat_brand_rrc[$pr_cat_id] == $pr_brand_id) {
                    $price_in_main = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                    $price         = $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0;
                }

                //                $price_in_main = $price_in_main != null ? (double)$price_in_main : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);
                //                $price = $price != null ? (double)$price : (double)($product['recommendable_price'] && $product['recommendable_price'] != null && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah']);

                $price_in_main = $price_in_main !== null ? (double) $price_in_main : $product['retail_price_uah'];
                $price         = $price_in_main;

                $data_new_var[] = [
                                   'id'                  => $prod_vars_count,
                                   'product_id'          => $prod_vars_count,
                                   'price_in_main'       => str_replace(',', '.', $price_in_main),
                                   'price'               => str_replace(',', '.', $price),
                                   'recommendable_price' => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0,
                                   'retail_price_uah'    => $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0,
                                   'currency'            => $def_cur,
                    //$codeAndId[$product['currencyId']],
                                   'number'              => $product['articul'],
                                   'product_code_brain'  => $product['product_code'],
                                   'stock'               => !empty($product['available']) ? max((array) $product['available']) : 0,
                                   'external_id'         => $product['productID'],
                                   'mainImage'           => $picture,
                                   'is_brain'            => 1,
                                  ];

                $data_new_var_i18[] = [
                                       'id'     => $prod_vars_count,
                                       'name'   => $product['name'],
                                       'locale' => 'ru',
                                      ];

                $cat_all_rpod_full_data[$prod_vars_count] = [
                                                             'product_code_brain' => $product['product_code'],
                                                             'category_id'        => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                                                            ];

                $this->result['products'][$prod_vars_count] = $prod_vars_count;

            }
            unset($pr_key, $product, $price_in_main, $price);
        }
        if ($count_for_new_prod > 0) {

            $this->db->insert_batch('shop_products', $data_new_prod);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_products');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_products_i18n', $data_new_prod_i18);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_products_i18n');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_product_variants', $data_new_var);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_variants');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_product_variants_i18n', $data_new_var_i18);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_variants_i18n');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_product_categories', $data_new_prod_cats);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_categories');
                dd($this->db->_error_message());
            }

            $prod_routes = $this->getPathsAndParentsProds($data_new_prod_for_urls);
            $route_id    = 'route_id = (CASE id';
            $ids_prs     = '';
            foreach ($prod_routes as $key_pr => $val_pr) {
                $route_id .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['route_id'] . "' ";
                $ids_prs  .= '"' . $key_pr . '",';
                unset($key_pr, $val_pr);
            }
            $route_id .= 'END) ';

            $ids_prs = rtrim($ids_prs, ',');
            $z_pr    = 'UPDATE shop_products SET ' . $route_id . ' WHERE id IN (' . $ids_prs . ')';
            $this->db->query($z_pr);

            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product1111');
                dd($this->db->_error_message());
            }
            foreach ($data_new_var as $var => $var_data) {
                Image::create()->resizeById($var_data['id']);
                //                    ->resizeByIdAdditional($var_data['id'], TRUE);
                unset($var, $var_data);
            }

            $rpods_full_data = $this->rpod_full_data($cat_all_rpod_full_data);

            $properties = $this->parseParam($rpods_full_data);

            list($prod_property_new, $prod_property_ex) = $properties;
            $this->fill_product_props_vals_n($prod_property_new, $prod_property_ex);

            $this->fill_prod_props_exist_vals_not_ext($prod_property_ex);
            $this->fill_prod_props_data($prod_property_new, $prod_property_ex);
            //                $this->fill_prod_props_exist_vals_exist($prod_property_ex);
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

    public function rpod_full_data($products) {
        $some_count_prop = 0;
        foreach ($products as $key_pr_id => $product) {
            $some_count_prop++;

            $pr_data_brain     = (array) \parse_brain\classes\ParentBrain::getInstance()
                                                                        ->curl_to_send(null, 'http://api.brain.com.ua/product/product_code/' . $product['product_code_brain'] . '/' . $this->auth_session . '?', 'get');
            $pr_data_brain_opt = (array) \parse_brain\classes\ParentBrain::getInstance()
                                                                        ->curl_to_send(null, 'http://api.brain.com.ua/product_options/' . $pr_data_brain['productID'] . '/' . $this->auth_session . '?', 'get');

            $pr_data[$key_pr_id] = [
                                    '0'           => $pr_data_brain,
                                    'category_id' => $product['category_id'],
                                    'options'     => $pr_data_brain_opt,
                                   ];
            unset($key_pr_id, $product);
        }
        unset($products);

        return $pr_data;
    }

    public function parseParam($rpods_full_data) {
        /*создание свойств, привязка их к категориям*/
        $all_exist_props = $this->db->select('id, external_id')->get('shop_product_properties');
        if ($all_exist_props && $all_exist_props != null) {
            $all_exist_props = $all_exist_props->result_array();
            foreach ($all_exist_props as $prop_id => $csv) {
                $exist_props[$csv['external_id']] = $csv['id'];
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
            //            $update_product_description[$prod_id] = ['full_description' => $pr_data['0']['description'],
            //                'short_description' => $pr_data['0']['brief_description']];

            $update_product_description[$prod_id] = ['short_description' => $pr_data['0']['brief_description']];

            $options = $pr_data['options'];

            foreach ($options as $opt_key => $property_ob) {
                $property = (array) $property_ob;
                unset($property_ob);

                if (array_key_exists($property['OptionID'], $exist_props)) {
                    $prod_property_ex[$prod_id][$property['OptionID']] = $property;//ИД продукта/ИД свойства /= [имя и значения свойства]
                    //                    $new_prop_catsN[$exist_props[$property['OptionID']] . '-' . $pr_data['category_id']] = !key_exists($exist_props[$property['OptionID']] . '-' . $pr_data['category_id'], $exist_prop_cats)
                    //                     ? ['property_id' => $exist_props[$property['OptionID']], 'category_id' => $pr_data['category_id']] : false;
                    if (!array_key_exists($exist_props[$property['OptionID']] . '-' . $pr_data['category_id'], $exist_prop_cats)) {
                        $new_prop_catsN[$exist_props[$property['OptionID']] . '-' . $pr_data['category_id']] = [
                                                                                                                'property_id' => $exist_props[$property['OptionID']],
                                                                                                                'category_id' => $pr_data['category_id'],
                                                                                                               ];
                    }
                } else {
                    $props_count++;
                    $new_prop_cats[]                         = [
                                                                'property_id' => $props_count,
                                                                'category_id' => $pr_data['category_id'],
                                                               ];
                    $data_cr_prop[$property['OptionID']]     = [
                                                                'id'            => $props_count,
                                                                'csv_name'      => translit_url(trim($property['OptionName']) . $property['OptionID']),
                                                                'active'        => 1,
                                                                'show_on_site'  => 1,
                                                                'multiple'      => 0,
                                                                'main_property' => 1,
                                                                'external_id'   => $property['OptionID'],
                                                               ];
                    $data_cr_prop_18n[$property['OptionID']] = [
                                                                'id'     => $props_count,
                                                                'name'   => (string) trim($property['OptionName']),
                                                                'locale' => 'ru',
                                                               ];

                    $prod_property_new[$prod_id][$property['OptionID']] = $property;//ИД продукта/CSV свойства / имя и значения свойства
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

    public function fill_product_props_vals_n($prod_property_new, $prod_property_ex) {
        //        $props_data_count = $this->db->query('SELECT id FROM shop_product_properties_data ORDER BY id DESC LIMIT 1')->result()['0']->id;
        $props_vals_count = $this->db->query('SELECT id FROM shop_product_property_value_i18n ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $all_prop_vals = $this->db->select('shop_product_property_value.external_id, shop_product_property_value.id, shop_product_property_value.property_id')//            ->join('shop_product_property_value', 'shop_product_property_value.id=shop_product_property_value_i18n.id')
        //            ->where('locale', 'ru')
            ->get('shop_product_property_value');
        //            ->get('shop_product_property_value_i18n');
        if ($all_prop_vals && $all_prop_vals != null) {
            $all_prop_vals = $all_prop_vals->result_array();
            foreach ($all_prop_vals as $key2 => $prop_val) {
                $exist_prop_vals[$prop_val['external_id']] = $prop_val;
                unset($key2, $prop_val);
            }
        }

        if (count($prod_property_new) > 0) {

            $all_exist_props = $this->db->select('id, external_id')->get('shop_product_properties');
            if ($all_exist_props && $all_exist_props != null) {
                $all_exist_props = $all_exist_props->result_array();
                foreach ($all_exist_props as $prop_id => $csv1) {
                    $exist_props[$csv1['external_id']] = $csv1['id'];
                    unset($prop_id, $csv1);
                }
            }

            foreach ($prod_property_new as $prod_id => $props) {

                foreach ($props as $csv => $prop) {

                    $props_vals_count++;

                    $new_val[$prop['ValueID']]     = [
                                                      'id'          => $props_vals_count,
                                                      'property_id' => $exist_props[$prop['OptionID']],
                                                      'external_id' => $prop['ValueID'],
                                                     ];
                    $new_val_i18[$prop['ValueID']] = [
                                                      'id'     => $props_vals_count,
                                                      'locale' => 'ru',
                                                      'value'  => trim($prop['ValueName']),
                                                     ];

                    unset($csv, $prop);
                }
                unset($prod_id, $props);
            }

            $this->db->insert_batch('shop_product_property_value', $new_val);
            $this->db->insert_batch('shop_product_property_value_i18n', $new_val_i18);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('222');
                dd($this->db->_error_message());
            }
            unset($key_val, $val_val);

        }

        unset($exist_props, $exist_prop_vals);

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
    }

    public function fill_prod_props_exist_vals_not_ext($prod_property_ex) {
        $props_vals_count = $this->db->query('SELECT id FROM shop_product_property_value_i18n ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $all_prop_vals = $this->db->select('shop_product_property_value.external_id, shop_product_property_value.id, shop_product_property_value.property_id')
            ->get('shop_product_property_value');
        if ($all_prop_vals && $all_prop_vals != null) {
            $all_prop_vals = $all_prop_vals->result_array();
            foreach ($all_prop_vals as $key2 => $prop_val) {
                $exist_prop_vals[$prop_val['external_id']] = $prop_val;
                unset($key2, $prop_val);
            }
        }

        if (count($prod_property_ex) > 0) {

            $all_exist_props = $this->db->select('id, external_id')->get('shop_product_properties');
            if ($all_exist_props && $all_exist_props != null) {
                $all_exist_props = $all_exist_props->result_array();
                foreach ($all_exist_props as $prop_id => $csv1) {
                    $exist_props[$csv1['external_id']] = $csv1['id'];
                    unset($prop_id, $csv1);
                }
            }

            foreach ($prod_property_ex as $prod_id => $props1) {
                $props = (array) $props1;
                unset($props1);
                foreach ($props as $prop_id => $prop) {
                    if (array_key_exists($prop['OptionID'], $exist_props)) {
                        if (!array_key_exists($prop['ValueID'], $exist_prop_vals)) {
                            $props_vals_count++;
                            $new_val[$prop['ValueID']]     = [
                                                              'id'          => $props_vals_count,
                                                              'property_id' => $exist_props[$prop['OptionID']],
                                                              'external_id' => $prop['ValueID'],
                                                             ];
                            $new_val_i18[$prop['ValueID']] = [
                                                              'id'     => $props_vals_count,
                                                              'locale' => 'ru',
                                                              'value'  => trim($prop['ValueName']),
                                                             ];
                        }
                    }

                    unset($prop_id, $prop);
                }
                unset($prod_id, $props);
            }
            $this->db->insert_batch('shop_product_property_value', $new_val);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_property_value22');
                dd($this->db->_error_message());
            }
            $this->db->insert_batch('shop_product_property_value_i18n', $new_val_i18);
            if ($this->db->_error_message() && $this->db->_error_message() != null && $this->db->_error_message() != '') {
                dump('shop_product_property_value_i18n3333');
                dd($this->db->_error_message());
            }
        }
        unset($exist_props, $exist_prop_vals);
    }

    /**
     * @param $prod_property_new
     * @param $prod_property_ex
     *
     * @return CI_User_agent
     */
    public function fill_prod_props_data($prod_property_new, $prod_property_ex) {
        $props_vals_data_count = $this->db->query('SELECT id FROM shop_product_properties_data ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $all_prop_vals = $this->db->select('shop_product_property_value.external_id, shop_product_property_value.id')
            ->get('shop_product_property_value');
        if ($all_prop_vals && $all_prop_vals != null) {
            $all_prop_vals = $all_prop_vals->result_array();
            foreach ($all_prop_vals as $key2 => $prop_val) {
                $exist_prop_vals[$prop_val['external_id']] = $prop_val['id'];
                unset($key2, $prop_val);
            }
        }
        $all_exist_props = $this->db->select('id, external_id')->get('shop_product_properties');
        if ($all_exist_props && $all_exist_props != null) {
            $all_exist_props = $all_exist_props->result_array();
            foreach ($all_exist_props as $prop_id => $csv1) {
                $exist_props[$csv1['external_id']] = $csv1['id'];
                unset($prop_id, $csv1);
            }
        }
        $all_prods_prop_data = $this->db->select('shop_product_properties_data.id, shop_product_properties_data.property_id, shop_product_properties_data.product_id, shop_product_properties_data.value_id')
            ->get('shop_product_properties_data');
        if ($all_prods_prop_data && $all_prods_prop_data != null) {
            $all_prods_prop_data = $all_prods_prop_data->result_array();
            foreach ($all_prods_prop_data as $key2 => $prod_prop_val) {
                $exist_prod_prop_vals_data[$prod_prop_val['product_id'] . $prod_prop_val['property_id'] . $prod_prop_val['value_id']] = $prod_prop_val['id'];
                unset($key2, $prod_prop_val);
            }
        }

        if (count($prod_property_ex) > 0) {

            foreach ($prod_property_ex as $prod_id => $props1) {
                $props = (array) $props1;
                unset($props1);
                foreach ($props as $prop_id => $prop) {
                    if (!array_key_exists($prod_id . $exist_props[$prop['OptionID']] . $exist_prop_vals[$prop['ValueID']], $exist_prod_prop_vals_data)) {
                        $props_vals_data_count++;
                        $prod_prop_data_prod_id[$props_vals_data_count] = [
                                                                           'id'          => $props_vals_data_count,
                                                                           'property_id' => $exist_props[$prop['OptionID']],
                                                                           'product_id'  => $prod_id,
                                                                           'value_id'    => $exist_prop_vals[$prop['ValueID']],
                                                                          ];
                    }
                    unset($prop_id, $prop);
                }
                unset($prod_id, $props1);
            }
            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data_prod_id);
        }
        if (count($prod_property_new) > 0) {

            foreach ($prod_property_new as $prod_id => $props1) {
                $props = (array) $props1;
                unset($props1);
                foreach ($props as $prop_id => $prop) {
                    if (!array_key_exists($prod_id . $exist_props[$prop['OptionID']] . $exist_prop_vals[$prop['ValueID']], $exist_prod_prop_vals_data)) {
                        $props_vals_data_count++;
                        $prod_prop_data_prod_id[$props_vals_data_count] = [
                                                                           'id'          => $props_vals_data_count,
                                                                           'property_id' => $exist_props[$prop['OptionID']],
                                                                           'product_id'  => $prod_id,
                                                                           'value_id'    => $exist_prop_vals[$prop['ValueID']],
                                                                          ];
                    }
                    unset($prop_id, $prop);
                }
                unset($prod_id, $props1);
            }
            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data_prod_id);
        }
    }

}

/*
  Родительский класс для XML обработчиков.
 */