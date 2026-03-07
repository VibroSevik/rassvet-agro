<?php
class ControllerCommonSearch extends Controller {
	public function index() {
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');

		if (isset($this->request->get['search'])) {
			$data['search'] = $this->request->get['search'];
		} else {
			$data['search'] = '';
		}

		// >>>>>> Nice Theme . Begin
		$data['categories_status'] = $this->config->get('theme_nice_search_categories_status');
		
		if (!$data['categories_status']) {
			goto search_categories_end;
		}
		
		$this->load->language('extension/theme/nice');
		
		$this->load->model('catalog/category');
		
		$data['categories'] = array();
		
		$data['categories'] = $this->cache->get('nice_search.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'));
		
		if ($data['categories']) {
			goto search_categories_end;
		}
		
		$categories = $this->model_catalog_category->getCategories(0);
		
		foreach ($categories as $category) {
			$children_data = array();

			$children = $this->model_catalog_category->getCategories($category['category_id']);
			
			foreach ($children as $child) {
				$grandchildren_data = array();

				$grandchildren = $this->model_catalog_category->getCategories($child['category_id']);
				
				foreach ($grandchildren as $grandchild) {
					$grandchildren_data[] = array(
						'name'  => $grandchild['name'],
						'category_id' => $grandchild['category_id'],
					);
				}
				
				$children_data[] = array(
					'name'  => $child['name'],
					'grandchildren' => $grandchildren_data,
					'category_id' => $child['category_id'],
				);
			}
		
			$data['categories'][] = array(
				'name' => $category['name'],
				'category_id' => $category['category_id'],
				'children' => $children_data,
			);
		}
		
		$this->cache->set('nice_search.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'), $data['categories']);
		
		search_categories_end:

		$data['selected_category_id'] = $this->request->get['category_id'] ?? 0;
		// <<<<<< Nice Theme . End

		return $this->load->view('common/search', $data);
	}
}