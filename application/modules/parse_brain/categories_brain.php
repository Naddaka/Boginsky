<?php

use CMSFactory\ModuleSettings;
use parse_brain\classes\ParentBrain;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Categories_brain extends MY_Controller
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

        //        $products_cats_obj = \parse_brain\classes\ParentBrain::getInstance()->curl_to_send(null, 'http://api.brain.com.ua/products/1181'. '/' . $this->auth_session , 'get');
        //        dump($products_cats_obj);
        //        $products_cat = (array)$products_cats_obj->list;
        //
        //        dd($products_cat);

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

    /**
     * @return string
     */
    public function cats_create() {

        $categories = ParentBrain::getInstance()
                                 ->curl_to_send(null, 'http://api.brain.com.ua/categories/' . $this->auth_session . '?', 'get');

        $categories_for_prods1 = $categories;
        unset($categories);
        foreach ($categories_for_prods1 as $key => $cat) {
            $categories_for_prods[$cat->categoryID] = (array) $cat;
            unset($key, $cat);
        }
        unset($categories_for_prods1);

        $this->cats_create_full($categories_for_prods);

        if ($this->result['categories'] != null) {
            $new_cats = $this->result['categories'];

            unset($this->result['categories']);
            $this->catsFix($new_cats);

        }

        $this->vendors_create_1();

        showMessage(lang('Создано ', '') . count($new_cats) . lang(' категорий, в файле было  ', '') . count($categories_for_prods));

        return json_encode(
            [
             lang('Создано ', '') . count($new_cats) . lang(' категорий, в файле было  ', '') . count($categories_for_prods),
             'g',
             count($new_cats),
            ]
        );
    }

    public function vendors_create_1() {
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

            if ($model == null) {
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
            unset($categories2);
            $parent_id     .= 'END),';
            $route_id      .= 'END), ';
            $full_path_ids .= 'END) ';
            $ids           = rtrim($ids, ',');
            $z             = 'UPDATE shop_category SET ' . $parent_id . ' ' . $route_id . ' ' . $full_path_ids . ' WHERE id IN (' . $ids . ')';
            $this->db->query($z);

        }

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

    private function cats_create_full($categories) {
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
                //                $categoryExistId = $this->db->where('external_id', (int)$category['categoryID'])->get('shop_category')->row()->id;
                //                $categoryExistId_all[] = $categoryExistId;
                //
                //
                //                $data_upd_cat[] = [
                //                    'id' => $categoryExistId,
                //                    'active' => 1,
                //                    'show_in_menu' => 1,
                //                    'updated' => time(),
                //                ];
                //                $data_upd_cat_18n[] = [
                //                    'id' => $categoryExistId,
                //                    'name' => (string)$category['name'],
                //                    'locale' => 'ru'
                //                ];
                //
                //                $this->result['categories'][$categoryExistId] = [
                //                    'id' => $categoryExistId,
                //                    'name' => (string)$category['name'],
                //                    'external_id' => (int)$category['categoryID'],
                //                    'parent_id_exter' => $category['parentID'] == '1' ? 0 : $category['parentID']
                //                ];
            }
            unset($k, $category);
        }
        unset($categories);
        $this->db->insert_batch('shop_category', $data_cr_cat);
        $this->db->insert_batch('shop_category_i18n', $data_cr_cat_18n);
        unset($data_cr_cat, $data_cr_cat_18n);

        //        if (count($categoryExistId_all) > 0) { // обновления категорий
        //
        //            $name = 'name = (CASE id';
        //            $locale = 'locale = (CASE id';
        //            $ids = '';
        //
        //            foreach ($data_upd_cat_18n as $key => $val) {
        //                $name .= " WHEN '" . $val['id'] . "' THEN  '" . $val['name'] . "' ";
        //                $locale .= " WHEN '" . $val['id'] . "' THEN '" . 'ru' . "' ";
        //                $ids .= '"' . $val['id'] . '",';
        //
        //                unset($key, $val);
        //            }
        //            $name .= 'END),';
        //            $locale .= 'END) ';
        //            $ids = rtrim($ids, ',');
        //            $z = "UPDATE shop_category_i18n SET " . $name . ' ' . $locale . " WHERE id IN (" . $ids . ")";
        //            $this->db->query($z);
        //            unset($categoryExistId_all);
        //            unset($data_upd_cat_18n);
        //
        //        }

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
            $this->create_full_rote_urls($categoryId, array_reverse($currentPathIds));
            unset($categoryId, $categoryData);
        }
        unset($categories1);

        return $categories;
    }

}

/*
  Родительский класс для XML обработчиков.
 */