<?php

class ModelExtensionThemeNice extends Model {
	public function isInstalledPreviously() {
		$sql = "SELECT code FROM " . DB_PREFIX . "modification WHERE code = 'NiceThemeBySergeTkach'";
		$query = $this->db->query($sql);

		if ($query->num_rows > 0) {
			return true;
		}
		
		return false;
	}
	
	/*
	 * No addSettingValue method in setting model
	 */
	public function addSettingValue($code = '', $key = '', $value = '', $store_id = 0) {
		if (!is_array($value)) {
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', store_id = '" . (int)$store_id . "'");
		} else {
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', store_id = '" . (int)$store_id . "'");
		}
	}
	
	private function getLanguagesDummy() {
		$output = [];
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language");
		
		if ($query->num_rows > 0) {
			foreach ($query->rows as $language) {
				$otput[$language['language_id']] = '';
			}
		}
		
		return $output;
	}
	
	public function defaultValues() {
		$languages_dummy = $this->getLanguagesDummy();
		
		return [
			'theme_nice_custom_css' => '',
			'theme_nice_page_product_shortdescritipon_length' => '255',
			'theme_nice_productlist_cols_on_mobile' => '2',
			'theme_nice_productlist_grid_hover_effect' => '0',
			'theme_nice_productlist_price_font_weight' => 'regular',
			'theme_nice_productlist_name_font_weight' => 'bold',
			'theme_nice_productlist_description_on_mobile' => '0',
			'theme_nice_productlist_description' => '0',
			'theme_nice_productlist_image_margins' => '0',
			'theme_nice_home_banner_2_link' => $languages_dummy,
			'theme_nice_home_banner_2' =>  $languages_dummy,
			'theme_nice_home_banner_1_link' => $languages_dummy,
			'theme_nice_home_banner_1' =>  $languages_dummy,
			'theme_nice_home_banner_near_slideshow_status' => '0',
			'theme_nice_home_slideshow_height' => '570',
			'theme_nice_home_slideshow_width' => '1370',
			'theme_nice_home_slideshow_id' => '0',
			'theme_nice_home_slideshow_status' => '0',
			'theme_nice_search_categories_status' => '0',
			'theme_nice_multilang_logo_text' => $languages_dummy,
			'theme_nice_multilang_logo_text_icon' => '',
			'theme_nice_multilang_logo_image' => $languages_dummy,
			'theme_nice_multilang_logo_status' => '0',
			'theme_nice_mobile_logo_center' => '0',
			'theme_nice_menu_top_mobile_label' => $languages_dummy,
			'theme_nice_menu_top_status' => '0',
			'theme_nice_color_footer_bg' => '#525a5d',
			'theme_nice_color_accent__darker_3' => '#5859a0',
			'theme_nice_color_accent__darker_2' => '#4f508f',
			'theme_nice_color_accent__darker_1' => '#5859a0',
			'theme_nice_color_accent' => '#ea435d',
			'theme_nice_color_accent__lighter_1' => '#7677b4',
			'theme_nice_color_accent__lighter_2' => '#8788bd',
			'theme_nice_color_accent__lighter_3' => '#9798c6',
			'theme_nice_color_primary__darker_3' => '#5859a0',
			'theme_nice_color_primary__darker_2' => '#4f508f',
			'theme_nice_color_primary__darker_1' => '#5859a0',
			'theme_nice_color_primary' => '#6667ab',
			'theme_nice_color_primary__lighter_1' => '#7677b4',
			'theme_nice_color_primary__lighter_2' => '#8788bd',
			'theme_nice_color_primary__lighter_3' => '#9798c6',
			'theme_nice_image_location_height' => '50',
			'theme_nice_image_location_width' => '268',
			'theme_nice_image_cart_height' => '47',
			'theme_nice_image_cart_width' => '47',
			'theme_nice_image_wishlist_height' => '47',
			'theme_nice_image_wishlist_width' => '47',
			'theme_nice_image_compare_height' => '90',
			'theme_nice_image_compare_width' => '90',
			'theme_nice_image_related_height' => '80',
			'theme_nice_image_related_width' => '80',
			'theme_nice_image_additional_height' => '74',
			'theme_nice_image_additional_width' => '74',
			'theme_nice_image_product_height' => '228',
			'theme_nice_image_product_width' => '228',
			'theme_nice_image_thumb_height' => '228',
			'theme_nice_image_popup_width' => '500',
			'theme_nice_image_popup_height' => '500',
			'theme_nice_image_thumb_width' => '228',
			'theme_nice_image_manufacturer_height' => '80',
			'theme_nice_image_manufacturer_width' => '80',
			'theme_nice_image_category_height' => '80',
			'theme_nice_image_category_width' => '80',
			'theme_nice_product_description_length' => '100',
			'theme_nice_product_limit' => '15',
			'theme_nice_status' => '1',
			'theme_nice_directory' => 'nice',
			'theme_nice_subscribe_email' => '',
		];
	}
	
	public function defaultNewValuesIn_1_6_0() {
		$languages_dummy = $this->getLanguagesDummy();
		
		return [
			'theme_nice_menu_top_mobile_label' => $languages_dummy,
			'theme_nice_color_menu_bg' => '#6667ab',
			'theme_nice_color_menu_bg__mobile' => '#f8f8f8',
		];
	}
	
	
}