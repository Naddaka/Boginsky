<?php

use CMSFactory\ModuleSettings;
use parse_brain\classes\ParentBrain;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Parse_brain extends MY_Controller
{

    private $i           = 0;

    private $time;

    private $arr         = [];

    private $result;

    private $xml_prop;

    private $countProp   = 200;

    private $uploadDir   = './uploads/';

    private $csvFileName = 'comments.csv';

    //    public $auth_session;

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('parse_brain');
        $this->load->helper('translit');
        $this->time         = time();
        $this->settings     = ModuleSettings::ofModule('parse_brain')->get();
        $this->auth_session = $this->get_auth_brain();

    }

    /**
     * @return CI_User_agent
     */
    //    public function index2()
    //    {
    //        $products_cats_obj = \parse_brain\classes\ParentBrain::getInstance()->curl_to_send(null, 'http://api.brain.com.ua/products/1181'/* . $cats_in_shop[$one_cat]*/ . '/' . $this->auth_session, 'get');
    //        dump($products_cats_obj);
    //        $products_cat = (array)$products_cats_obj->list;
    //
    //        dd($products_cat);
    //    }

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

    /**
     * @return CI_User_agent
     */
    public function start_parse() {

        //       $pr =  \parse_brain\classes\ParentBrain::getInstance()->curl_to_send(null, 'http://api.brain.com.ua/product/product_code/ET05560/' . $this->auth_session . '?', 'get');
        //        $pr = (array)$pr;
        //        dd(max((array)['0'=>2, '1'=>6]));
        //        dd($pr);

        $categories = ParentBrain::getInstance()
                                 ->curl_to_send(null, 'http://api.brain.com.ua/categories/' . $this->auth_session . '?', 'get');

        /*
         в категории, товере, варианте и бренде добавим колонку is_brain  - признак, что из брейна
         * */

        $categories_for_prods1 = $categories;
        unset($categories);
        foreach ($categories_for_prods1 as $key => $cat) {
            $categories_for_prods[$cat->categoryID] = (array) $cat;
            unset($key, $cat);
        }
        unset($categories_for_prods1);

        $this->cats_create($categories_for_prods);

        if ($this->result['categories'] != null) {
            $new_cats = $this->result['categories'];

            unset($this->result['categories']);
            $this->catsFix($new_cats);
            unset($new_cats);

        }

        $this->vendors_create();
        dump(time());

        try {
            $this->products_create((array) $categories_for_prods);
        } catch (\Exception $e) {
            dd($e);
        }

        unset($categories_for_prods);

    }

    public function products_create($categories) {
        /*
        в схеме пропела добавить для варианта
        <column name="retail_price_uah" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="recommendable_price" type="float" required="true" sqlType="DOUBLE (20,5)"/>
        <column name="product_code_brain"  type="varchar" size="255" required="false"/>
        * */

        $pr_cats = $this->db->select('id,external_id')->get('shop_category')->result_array();
        foreach ($pr_cats as $cat_key => $cat) {
            $exist_cats[$cat['external_id']] = $cat['id'];
            unset($cat_key, $cat);
        }

        $pr_brands = $this->db->select('id, external_id')->get('shop_brands')->result_array();
        foreach ($pr_brands as $br_key => $brand) {
            $exist_brands[$brand['external_id']] = $brand['id'];
            unset($br_key, $brand);
        }

        $aa = 0;
        foreach ($categories as $key => $one_cat) {
            $aa++;
            $prod_exist_ids = $this->db->select('id, external_id')->get('shop_products')->result_array();

            foreach ($prod_exist_ids as $prod_key => $prod) {
                $exist_prods_ids[$prod['external_id']] = $prod['id'];
                unset($prod_key, $prod);
            }

            $products_cats = ParentBrain::getInstance()
                                        ->curl_to_send(null, 'http://api.brain.com.ua/products/' . $one_cat['categoryID'] . '/' . $this->auth_session, 'get');
            $products_cat  = (array) $products_cats->list;
            $this->process_prods_one_cat($products_cat, $exist_prods_ids, $exist_brands, $exist_cats);
            unset($products_cats, $prod_vars_count);
            if ($aa == '200' || $aa == '400' || $aa == '600' || $aa == '800') {
                dump($aa . '-- ' . time());
            }
            unset($key, $one_cat);
        }
        dump(time());
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
        //                $this->db->truncate('shop_products');
        //        $this->db->truncate('shop_products_i18n');
        //        $this->db->truncate('shop_product_variants');
        //        $this->db->truncate('shop_product_variants_i18n');
        //        $this->db->truncate('shop_product_categories');
        //        $this->db->truncate('shop_product_images');
        //        $this->db->truncate('custom_fields_data');
        //
        //        $this->db->truncate('shop_product_properties');
        //        $this->db->truncate('shop_product_properties_i18n');
        //        $this->db->truncate('shop_product_properties_categories');
        //        $this->db->truncate('shop_product_properties_data');
        //        $this->db->truncate('shop_product_properties_i18n');
        //        $this->db->truncate('shop_product_property_value_i18n');
        //        $this->db->truncate('shop_product_property_value');
        //
        //        $this->db->query("DELETE FROM `route` WHERE `type` ='product'");
        //        $this->db->query("DELETE FROM `route` WHERE `type` ='product'");
        //exit();

        $prod_vars_count = $this->db->query('SELECT id FROM shop_product_variants ORDER BY id DESC LIMIT 1')
            ->result()['0']->id;

        $count_for_ext_prods = 0;
        $count_for_new_prod  = 0;
        $my_some_counter     = 0;
        foreach ($products_cat as $pr_key => $product_obj) {
            $my_some_counter++;

            $product = (array) $product_obj;
            unset($product_obj);
            $prod_vars_count++;
            if (!array_key_exists($product['productID'], $exist_prods_ids)) {
                $count_for_new_prod++;

                $picture1 = !file_exists('./uploads/shop/products/origin/' . $product['vendorCode'] . pathinfo($product['medium_image'], PATHINFO_BASENAME)) ? 'false' : '1';//pathinfo($val, PATHINFO_BASENAME) ;
                if ($picture1 == 'false') {
                    $pic_data = file_get_contents($product['medium_image']);
                    if ($pic_data) {
                        $picture = pathinfo($product['medium_image'], PATHINFO_BASENAME);
                        file_put_contents('./uploads/shop/products/origin/' . $product['vendorCode'] . pathinfo($product['medium_image'], PATHINFO_BASENAME), $pic_data);
                    }
                }

                $data_new_prod[$prod_vars_count]          = [
                                                             'id'              => $prod_vars_count,
                                                             'active'          => 1,
                                                             'hot'             => $product['is_new'] != '0' ? 1 : 0,
                                                             'archive'         => $product['is_archive'] != '0' ? 1 : 0,
                                                             'external_id'     => $product['productID'],
                                                             'category_id'     => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                    //$pr_cat && $pr_cat !=null?$pr_cat:1,
                                                             'brand_id'        => array_key_exists($product['vendorID'], $exist_brands) ? $exist_brands[$product['vendorID']] : 0,
                    //$pr_brand && $pr_brand !=null?$pr_brand:null,//$product['vendorID']
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
                                                             'id'                => $prod_vars_count,
                                                             'name'              => $product['name'],
                                                             'locale'            => 'ru',
                                                             'full_description'  => $product['brief_description'],
                                                             'short_description' => $product['brief_description'],

                                                            ];
                $data_new_var[]                           = [
                                                             'id'                  => $prod_vars_count,
                                                             'product_id'          => $prod_vars_count,
                                                             'price_in_main'       => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah'],
                                                             'recommendable_price' => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : 0,
                                                             'retail_price_uah'    => $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0,
                                                             'currency'            => 1,
                    //$codeAndId[$product['currencyId']],
                                                             'number'              => $product['articul'],
                                                             'product_code_brain'  => $product['product_code'],
                                                             'stock'               => !empty($product['available']) ? max((array) $product['available']) : 0,
                                                             'external_id'         => $product['productID'],
                                                             'mainImage'           => $picture,
                                                             'is_brain'            => 1,
                                                            ];
                $data_new_var_i18[]                       = [
                                                             'id'     => $prod_vars_count,
                                                             'name'   => $product['name'],
                                                             'locale' => 'ru',
                                                            ];

                $cat_all_rpod_full_data[$prod_vars_count] = [
                                                             'product_code_brain' => $product['product_code'],
                                                             'category_id'        => array_key_exists($product['categoryID'], $exist_cats) ? $exist_cats[$product['categoryID']] : 1,
                                                            ];

                $this->result['products'][$prod_vars_count] = $prod_vars_count;

            } else {// Обновления продуктов
                $count_for_ext_prods++;
                $data_upd_prod[] = [
                                    'id'          => $exist_prods_ids[$product['productID']],
                                    'hot'         => $product['is_new'] != '0' ? 1 : 0,
                                    'archive'     => $product['is_archive'] != '0' ? 1 : 0,
                                    'external_id' => $product['productID'],
                                    'updated'     => time(),
                                   ];
                $data_upd_var[]  = [
                                    'id'                  => $exist_prods_ids[$product['productID']],
                                    'price_in_main'       => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : $product['retail_price_uah'],
                                    'recommendable_price' => $product['recommendable_price'] && $product['recommendable_price'] != '0' ? $product['recommendable_price'] : null,
                                    'retail_price_uah'    => $product['retail_price_uah'] && $product['retail_price_uah'] != '0' ? $product['retail_price_uah'] : 0,
                                    'currency'            => 1,
                    //$codeAndId[$product['currencyId']],
                                    'number'              => $product['articul'],
                                    'product_code_brain'  => $product['product_code'],
                                    'stock'               => !empty($product['available']) ? max((array) json_decode($product['available'])) : 0,
                    //                        'external_id' => $product['productID'],
                    //                        'mainImage' => $picture1,
                                   ];
            }
            unset($pr_key, $product);
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

            $rpods_full_data = $this->rpod_full_data($cat_all_rpod_full_data);

            $properties = $this->parseParam($rpods_full_data);

            list($prod_property_new, $prod_property_ex) = $properties;
            $this->fill_product_props_vals_n($prod_property_new, $prod_property_ex);

            $this->fill_prod_props_exist_vals_not_ext($prod_property_ex);
            $this->fill_prod_props_data($prod_property_new, $prod_property_ex);
            //                $this->fill_prod_props_exist_vals_exist($prod_property_ex);
        }

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

            $price_in_main       = 'price_in_main = (CASE id';
            $recommendable_price = 'recommendable_price = (CASE id';
            $retail_price_uah    = 'retail_price_uah = (CASE id';
            $currency            = 'currency = (CASE id';
            $number              = 'number = (CASE id';
            $product_code_brain  = 'product_code_brain = (CASE id';
            $stock               = 'stock = (CASE id';
            $ids_var             = '';

            foreach ($data_upd_var as $keys => $vals) {

                $price_in_main       .= " WHEN '" . $vals['id'] . "' THEN  '" . $vals['price_in_main'] . "' ";
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
            $recommendable_price .= 'END) ,';
            $retail_price_uah    .= 'END) ,';
            $currency            .= 'END) ,';
            $number              .= 'END) ,';
            $product_code_brain  .= 'END) ,';
            $stock               .= 'END) ';
            $ids_var             = rtrim($ids_var, ',');
            $zz                  = 'UPDATE shop_product_variants SET ' . $price_in_main . ' ' . $recommendable_price . ' ' . $retail_price_uah . ' ' . $currency . ' ' . $number . ' ' . $product_code_brain . ' ' . $stock . ' WHERE id IN (' . $ids_var . ')';

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

    public function rpod_full_data($products) {
        $some_count_prop = 0;
        foreach ($products as $key_pr_id => $product) {
            $some_count_prop++;

            $pr_data_brain     = (array) ParentBrain::getInstance()
                                                   ->curl_to_send(null, 'http://api.brain.com.ua/product/product_code/' . $product['product_code_brain'] . '/' . $this->auth_session . '?', 'get');
            $pr_data_brain_opt = (array) ParentBrain::getInstance()
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
            $update_product_description[$prod_id] = [
                                                     'full_description'  => $pr_data['0']['description'],
                                                     'short_description' => $pr_data['0']['brief_description'],
                                                    ];
            dd($pr_data);
            $options = $pr_data['options'];

            foreach ($options as $opt_key => $property_ob) {
                $property = (array) $property_ob;
                unset($property_ob);

                if (array_key_exists($property['OptionID'], $exist_props)) {
                    $prod_property_ex[$prod_id][$property['OptionID']]                                   = $property;//ИД продукта/ИД свойства /= [имя и значения свойства]
                    $new_prop_catsN[$exist_props[$property['OptionID']] . '-' . $pr_data['category_id']] = !array_key_exists($exist_props[$property['OptionID']] . '-' . $pr_data['category_id'], $exist_prop_cats) /*||
                    $exist_prop_cats[$exist_props[$by_csv]] != $pr_data['category_id']*/ ? [
                                                                                            'property_id' => $exist_props[$property['OptionID']],
                                                                                            'category_id' => $pr_data['category_id'],
                                                                                           ] : false;
                } else {
                    $props_count++;
                    $new_prop_cats[]                = [
                                                       'property_id' => $props_count,
                                                       'category_id' => $pr_data['category_id'],
                                                      ];
                    $data_cr_prop[$props_count]     = [
                                                       'id'            => $props_count,
                                                       'csv_name'      => translit_url(trim($property['OptionName'])),
                                                       'active'        => 1,
                                                       'show_on_site'  => 1,
                                                       'multiple'      => 0,
                                                       'main_property' => 1,
                                                       'external_id'   => $property['OptionID'],
                                                      ];
                    $data_cr_prop_18n[$props_count] = [
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

            $pr_sh_desc   = 'id = (CASE id';
            $pr_full_desc = 'id = (CASE id';
            $ids_prs      = '';
            foreach ($update_product_description as $key_pr => $val_pr) {
                $pr_sh_desc   .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['short_description'] . "' ";
                $pr_full_desc .= " WHEN '" . $key_pr . "' THEN  '" . $val_pr['full_description'] . "' ";
                $ids_prs      .= '"' . $key_pr . '",';
                unset($key_pr, $val_pr);
            }
            $pr_sh_desc   .= 'END), ';
            $pr_full_desc .= 'END) ';
            $ids_prs      = rtrim($ids_prs, ',');
            $desk_pr      = 'UPDATE shop_products SET ' . $pr_sh_desc . ' ' . $pr_full_desc . ' WHERE id IN (' . $ids_prs . ')';
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
                dump($props);

                foreach ($props as $csv => $prop) {
                    dump($prop);
                    dd($csv);

                    $props_vals_count++;

                    $new_val[$props_vals_count]     = [
                                                       'id'          => $props_vals_count,
                                                       'property_id' => $exist_props[$prop['OptionID']],
                                                       'external_id' => $prop['ValueID'],
                                                      ];
                    $new_val_i18[$props_vals_count] = [
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
                            $new_val[$props_vals_count]     = [
                                                               'id'          => $props_vals_count,
                                                               'property_id' => $exist_props[$prop['OptionID']],
                                                               'external_id' => $prop['ValueID'],
                                                              ];
                            $new_val_i18[$props_vals_count] = [
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
                if (!array_key_exists($prod_id . $exist_props[$props1['OptionID']] . $exist_prop_vals[$props1['ValueID']], $exist_prod_prop_vals_data)) {
                    $props_vals_data_count++;
                    $prod_prop_data_prod_id[$props_vals_data_count] = [
                                                                       'id'          => $props_vals_data_count,
                                                                       'property_id' => $exist_props[$props1['OptionID']],
                                                                       'product_id'  => $prod_id,
                                                                       'value_id'    => $exist_prop_vals[$props1['ValueID']],
                                                                      ];
                }
            }
            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data_prod_id);
        }
        if (count($prod_property_new) > 0) {

            foreach ($prod_property_new as $prod_id => $props1) {
                if (!array_key_exists($prod_id . $exist_props[$props1['OptionID']] . $exist_prop_vals[$props1['ValueID']], $exist_prod_prop_vals_data)) {
                    $props_vals_data_count++;
                    $prod_prop_data_prod_id[$props_vals_data_count] = [
                                                                       'id'          => $props_vals_data_count,
                                                                       'property_id' => $exist_props[$props1['OptionID']],
                                                                       'product_id'  => $prod_id,
                                                                       'value_id'    => $exist_prop_vals[$props1['ValueID']],
                                                                      ];
                }
            }
            $this->db->insert_batch('shop_product_properties_data', $prod_prop_data_prod_id);
        }
    }

    public function vendors_create() {
        //        $this->db->truncate('shop_brands');
        //        $this->db->truncate('shop_brands_i18n');

        //нужно в моделях пропела добавить для бренда external_id
        $vendors = ParentBrain::getInstance()
                              ->curl_to_send(null, 'http://api.brain.com.ua/vendors/' . $this->auth_session . '?', 'get');
        foreach ($vendors as $key => $vend) {
            $vendors_new[$vend->vendorID] = $vend;
            unset($key, $vend);
        }
        unset($vendors);
        $vendors_array = $vendors_new;
        unset($vendors_new);
        $brands_count = $this->db->query('SELECT id FROM shop_brands ORDER BY id DESC LIMIT 1')->result()['0']->id;
        foreach ($vendors_array as $k => $one_vendor) {

            $model = $this->db->where('external_id', $one_vendor->vendorID)->get('shop_brands')->row()->id;

            if ($model != null) {
            } else {
                $brands_count++;
                $data_cr_br[]     = [
                                     'id'          => $brands_count,
                                     'url'         => $one_vendor->vendorID . '-' . translit_url($one_vendor->name),
                                     'external_id' => (int) $one_vendor->vendorID,
                                     'created'     => time(),
                                     'updated'     => time(),
                                     'is_brain'    => 1,
                                    ];
                $data_cr_br_18n[] = [
                                     'id'     => $brands_count,
                                     'name'   => (string) trim($one_vendor->name),
                                     'locale' => 'ru',
                                    ];
            }
            unset($k, $one_vendor);
        }

        $this->db->insert_batch('shop_brands', $data_cr_br);
        $this->db->insert_batch('shop_brands_i18n', $data_cr_br_18n);
        unset($data_cr_br, $data_cr_br_18n);

    }

    public function cats_create($categories) {
        //        $this->db->truncate('shop_category');
        //        $this->db->truncate('shop_category_i18n');
        //        $this->db->query("DELETE FROM `route` WHERE `type` ='product'");
        //        $this->db->query("DELETE FROM `route` WHERE `type` ='shop_category'");
        //        $this->db->truncate('route');

        $cats_count = $this->db->query('SELECT id FROM shop_category ORDER BY id DESC LIMIT 1')->result()['0']->id;

        foreach ($categories as $k => $category) {

            $cats_count++;
            $is_exist_cat = $this->db->where('external_id', (int) $category['categoryID'])
                ->get('shop_category')
                ->row()->id;
            if ($is_exist_cat == null || $is_exist_cat == '') {
                $data_cr_cat[]     = [
                                      'id'           => $cats_count,
                                      'active'       => 1,
                                      'show_in_menu' => 1,
                                      'external_id'  => (int) $category['categoryID'],

                                      'created'      => time(),
                                      'updated'      => time(),
                                      'route_id'     => '',
                                      'is_brain'     => 1,
                                     ];
                $data_cr_cat_18n[] = [
                                      'id'     => $cats_count,
                                      'name'   => (string) $category['name'],
                                      'locale' => 'ru',
                                     ];

                $this->result['categories'][$cats_count] = [
                                                            'id'              => $cats_count,
                                                            'name'            => (string) $category['name'],
                                                            'external_id'     => (int) $category['categoryID'],
                                                            'parent_id_exter' => $category['parentID'] == '1' ? 0 : $category['parentID'],
                                                           ];
            } else {
                $categoryExistId       = $this->db->where('external_id', (int) $category['categoryID'])
                    ->get('shop_category')
                    ->row()->id;
                $categoryExistId_all[] = $categoryExistId;

                $data_upd_cat[]     = [
                                       'id'           => $categoryExistId,
                                       'active'       => 1,
                                       'show_in_menu' => 1,
                                       'updated'      => time(),
                                      ];
                $data_upd_cat_18n[] = [
                                       'id'     => $categoryExistId,
                                       'name'   => (string) $category['name'],
                                       'locale' => 'ru',
                                      ];

                $this->result['categories'][$categoryExistId] = [
                                                                 'id'              => $categoryExistId,
                                                                 'name'            => (string) $category['name'],
                                                                 'external_id'     => (int) $category['categoryID'],
                                                                 'parent_id_exter' => $category['parentID'] == '1' ? 0 : $category['parentID'],
                                                                ];
            }
            unset($k, $category);
        }
        unset($categories);
        $this->db->insert_batch('shop_category', $data_cr_cat);
        $this->db->insert_batch('shop_category_i18n', $data_cr_cat_18n);
        unset($data_cr_cat, $data_cr_cat_18n);

        if (count($categoryExistId_all) > 0) {

            $name   = 'name = (CASE id';
            $locale = 'locale = (CASE id';
            $ids    = '';

            foreach ($data_upd_cat_18n as $key => $val) {
                $name   .= " WHEN '" . $val['id'] . "' THEN  '" . $val['name'] . "' ";
                $locale .= " WHEN '" . $val['id'] . "' THEN '" . 'ru' . "' ";
                $ids    .= '"' . $val['id'] . '",';

                unset($key, $val);
            }
            $name   .= 'END),';
            $locale .= 'END) ';
            $ids    = rtrim($ids, ',');
            $z      = 'UPDATE shop_category_i18n SET ' . $name . ' ' . $locale . ' WHERE id IN (' . $ids . ')';
            $this->db->query($z);
            unset($categoryExistId_all, $data_upd_cat_18n);

        }

    }

    public function catsFix($categories1) {
        $categories = $categories1;

        unset($categories1);
        foreach ($categories as $id => $category) {

            $parent_id                    = (int) $category['parent_id_exter'] ? $this->db->where('external_id', (int) $category['parent_id_exter'])
                ->get('shop_category')
                ->row()->id : 0;
            $categories[$id]['parent_id'] = (int) $parent_id;

        }
        if (count($categories) > 0) {
            $categories2 = $this->getPathsAndParents($categories);
            unset($categories);
            $parent_id     = 'parent_id = (CASE id';
            $route_id      = 'route_id = (CASE id';
            $full_path_ids = 'full_path_ids = (CASE id';
            $ids           = '';

            foreach ($categories2 as $key => $val) {
                $parent_id     .= " WHEN '" . $val['id'] . "' THEN  '" . $val['parent_id'] . "' ";
                $route_id      .= " WHEN '" . $val['id'] . "' THEN '" . $val['route_id'] . "' ";
                $full_path_ids .= " WHEN '" . $val['id'] . "' THEN '" . $val['full_path_ids'] . "' ";
                $ids           .= '"' . $val['id'] . '",';

                unset($key, $val);
            }

            $parent_id     .= 'END),';
            $route_id      .= 'END), ';
            $full_path_ids .= 'END) ';
            $ids           = rtrim($ids, ',');
            $z             = 'UPDATE shop_category SET ' . $parent_id . ' ' . $route_id . ' ' . $full_path_ids . ' WHERE id IN (' . $ids . ')';
            $this->db->query($z);
            $this->create_full_rote_urlsN($categories2);
            unset($categories2);
        }

    }

    public function create_full_rote_urlsN($categories) {
        foreach ($categories as $cat_id => $parents) {
            unset($urls);
            $urls = $this->db->select('url')
                ->where('type', 'shop_category')
                ->where_in('entity_id', unserialize($parents['full_path_ids']))
                ->get('route');
            unset($full_rote_urls);
            if ($urls && $urls != null) {
                $urls = $urls->result_array();

                //                krsort($urls);

                foreach ($urls as $key => $url_1) {

                    //                    $full_rote_urls[] = $url_1['url'];
                    $full_rote_urls .= $urls[$key]['url'] . '/';
                    unset($key, $url_1);
                }

                //                $this->db->set('parent_url', implode('/', $full_rote_urls))->where('entity_id', $cat_id)->where('type', 'shop_category')->update('route');
                $this->db->set('parent_url', substr($full_rote_urls, 0, -1))
                    ->where('entity_id', $cat_id)
                    ->where('type', 'shop_category')
                    ->update('route');
                unset($full_rote_urls);
            }
            unset($urls, $cat_id, $parents);
        }

    }

    private function getPathsAndParents($categories11) {
        $categories = $categories11;

        foreach ($categories as $categoryId => $categoryData) {
            $categories1[$categoryData['id']] = $categoryData;
        }

        // creating id-paths and url-paths of each category
        foreach ($categories1 as $categoryId => $categoryData) {
            $currentPathIds = [];

            $neededCid = $categoryData['parent_id'];

            while ($neededCid != 0) {
                $currentPathIds[] = $neededCid;
                $neededCid        = $categories[$neededCid]['parent_id'];
            }
            $ext_rote = $this->db->where('entity_id', $categoryData['id'])
                ->where('type', 'shop_category')
                ->where('url', translit_url($categoryData['name']) . '-' . $categoryData['id'])
                ->get('route')
                ->result_array();

            if (!$ext_rote || count($ext_rote) == 0 || $ext_rote == null) {
                $parentUrl = $this->db->where('entity_id', $categoryData['parent_id'])
                    ->where('type', 'shop_category')
                    ->get('route')
                    ->result_array();

                $parentUrl1 = !empty($parentUrl) ? $parentUrl['0']['parent_url'] . '/' . $parentUrl['0']['url'] : '';

                $route = [
                          'parent_url' => $parentUrl1,
                          'url'        => translit_url($categoryData['name']) . '-' . $categoryData['id'],
                          'entity_id'  => $categoryData['id'],
                          'type'       => 'shop_category',
                         ];

                $this->db->insert('route', $route);
                $newRouteId = $this->db->insert_id();
                //                $this->db->update('shop_category', ['route_id' => $newRouteId], ['id' => $categoryData['id']]);
                $categories[$categoryId]['route_id'] = $newRouteId;
            } else {
                $parentUrl = $this->db->where('entity_id', $categoryData['parent_id'])
                    ->where('type', 'shop_category')
                    ->get('route')
                    ->result_array();

                $parentUrl1 = !empty($parentUrl) ? $parentUrl['0']['url'] : '';
                $route      = [
                               'parent_url' => $parentUrl1,
                               'url'        => translit_url($categoryData['name']) . '-' . $categoryData['id'],
                               'entity_id'  => $categoryData['id'],
                               'type'       => 'shop_category',
                              ];

                $this->db->update('route', $route, ['id' => $ext_rote['0']['id']]);
                //                $this->db->update('shop_category', ['route_id' => $ext_rote['0']['id']], ['id' => $categoryData['id']]);
                $categories[$categoryId]['route_id'] = $ext_rote['0']['id'];
            }
            $categories[$categoryId]['full_path_ids'] = serialize(array_reverse($currentPathIds));
            //            self::create_full_rote_urls($categoryId, array_reverse($currentPathIds));
            unset($categoryId, $categoryData);
        }
        unset($categories1);

        return $categories;
    }

    public function create_full_rote_urls($categoryId, $currentPathIds) {
        if (!empty($currentPathIds)) {
            $currentPathIds[max($currentPathIds)] = $categoryId;
            $urls                                 = $this->db->select('url')
                ->where('type', 'shop_category')
                ->where_in('entity_id', $currentPathIds)
                ->get('route')
                ->result_array();
            krsort($urls);
            foreach ($urls as $key => $url_1) {
                $full_rote_urls[] = $url_1['url'];
            }
            $this->db->set('parent_url', implode('/', $full_rote_urls))
                ->where('entity_id', $categoryId)
                ->where('type', 'shop_category')
                ->update('route');

        }

    }

    public function _install() {
        $this->load->dbforge();
        ($this->dx_auth->is_admin()) OR exit;
        $this->db->where('identif', 'parse_brain')->update(
            'components',
            [
             'settings' => '',
             'enabled'  => 1,
             'autoload' => 1,
            ]
        );

        $this->db->query("ALTER TABLE  `shop_product_variants` ADD  `is_brain` INT( 1 ) NOT NULL DEFAULT  '0'");
        $this->db->query('ALTER TABLE  `shop_product_variants` ADD  `product_code_brain` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
        $this->db->query('ALTER TABLE  `shop_product_variants` ADD  `recommendable_price` DOUBLE( 20, 5 ) NOT NULL');
        $this->db->query('ALTER TABLE  `shop_product_variants` ADD  `retail_price_uah` DOUBLE( 20, 5 ) NOT NULL ');

        $this->db->query("ALTER TABLE  `shop_products` ADD  `is_brain` INT( 1 ) NOT NULL DEFAULT  '0'");

        $this->db->query("ALTER TABLE  `shop_category` ADD  `is_brain` INT( 1 ) NOT NULL DEFAULT  '0'");

        $this->db->query("ALTER TABLE  `shop_brands` ADD  `is_brain` INT( 1 ) NOT NULL DEFAULT  '0'");
        $this->db->query('ALTER TABLE  `shop_brands` ADD  `external_id` VARCHAR( 255 ) NULL DEFAULT NULL');

        return true;
    }

    public function _deinstall() {

        $this->load->dbforge();
        ($this->dx_auth->is_admin()) OR exit;
    }

}

/*
  Родительский класс для XML обработчиков.
 */