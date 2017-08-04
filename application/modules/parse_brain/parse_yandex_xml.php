<?php

use Category\CategoryApi;
use CMSFactory\ModuleSettings;
use Products\ProductApi;

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Parse_yandex_xml extends MY_Controller
{

    private $i         = 0;

    private $time;

    private $arr       = [];

    private $xml;

    private $countProp = 200;

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('parse_brain');
        $this->load->helper('translit');
        $this->time     = time();
        $this->settings = ModuleSettings::ofModule('parse_yandex_xml')->get();

        $cur_shop  = \Currency\Currency::create()->getCurrencies();
        $codeAndId = [];
        foreach ($cur_shop as $val) {
            $codeAndId[$val->getCode()] = $val->getId();
        }
        $this->codeAndId = $codeAndId;
        //        $this->xml=@fopen($this->settings['url']);
        //        $this->xml = new XMLReader();
        //       $this->xml = simplexml_load_string(file_get_contents($this->settings['url']));
        // $this->xml = simplexml_load_string(file_get_contents('http://796685.forimage.web.hosting-test.net/yandex_market.xml'));
    }

    public function vesta_alpha() {

        // $this->db->truncate('shop_category');
        // $this->db->truncate('shop_category_i18n');
        // $this->db->truncate('shop_brands');
        // $this->db->truncate('shop_brands_i18n');
        // $this->db->truncate('shop_products');
        // $this->db->truncate('shop_products_i18n');
        // $this->db->truncate('shop_product_variants');
        // $this->db->truncate('shop_product_variants_i18n');
        // $this->db->truncate('shop_product_categories');
        // $this->db->truncate('shop_product_images');

        // $this->db->truncate('shop_product_properties');
        // $this->db->truncate('shop_product_properties_i18n');
        // $this->db->truncate('shop_product_properties_categories');
        // $this->db->truncate('shop_product_properties_data');
        // $this->db->truncate('shop_product_property_value');
        // $this->db->truncate('shop_product_property_value_i18n');
        // $this->db->query("DELETE FROM `route` WHERE `type` ='product'");
        // // $this->db->query("DELETE FROM `route` WHERE `type` ='shop_category'");
        // exit();
        $site_url = substr($this->settings['url_oasis'], 1);
        if (!$this->input->post()) {
            // $site_url = substr($this->settings['url_oasis'], 1);//str_replace('/', '', substr(site_url(), -1));

            if (substr_count($this->settings['url_oasis'], 'http://') > 0 || substr_count($this->settings['url_oasis'], 'https://') > 0) {
                if ($this->settings['url_oasis'] /*and ! file_exists('./uploads/create_Yml_file.xml')*/) {
                    if (file_put_contents('./uploads/files/create_Yml_file_url_oasis.xml', file_get_contents($this->settings['url_oasis'])) == 0) {
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $this->settings['url_oasis']);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0');
                        $out = curl_exec($curl);

                        file_put_contents('./uploads/files/create_Yml_file_url_oasis.xml', $out);

                    }
                }

                $site_url = 'uploads/files/create_Yml_file_url_oasis.xml';
            }

            return true;
        }

        if (substr_count($this->settings['url_oasis'], 'http://') > 0 || substr_count($this->settings['url_oasis'], 'https://') > 0) {
            $site_url = 'uploads/files/create_Yml_file_url_oasis.xml';
        }

        include_once 'ConfigXMLReaderUrlOasis.php';

        $reader = new ConfigXMLReaderUrlOasis($site_url); //uploads/tekstil.xml
        // чтобы не тратить память на хранение ненужных элементов, мы их просто выбрасываем на каждой итерации
        $reader->onEvent(
            'afterParseElement',
            function ($name, $context) {
                $context->clearResult();
            }
        );
        // мы хотим получать только настройки наценок
        // эта анонимная функция(PHP5.3 и выше) будет вызвана сразу по завершению парсинга элементов <offer>
        $reader->onEvent(
            'parseOffer',
            function ($context) {
                $ratio = $context->getResult()['offers'][0];
            }
        );
        // запускаем парсинг

        $reader->parse($this->input->post());
        $parse_result = $reader->resultOfferAlfa;

        if ($this->input->post('cats') && !$this->input->post('products')) {
            $this->catsFixOasis($parse_result['categories'], $this->settings['CategoryIdOasis']);
            showMessage(lang('Создано ', '') . count($parse_result['new_categories']) . lang(' категорий, в файле было  ', '') . count($parse_result['categories']));

            return json_encode(
                [
                 lang('Создано ', '') . count($parse_result['new_categories']) . lang(' категорий, в файле было  ', '') . count($parse_result['categories']),
                 'g',
                 count($parse_result['all_offers']),
                ]
            );
        }

        if ($this->input->post('products')) {

            $count_new_offers = $this->create_naw_products($parse_result['all_offers'], $this->input->post('products'));
            $count_new_offers = count($count_new_offers);
            showMessage(lang('Создано ', '') . $count_new_offers . lang(' идет обработка файла  ', ''), 'g');

            return $count_new_offers;
            // return  json_encode(array(lang('Создано ', '').count($parse_result['new_offers']).lang(' идет обработка файла  ', ''), 'g', count($parse_result['all_offers'])));
        }

        //        $reader->close(); //???????????

        // foreach ($parse_result['offers'] as $key => $value) {
        //     foreach ($value['pictures'] as  $picture) {
        //         Image::create()->checkOriginFolder();
        //         /** Check images folders* */
        //         Image::create()->checkImagesFolders();
        //         /** Check watermarks folder */
        //         Image::create()->checkWatermarks();
        //         Image::create()->resizeByName([$value['vendorCode'].pathinfo($picture, PATHINFO_BASENAME)]);
        //     }
        // }

        $created_prods = $reader->resultOfferAlfa;
        $created_prods = count($created_prods['offers']);
        showMessage(lang('Создано ', '') . $created_prods . lang(' товаров ', ''));

        return json_encode(
            [
             lang('Создано ', '') . $created_prods . lang(' товаров ', ''),
             'g',
            ]
        );

        return $created_prods;

    }

    private function create_naw_products($all_offers, $count_post) {

        $codeAndId = $this->codeAndId;
        array_splice($all_offers, $count_post);
        if ($count_post > 150) {
            array_splice($all_offers, 0, $count_post - 51);
        }

        // for ($i = (int)$count_post, $total_prods_c = count($all_offers);   $i < $total_prods_c, $i > (int)$count_post-50, $i <= (int)$count_post; $i++) {
        foreach ($all_offers as $i => $offer) {

            $product = $all_offers[$i];

            if ($product['vendorCode'] == null || $product['vendorCode'] == '') {
                $product['vendorCode'] = $product['id'];
            }
            if ($product['prod_name'] == null || $product['prod_name'] == '') {
                $product['prod_name'] = $product['name'];
            }
            if ($product['var_name'] == null || $product['var_name'] == '') {
                $product['var_name'] = $product['name'];
            }

            $pID = $this->db->select('id as id, product_id as pr_id')
                ->where('number', $product['vendorCode'])
                ->get('shop_product_variants')
                ->result_array();

            if (!$pID || count($pID) == 0 || $pID == null) {
                $vendorCode = translit_url($product['vendorCode']);
                // if($i>count($all_offers) - 300){
                foreach ($product['pictures'] as $key => $val) {

                    if ($key == 0) {
                        $picture1 = !file_exists('./uploads/shop/products/origin/' . $vendorCode . pathinfo($val, PATHINFO_BASENAME)) ? 'false' : '1';//pathinfo($val, PATHINFO_BASENAME) ;

                        if ($picture1 == 'false') {
                            $pic_data = file_get_contents($val);

                            if ($pic_data) {

                                file_put_contents('./uploads/shop/products/origin/' . $vendorCode . pathinfo($val, PATHINFO_BASENAME), $pic_data);
                            } else {
                                $product['pictures']['0'] = null;
                            }
                        }
                    } else {
                        $picture1 = !file_exists('./uploads/shop/products/origin/additional/' . $vendorCode . pathinfo($val, PATHINFO_BASENAME)) ? 'false' : '1';//pathinfo($val, PATHINFO_BASENAME) ;
                        if ($picture1 == 'false') {
                            $pic_data = file_get_contents($val);
                            if ($pic_data) {
                                file_put_contents('./uploads/shop/products/origin/additional/' . $vendorCode . pathinfo($val, PATHINFO_BASENAME), $pic_data);
                            } else {
                                $product['pictures'][$key] = null;
                            }
                        }
                    }
                }
                // }

                $categ_id              = $this->db->where('external_id', $product['categoryId'] . $product['categoryId'])
                    ->get('shop_category')
                    ->row()->id;
                $product['pr_id_main'] = $product['pr_id_main'] != null ? $product['pr_id_main'] . $product['pr_id_main'] : $product['vendorCode'];

                $data = [
                         'product_name'     => htmlspecialchars_decode(htmlspecialchars_decode(trim($product['prod_name']))),
                         'active'           => 1,
                    // $product['active'] ==''?0:1,
                         'variant_name'     => htmlspecialchars_decode(htmlspecialchars_decode(trim($product['var_name']))),
                         'price_in_main'    => $product['price'],
                         'currency'         => $codeAndId[$product['currencyId']] ?: $codeAndId['RUR'],
                         'number'           => $product['vendorCode'],
                         'stock'            => $product['stock'],
                         'external_id'      => $product['pr_id_main'] . $product['pr_id_main'],
                         'mainImage'        => $product['pictures']['0'] != null ? $vendorCode . pathinfo($product['pictures']['0'], PATHINFO_BASENAME) : null,
                         'category_id'      => $categ_id ?: $this->settings['CategoryIdOasis'],
                         'brand_id'         => $product['vendor'] ? $this->createBrand($product['vendor']) : '',
                         'full_description' => html_entity_decode($product['description']),
                    // 'url' => translit_url(str_replace(['.html', '.htm', ' '], '', end(explode('/', parse_url($product['url'], PHP_URL_PATH))))) /* . '-' . $product['vendorCode']) */ . $product['id'],
                         'url'              => translit_url($product['name'] . '-' . $product['id'] . '-' . $product['vendorCode']),
                         'enable_comments'  => 1,
                         'created'          => time(),
                         'updated'          => time(),
                        ];

                //     $pr_id_main = $this->db
                //         ->select('id')
                //         ->where('external_id', $product['pr_id_main'])
                //         ->distinct()
                //         ->get('shop_products');

                // if ($pr_id_main->result_array() && count($pr_id_main->result_array()) >0/*== 1*/) {
                //     $pr_id_main = $pr_id_main->result_array()['0'];

                //     $varDatas = [
                //         'number' => $product['vendorCode'],
                //         'stock' => $product['stock'],
                //         'currency' => $codeAndId[$product['currencyId']] ? $codeAndId[$product['currencyId']] : $codeAndId['RUR'],
                //         'price_in_main' => $product['price'],
                //         'variant_name' => htmlspecialchars_decode(htmlspecialchars_decode(trim($product['var_name']))),
                //     ];
                //     /** Set product variants mainImage name uploaded from computer or internet */
                //     $varDatas['mainImage'] = $vendorCode . pathinfo($product['pictures']['0'], PATHINFO_BASENAME);
                //     ProductApi::getInstance()->addVariant($pr_id_main['id'], $varDatas);
                // } else {

                $model2 = ProductApi::getInstance()->addProduct($data, 'ru');
                if (ProductApi::getInstance()->getError()) {

                    // dump($product['name']);
                    // dd(ProductApi::getInstance()->getError());
                } else {

                    $this->db->where('product_id', $model2->getId())->delete('shop_product_images');
                    $i = 1;
                    if (count($product['pictures']) > 1) {
                        unset($product['pictures']['0']);
                        foreach ($product['pictures'] as $imageName) {
                            if ($imageName != null) {
                                ProductApi::getInstance()
                                          ->getInstance()
                                          ->saveProductAdditionalImage($model2->getId(), $vendorCode . pathinfo($imageName, PATHINFO_BASENAME), $i++);
                            }
                        }
                        unset($product['pictures']);
                    }
                    if (!empty($product['params'])) {
                        foreach ($product['params'] as $pr_name => $pr_val) {
                            $pr_id_val = $this->parseParamFtp($pr_name, $pr_val);
                            $data1     = [
                                          'property_id' => $pr_id_val[0],
                                          'category_id' => $data['category_id'],
                                         ];
                            $prop_cat  = $this->db->where($data1)->get('shop_product_properties_categories');
                            if (!$prop_cat->result_array()) {
                                $this->db->insert('shop_product_properties_categories', $data1);
                                // ->get('shop_product_properties_categories');
                            }
                            $property      = SPropertiesQuery::create()->findOneById($pr_id_val[0]);
                            $propertyValue = SPropertyValueQuery::create()
                                                                ->joinWithI18n('ru')
                                                                ->useI18nQuery('ru')
                                                                ->filterByValue($pr_val)
                                                                ->endUse()
                                                                ->findOneByPropertyId($pr_id_val[0]);
                            $id            = false;
                            if (!$property) {
                            } elseif ($propertyValue !== null) {
                                $pr_val_id = $propertyValue->getId();
                            } else {
                                $propertyValue = new SPropertyValue();
                                $propertyValue->setLocale('ru')
                                    ->setPropertyId($pr_id_val[0])
                                    ->setValue($pr_val)
                                    ->save();
                                $pr_val_id = $propertyValue->getId();
                            }
                            ProductApi::getInstance()
                                      ->setProductPropertyValue($model2->getId(), $pr_id_val[0], $pr_val_id, 'ru');
                            // $pr_id_vals[$pr_id_val[0]]=$pr_id_val;
                            unset($product['params'][$pr_name]);
                        }
                    }
                    // }

                }
                $all_offers[$i] = serialize($all_offers[$i]);
                unset($all_offers[$i], $product);
            }
        }
    }

    private function createBrand($name) {
        if ($name == null) {
            return null;
        }
        $model = $this->db->where('name', $name)->get('shop_brands_i18n')->row()->id;
        if ($model != null) {
            return $model;
        }

        $this->db->set('url', translit_url($name))
            ->set('created', time())
            ->set('updated', time())
            ->insert('shop_brands');
        if ($this->db->_error_message()) {
            return $this->db->_error_message();
        }
        $id = $this->db->insert_id();
        $this->db->set('id', $id)->set('name', $name)->set('locale', 'ru')->insert('shop_brands_i18n');

        return $id;
    }

    protected function parseParamFtp($pr_name, $pr_val) {

        $prop_exist   = SPropertiesQuery::create()//->findByExternalID($key_pr);
                                        ->findByCsvName(trim(str_replace(' ', '_', translit_url($pr_name))));
        $truePropData = [];

        if ($prop_exist->getData()['0'] == null && $pr_val != '') {
            // $_POST['Name'] =$pr_name;
            // $truePropData['Name'] = $pr_name;
            // $truePropData['Locale'] = \MY_Controller::getDefaultLanguage();
            $truePropData['CsvName']      = trim(str_replace(' ', '_', translit_url($pr_name)));
            $truePropData['Active']       = '1';
            $truePropData['ShowOnSite']   = '1';
            $truePropData['ShowInFilter'] = '0';
            $truePropData['MainProperty'] = '1';
            // $truePropData['Description'] = '';
            $truePropData['ExternalId'] = '';
            // $truePropData['property_value'] = '';

            if ($truePropData['CsvName']) {
                $model = new SProperties;
                $model->fromArray($truePropData);
                $model->save();

                $this->db->set('id', $model->getId())
                    ->set('name', $pr_name)
                    ->set('locale', \MY_Controller::getDefaultLanguage()['identif'])
                    ->insert('shop_product_properties_i18n');

            }

            return [
                    $model->getId(),
                    $pr_val,
                   ];
        }

        return [
                $prop_exist->getData()['0']->id,
                $pr_val,
               ];

    }

    public function catsFixOasis($categories, $categoryId) {
        $k = 0;
        foreach ($categories as $category) {

            $k++;
            // $url = translit_url(trim((string)$category['name'].'-'.$category['id']));
            if ($this->db->where('external_id', $category['id'] . $category['id'])->get('shop_category')->row()->id) {

                $exist_cat = $this->db->where('external_id', $category['id'] . $category['id'])
                    ->get('shop_category')
                    ->result_array();
                $image     = $exist_cat['0']['image'];
                $tpl       = $exist_cat['0']['tpl'];
                $url       = translit_url(trim((string) $category['name'] . '-' . $category['id'] . $category['id']));
            }

            $data = [
                     'url'          => $url,
                     'active'       => 1,
                     'show_in_menu' => 1,
                     'parent_id'    => $category['parentId'] ? $this->db->where('external_id', $category['parentId'] . $category['parentId'])
                         ->get('shop_category')
                         ->row()->id : $categoryId,
                     'external_id'  => $category['id'] . $category['id'],
                     'name'         => (string) $category['name'],
                     'created'      => time(),
                     'updated'      => time(),
                     'image'        => $image,
                     'tpl'          => $tpl,
                    ];
            /* @var $model SCategory */
            $model = CategoryApi::getInstance()
                                ->updateCategory(
                                    $this->db->where('external_id', $category['id'] . $category['id'])
                                        ->get('shop_category')
                                        ->row()->id,
                                    $data
                                );

            if (CategoryApi::getInstance()->getError()) {
                dump(CategoryApi::getInstance()->getError() . $data['url']);
                //                $this->lib_admin->log('YML create category error' . CategoryApi::getInstance()->getError() . ', ' . var_export($data, true));

            }
        }
    }

    public function catsFixVesta($categories, $categoryId) {

        foreach ($categories as $category) {

            $url = translit_url(trim((string) $category['name'] . '-' . $category['id']));
            // if (/*in_array(translit_url((string) $category)) or */
            // $this->db->where('url', translit_url(trim((string)$category['name'].'-'.$category['id'])))->get('shop_category')->row()->url
            // ) {
            //     $url = $url . (int)$category['id'];
            //     $exist_cat = $this->db->where('url', translit_url(trim((string)$category['name'].'-'.$category['id'])))->get('shop_category')->result_array();
            //     $image = $exist_cat['0']['image'];
            //     $tpl = $exist_cat['0']['tpl'];
            // }

            $data = [
                     'url'         => $url,
                     'active'      => 1,
                     'parent_id'   => $category['parentId'] ? $this->db->where('external_id', $category['parentId'] . $category['parentId'])
                         ->get('shop_category')
                         ->row()->id : $categoryId,
                     'external_id' => $category['id'],
                     'name'        => (string) $category['name'],
                     'created'     => time(),
                     'updated'     => time(),
                     'image'       => $image,
                     'tpl'         => $tpl,
                    ];
            /* @var $model SCategory */
            $model = CategoryApi::getInstance()
                                ->updateCategory(
                                    $this->db->where('external_id', $category['id'] . $category['id'])
                                        ->get('shop_category')
                                        ->row()->id,
                                    $data
                                );

            if (CategoryApi::getInstance()->getError()) {
                dump(CategoryApi::getInstance()->getError() . $data['url']);
                //                $this->lib_admin->log('YML create category error' . CategoryApi::getInstance()->getError() . ', ' . var_export($data, true));

            }
        }
    }

    /**
     * @return CI_User_agent
     */
    public function getAgent() {
        $pr_url = $this->db->select('url')->get('shop_products')->result_array();
        foreach ($pr_url as $url) {
            //            $this->db->where('trash_url', 'shop/product/' . $url['url'])->delete('trash');
            $new_url = '/shop/product/' . $url['url'];
            $urlN    = $url['url'];

            //            $this->db->query('UPDATE trash SET `trash_redirect` = ' . $new_url .'  WHERE (`trash_url`  LIKE  '%$urlN%') ');

            $sql = $this->db->query("UPDATE trash SET `trash_redirect` = '$new_url'  WHERE (`trash_url`  LIKE  '%$urlN%') ");

            //            dd($this->db->_error_message());
        }
        dd($pr_url);
    }

    public function _install() {
        $this->load->dbforge();
        ($this->dx_auth->is_admin()) OR exit;
        $this->db->where('identif', 'parse_yandex_xml')->update(
            'components',
            [
             'settings' => '',
             'enabled'  => 1,
             'autoload' => 1,
            ]
        );

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