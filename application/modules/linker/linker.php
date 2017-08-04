<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Autor Sempai
 *
 * Linker Module
 */
class Linker extends MY_Controller {

    public function __construct() {
        parent::__construct();
		$lang = new MY_Lang();
        $lang->load('linker');
    }

	public function get_links($page_id)
	{
	
	$data = array(
	     'prev_link' => $this->prev_link($page_id),
		 'next_link' => $this->next_link($page_id)		 
    );
	
	$this->template->registerCssFile('/application/modules/linker/templates/css/style.css');
	$this->template->add_array($data); 
	$this->show_tpl('links');
	}
	
    private function prev_link($page_id) 
	{
        $content = $this->get_content($page_id);
	
		$this->db->where('category >', 0);
		$this->db->where('category', $content['category']);
		$this->db->where('lang', $content['lang']);
		$this->db->where('post_status', 'publish');
        $this->db->where('publish_date <', $content['publish_date']);
		$this->db->order_by('publish_date','DESC');
		$prev = $this->db->get('content')->row_array();

        if ($prev) {
        return $prev;
		} else {
		return FALSE;
		}
		
    }
	
	private function next_link($page_id) 
	{
        $content = $this->get_content($page_id);
	
		$this->db->where('category >', 0);
		$this->db->where('category', $content['category']);
		$this->db->where('lang', $content['lang']);
		$this->db->where('post_status', 'publish');
        $this->db->where('publish_date >', $content['publish_date']);
		$this->db->order_by('publish_date','ASC');
		$next = $this->db->get('content')->row_array();

		if ($next) {
        return $next;
		} else {
		return FALSE;
		}
    }
	
	private function get_content($page_id) 
	{
		return $this->db->where('id', $page_id)->get('content')->row_array();
    }

    public function _install() {
        if ($this->dx_auth->is_admin() == FALSE)
            exit;
    }

    public function _deinstall() {
        if ($this->dx_auth->is_admin() == FALSE)
            exit;
    }
	
	private function show_tpl($file = '') {
        $file = realpath(dirname(__FILE__)) . '/templates/public/' . $file . '.tpl';
        $this->template->display('file:' . $file);
    }
}

/* End of file linker.php */
