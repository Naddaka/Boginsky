<?php

namespace parse_brain\classes;

/**
 * Image CMS
 *
 * @property \DX_Auth             $dx_auth
 * @property \CI_URI              $uri
 * @property \CI_DB_active_record $db
 * @property \CI_Input            $input
 * @version 1.0 big start!
 */
class ParentBrain extends \MY_Controller
{

    protected static $_instance;

    public function __construct() {

        parent::__construct();
        $this->load->module('core');

        $lang = new \MY_Lang();
    }

    public static function getInstance() {

        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

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

    public function xml_to_object($xml) {
        //моя функция http://php.net/manual/ru/function.xml-parse-into-struct.php
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        $elements = [];  // the currently filling [child] XmlElement array
        $stack    = [];
        foreach ($tags as $tag) {
            $index = count($elements);
            if ($tag['type'] == 'complete' || $tag['type'] == 'open') {
                $elements[$index]             = new XmlElement();
                $elements[$index]->name       = $tag['tag'];
                $elements[$index]->attributes = $tag['attributes'];
                $elements[$index]->content    = $tag['value'];
                if ($tag['type'] == 'open') {  // push
                    $elements[$index]->children = [];
                    $stack[count($stack)]       = &$elements;
                    $elements                   = &$elements[$index]->children;
                }
            }
            if ($tag['type'] == 'close') {  // pop
                $elements = &$stack[count($stack) - 1];
                unset($stack[count($stack) - 1]);
            }
        }

        return $elements[0]; //$elements[0];
    }

    public function xml2ary(&$string) {
        // ф-я с http://forum.php.su/topic.php?forum=60&topic=635
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $string, $vals, $index);
        xml_parser_free($parser);

        $mnary = [];
        $ary   = &$mnary;
        foreach ($vals as $r) {
            $t = $r['tag'];
            if ($r['type'] == 'open') {
                if (isset($ary[$t])) {
                    if (isset($ary[$t][0])) {
                        $ary[$t][] = [];
                    } else {
                        $ary[$t] = [
                                    $ary[$t],
                                    [],
                                   ];
                    }
                    $cv = &$ary[$t][count($ary[$t]) - 1];
                } else {
                    $cv = &$ary[$t];
                }
                if (isset($r['attributes'])) {
                    foreach ($r['attributes'] as $k => $v) {
                        $cv['_a'][$k] = $v;
                    }
                }
                $cv       = [];
                $cv['_p'] = &$ary;
                $ary      = &$cv;
            } elseif ($r['type'] == 'complete') {
                if (isset($ary[$t])) { // same as open
                    if (isset($ary[$t][0])) {
                        $ary[$t][] = [];
                    } else {
                        $ary[$t] = [
                                    $ary[$t],
                                    [],
                                   ];
                    }
                    $cv = &$ary[$t][count($ary[$t]) - 1];
                } else {
                    $cv = &$ary[$t];
                }
                if (isset($r['attributes'])) {
                    foreach ($r['attributes'] as $k => $v) {
                        $cv['_a'][$k] = $v;
                    }
                }
                $cv = (isset($r['value']) ? $r['value'] : '');
            } elseif ($r['type'] == 'close') {
                $ary = &$ary['_p'];
            }
        }

        $this->_del_p($mnary);

        return $mnary;
    }

    // _Internal: Remove recursion in result array
    public function _del_p(&$ary) {
        foreach ($ary as $k => $v) {
            if ($k === '_p') {
                unset($ary[$k]);
            } elseif (is_array($ary[$k])) {
                $this->_del_p($ary[$k]);
            }
        }
    }

}

class XmlElement
{

    public $name;

    public $attributes;

    public $content;

    public $children;

}