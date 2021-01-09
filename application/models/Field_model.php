<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Field_model extends CI_Model
{
    //input values
    public function input_values()
    {
        $data = array(
            'row_width' => $this->input->post('row_width', true),
            'is_required' => $this->input->post('is_required', true),
            'status' => $this->input->post('status', true),
            'field_order' => $this->input->post('field_order', true)
        );
        return $data;
    }

    //add field
    public function add_field()
    {
        $data = $this->input_values();
        if (empty($data["is_required"])) {
            $data["is_required"] = 0;
        }
        //generate filter key
        $field_name = $this->input->post('name_lang_' . $this->selected_lang->id, true);
        $key = str_slug($field_name);

        //check filter key exists
        $row = $this->get_field_by_filter_key($key);
        if (!empty($row)) {
            $key = 'q_' . $key;
            $row = $this->get_field_by_filter_key($key);
            if (!empty($row)) {
                $key = $key . rand(1, 999);
            }
        }
        $data['product_filter_key'] = $key;
        $data['field_type'] = $this->input->post('field_type', true);

        $name_array = array();
        foreach ($this->languages as $language) {
            $item = array(
                'lang_id' => $language->id,
                'name' => $this->input->post('name_lang_' . $language->id, true)
            );
            array_push($name_array, $item);
        }
        $data['name_array'] = serialize($name_array);

        return $this->db->insert('custom_fields', $data);
    }

    //update field
    public function update_field($id)
    {
        $data = $this->input_values();
        if (empty($data["is_required"])) {
            $data["is_required"] = 0;
        }

        $name_array = array();
        foreach ($this->languages as $language) {
            $item = array(
                'lang_id' => $language->id,
                'name' => $this->input->post('name_lang_' . $language->id, true)
            );
            array_push($name_array, $item);
        }
        $data['name_array'] = serialize($name_array);

        $this->db->where('id', $id);
        return $this->db->update('custom_fields', $data);
    }

    //add field option
    public function add_field_option($field_id)
    {
        $main_option = $this->input->post('option_lang_' . $this->selected_lang->id, true);
        $data = array(
            'field_id' => $field_id,
            'option_key' => str_slug($main_option)
        );
        if ($this->db->insert('custom_fields_options', $data)) {
            $last_id = $this->db->insert_id();
            //add names
            foreach ($this->languages as $language) {
                $option = $this->input->post('option_lang_' . $language->id, true);
                $item = array(
                    "option_id" => $last_id,
                    "lang_id" => $language->id,
                    "option_name" => trim($option)
                );
                $this->db->insert('custom_fields_options_lang', $item);
            }
        }
        return true;
    }

    //update field option
    public function update_field_option()
    {
        $id = $this->input->post('id', true);
        $field_option = $this->get_field_option($id);
        if (!empty($field_option)) {
            $main_option = $this->input->post('option_lang_' . $this->selected_lang->id, true);
            $data = array(
                'option_key' => str_slug($main_option)
            );
            $this->db->where('id', $field_option->id);
            if ($this->db->update('custom_fields_options', $data)) {
                //delete old names
                $this->db->where('option_id', $field_option->id)->delete('custom_fields_options_lang');
                //add names
                foreach ($this->languages as $language) {
                    $option = $this->input->post('option_lang_' . $language->id, true);
                    $item = array(
                        "option_id" => $field_option->id,
                        "lang_id" => $language->id,
                        "option_name" => trim($option)
                    );
                    $this->db->insert('custom_fields_options_lang', $item);
                }
            }
        }
    }

    //get field
    public function get_field($id)
    {
        $this->db->where('id', clean_number($id));
        return $this->db->get('custom_fields')->row();
    }

    //get field by filter key
    public function get_field_by_filter_key($filter_key)
    {
        $this->db->where('product_filter_key', $filter_key);
        return $this->db->get('custom_fields')->row();
    }

    //get fields
    public function get_fields()
    {
        $this->db->order_by('field_order');
        return $this->db->get('custom_fields')->result();
    }

    //get custom fields by category
    public function get_custom_fields_by_category($category_id)
    {
        $categories = get_parent_categories_tree($category_id, true);
        $category_ids = array();
        if (!empty($categories)) {
            $category_ids = get_ids_from_array($categories);
        }
        if (!empty($category_ids)) {
            $this->db->join('custom_fields_category', 'custom_fields_category.field_id = custom_fields.id');
            $this->db->select('custom_fields.*, custom_fields_category.category_id AS category_id');
            $this->db->where('custom_fields.status', 1);
            $this->db->where_in('custom_fields_category.category_id', $category_ids);
            $this->db->order_by('custom_fields.field_order');
            return $this->db->get('custom_fields')->result();
        }
        return array();
    }

    //get custom filters
    public function get_custom_filters($category_id = null)
    {
        $categories = get_parent_categories_tree($category_id, true);
        $category_ids = array();
        if (!empty($categories)) {
            $category_ids = get_ids_from_array($categories);
        }
        if (!empty($category_ids)) {
            $this->db->join('custom_fields_category', 'custom_fields_category.field_id = custom_fields.id');
            $this->db->where_in('custom_fields_category.category_id', $category_ids);
        }
        $this->db->select('custom_fields.*');
        $this->db->where('custom_fields.status', 1)->where('custom_fields.is_product_filter', 1);
        $this->db->order_by('custom_fields.field_order');
        return $this->db->get('custom_fields')->result();
    }

    //get field categories
    public function get_field_categories($field_id)
    {
        $this->db->where('field_id', clean_number($field_id));
        return $this->db->get('custom_fields_category')->result();
    }

    //get field options
    public function get_field_options($custom_field, $lang_id)
    {
        if (!empty($custom_field)) {
            $this->db->select('custom_fields_options.*');
            $this->db->select('(SELECT option_name FROM custom_fields_options_lang WHERE custom_fields_options.id = custom_fields_options_lang.option_id AND custom_fields_options_lang.lang_id =  ' . clean_number($lang_id) . ' LIMIT 1) AS option_name');
            if (item_count($this->languages) > 1) {
                $this->db->select('(SELECT option_name FROM custom_fields_options_lang WHERE custom_fields_options.id = custom_fields_options_lang.option_id AND custom_fields_options_lang.lang_id !=  ' . clean_number($lang_id) . ' LIMIT 1) AS second_name');
            }
            $this->db->where('custom_fields_options.field_id', clean_number($custom_field->id));
            if ($custom_field->sort_options == 'date') {
                $this->db->order_by('custom_fields_options.id');
            }
            if ($custom_field->sort_options == 'date_desc') {
                $this->db->order_by('custom_fields_options.id', 'DESC');
            }
            if ($custom_field->sort_options == 'alphabetically') {
                $this->db->order_by('option_name');
            }
            return $this->db->get('custom_fields_options')->result();
        }
        return array();
    }

    //update field options settings
    public function update_field_options_settings()
    {
        $field_id = $this->input->post('field_id', true);
        $data = array(
            'sort_options' => $this->input->post('sort_options', true)
        );
        $this->db->where('id', clean_number($field_id));
        return $this->db->update('custom_fields', $data);
    }

    //get field all options
    public function get_field_all_options($field_id)
    {
        $this->db->select('custom_fields_options.*');
        foreach ($this->languages as $language) {
            $this->db->select('(SELECT option_name FROM custom_fields_options_lang WHERE custom_fields_options.id = custom_fields_options_lang.option_id AND custom_fields_options_lang.lang_id =  ' . clean_number($language->id) . ' LIMIT 1) AS option_name_' . clean_number($language->id));
        }
        $this->db->where('custom_fields_options.field_id', clean_number($field_id));
        return $this->db->get('custom_fields_options')->result();
    }

    //get field option
    public function get_field_option($option_id)
    {
        $this->db->where('id', clean_number($option_id));
        return $this->db->get('custom_fields_options')->row();
    }

    //add category to field
    public function add_category_to_field()
    {
        $field_id = clean_number($this->input->post("field_id"));
        $category_id = get_dropdown_category_id();
        $row = $this->get_category_field($field_id, $category_id);
        if (empty($row)) {
            $data = array(
                'field_id' => $field_id,
                'category_id' => $category_id
            );
            return $this->db->insert('custom_fields_category', $data);
        }
        return false;
    }

    //get category field
    public function get_category_field($field_id, $category_id)
    {
        $this->db->where('field_id', clean_number($field_id))->where('category_id', clean_number($category_id));
        return $this->db->get('custom_fields_category')->row();
    }

    //get product custom field values
    public function get_product_custom_field_values($field_id, $product_id, $lang_id)
    {
        $this->db->select('custom_fields_product.*');
        $this->db->where('custom_fields_product.field_id', clean_number($field_id))->where('custom_fields_product.product_id', clean_number($product_id));
        $this->db->select('(SELECT option_name FROM custom_fields_options_lang WHERE custom_fields_product.selected_option_id = custom_fields_options_lang.option_id AND custom_fields_options_lang.lang_id =  ' . clean_number($lang_id) . ' LIMIT 1) AS option_name');
        if (item_count($this->languages) > 1) {
            $this->db->select('(SELECT option_name FROM custom_fields_options_lang WHERE custom_fields_product.selected_option_id = custom_fields_options_lang.option_id AND custom_fields_options_lang.lang_id !=  ' . clean_number($lang_id) . ' LIMIT 1) AS second_name');
        }
        return $this->db->get('custom_fields_product')->result();
    }

    //get product custom field input value
    public function get_product_custom_field_input_value($field_id, $product_id)
    {
        $this->db->where('field_id', clean_number($field_id))->where('product_id', clean_number($product_id))->limit(1);
        $row = $this->db->get('custom_fields_product')->row();
        if (!empty($row)) {
            return $row->field_value;
        }
        return "";
    }

    //delete category from field
    public function delete_category_from_field($field_id, $category_id)
    {
        $this->db->where('field_id', clean_number($field_id))->where('category_id', clean_number($category_id));
        return $this->db->delete('custom_fields_category');
    }

    //delete custom field option
    public function delete_custom_field_option($id)
    {
        $option = $this->get_field_option($id);
        if (!empty($option)) {
            //delete option
            $this->db->where('id', $option->id)->delete('custom_fields_options');
            //delete names
            $this->db->where('option_id', $option->id)->delete('custom_fields_options_lang');
        }
    }

    //clear field categories
    public function clear_field_categories($field_id)
    {
        $this->db->where('field_id', clean_number($field_id));
        $fields = $this->db->get('custom_fields_category')->result();
        if (!empty($fields)) {
            foreach ($fields as $item) {
                $this->db->where('id', $item->id);
                $this->db->delete('custom_fields_category');
            }
        }
    }

    //add remove custom field filters
    public function add_remove_custom_field_filters($field_id)
    {
        $field = $this->get_field($field_id);
        if (!empty($field)) {
            if ($field->is_product_filter == 1) {
                $data = array(
                    "is_product_filter" => 0
                );
            } else {
                $data = array(
                    "is_product_filter" => 1
                );
            }
            $this->db->where('id', $field->id);
            return $this->db->update('custom_fields', $data);
        }
    }

    //delete field options
    public function delete_field_options($field_id)
    {
        $this->db->where('field_id', clean_number($field_id));
        $fields = $this->db->get('custom_fields_options')->result();
        if (!empty($fields)) {
            foreach ($fields as $item) {
                $this->db->where('id', $item->id);
                $this->db->delete('custom_fields_options');
            }
        }
    }

    //delete field product values
    public function delete_field_product_values($field_id)
    {
        $this->db->where('field_id', clean_number($field_id));
        $fields = $this->db->get('custom_fields_product')->result();
        if (!empty($fields)) {
            foreach ($fields as $item) {
                $this->db->where('id', $item->id);
                $this->db->delete('custom_fields_product');
            }
        }
    }

    //delete field product values by product id
    public function delete_field_product_values_by_product_id($product_id)
    {
        $this->db->where('product_id', clean_number($product_id));
        $fields = $this->db->get('custom_fields_product')->result();
        if (!empty($fields)) {
            foreach ($fields as $item) {
                $this->db->where('id', $item->id);
                $this->db->delete('custom_fields_product');
            }
        }
    }

    //delete field
    public function delete_field($id)
    {
        $field = $this->get_field($id);
        if (!empty($field)) {
            //delete fields category
            $this->clear_field_categories($field->id);
            //delete options
            $this->delete_field_options($field->id);
            //delete product values
            $this->delete_field_product_values($field->id);

            $this->db->where('id', $field->id);
            return $this->db->delete('custom_fields');
        }
        return false;
    }

}
