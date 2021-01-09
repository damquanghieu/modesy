<?php defined('BASEPATH') or exit('No direct script access allowed');

class Category_model extends CI_Model
{
    //build query
    public function build_query($all_columns = false, $lang_id = null)
    {
        $ci =& get_instance();
        if (empty($lang_id)) {
            $lang_id = $this->selected_lang->id;
        }
        if ($all_columns == true) {
            $this->db->select('categories.*, categories.parent_id AS join_parent_id');
        } else {
            $this->db->select('categories.id, categories.slug, categories.parent_id, categories.category_order, categories.featured_order, categories.storage, categories.image, categories.show_image_on_navigation, categories.parent_id AS join_parent_id');
        }
        $this->db->select('(SELECT name FROM categories_lang WHERE categories_lang.category_id = categories.id AND categories_lang.lang_id = ' . clean_number($lang_id) . ' LIMIT 1) AS name');
        if (item_count($ci->languages) > 1) {
            $this->db->select('(SELECT name FROM categories_lang WHERE categories_lang.category_id = categories.id AND categories_lang.lang_id != ' . clean_number($lang_id) . ' LIMIT 1) AS second_name');
        }
        $this->db->select('(SELECT slug FROM categories WHERE id = join_parent_id) AS parent_slug');
        $this->db->select('(SELECT id FROM categories AS sub_categories WHERE sub_categories.parent_id = categories.id LIMIT 1) AS has_subcategory');
    }

    //get categories array
    public function get_categories_array()
    {
        $this->build_query();
        $this->db->where('visibility', 1);
        $this->order_by_categories();
        $query = $this->db->get('categories');
        $rows = $query->result();
        if (!empty($rows)) {
            $array = array();
            foreach ($rows as $row) {
                $array[$row->parent_id][] = $row;
            }
            if ($this->general_settings->sort_parent_categories_by_order == 1 && !empty($array[0])) {
                usort($array[0], function ($a, $b) {
                    return $a->category_order > $b->category_order;
                });
            }
            return $array;
        }
        return null;
    }

    //get category
    public function get_category($id)
    {
        $this->build_query(true);
        $this->db->where('categories.id', clean_number($id));
        $query = $this->db->get('categories');
        return $query->row();
    }

    //get category by slug
    public function get_category_by_slug($slug)
    {
        $this->build_query(true);
        $this->db->where('visibility', 1)->where('categories.slug', clean_str($slug))->limit(1);
        $query = $this->db->get('categories');
        return $query->row();
    }

    //get all categories ordered by name
    public function get_categories_ordered_by_name()
    {
        $this->build_query();
        $this->db->where('visibility', 1);
        $this->db->order_by('name');
        $query = $this->db->get('categories');
        return $query->result();
    }

    //get parent category by slug
    public function get_parent_category_by_slug($slug)
    {
        $this->build_query(true);
        $this->db->where('categories.slug', clean_str($slug))->where('visibility', 1)->where('parent_id', 0);
        $this->db->order_by('id')->limit(1);
        $query = $this->db->get('categories');
        return $query->row();
    }

    //get featured categories
    public function get_featured_categories()
    {
        $this->build_query();
        $this->db->where('visibility', 1)->where('is_featured', 1);
        $this->db->order_by('featured_order');
        return $this->db->get('categories')->result();
    }

    //get index categories
    public function get_index_categories()
    {
        $this->build_query();
        $this->db->where('visibility', 1)->where('show_products_on_index', 1);
        $this->db->order_by('homepage_order');
        return $this->db->get('categories')->result();
    }

    //get parent categories tree
    public function get_parent_categories_tree($category_id, $only_visible = true, $lang_id = null)
    {
        $sql = "SELECT tbl2.id FROM (
                  SELECT @r AS _id,
                  (SELECT @r := parent_id FROM categories WHERE id = _id) AS parent_id, @l := @l + 1 AS cat_level
                  FROM (SELECT @r := " . clean_number($category_id) . ", @l := 0) vars, categories h WHERE @r <> 0) tbl1
                JOIN categories tbl2 ON tbl1._id = tbl2.id ORDER BY tbl1.cat_level DESC";
        if (!empty($sql)) {
            $this->build_query(true, $lang_id);
            $this->db->where('categories.id IN (' . $sql . ')');
            if ($only_visible == true) {
                $this->db->where('categories.visibility', 1);
            }
            $this->db->order_by('categories.id');
            $query = $this->db->get('categories');
            return $query->result();
        }
        return array();
    }

    //get subcategories tree
    public function get_subcategories_tree($category_id, $only_visible = true, $lang_id = null)
    {
        $sql = "SELECT id FROM 
                    (SELECT * FROM categories ORDER BY parent_id, id) tbl,
                    (SELECT @pv := ?) INITIALISATION
                WHERE FIND_IN_SET(parent_id, @pv) > 0
                AND @pv := CONCAT(@pv, ',', id)";
        $query = $this->db->query($sql, array(clean_number($category_id)));
        $ids = $query->result_array();
        if (is_array($ids)) {
            $ids = get_array_column_values($ids, 'id');
            if (!in_array($category_id, $ids)) {
                array_push($ids, $category_id);
            }
        }
        if (!empty($ids)) {
            $this->build_query(true, $lang_id);
            $this->db->where_in('categories.id', $ids);
            if ($only_visible) {
                $this->db->where('categories.visibility', 1);
            }
            $this->db->order_by('categories.id');
            $query = $this->db->get('categories');
            return $query->result();
        }
        return array();
    }

    //sort categories
    public function order_by_categories()
    {
        $sort = $this->general_settings->sort_categories;
        if ($sort == "date") {
            $this->db->order_by('categories.created_at');
        } elseif ($sort == "date_desc") {
            $this->db->order_by('categories.created_at', 'DESC');
        } elseif ($sort == "alphabetically") {
            $this->db->order_by('name');
        } else {
            $this->db->order_by('category_order');
        }
    }

    /*
    *-------------------------------------------------------------------------------------------------
    * BACK-END
    *-------------------------------------------------------------------------------------------------
    */

    //input values
    public function input_values()
    {
        $data = array(
            'slug' => $this->input->post('slug', true),
            'title_meta_tag' => $this->input->post('title_meta_tag', true),
            'description' => $this->input->post('description', true),
            'keywords' => $this->input->post('keywords', true),
            'category_order' => $this->input->post('category_order', true),
            'featured_order' => 1,
            'visibility' => $this->input->post('visibility', true),
            'show_image_on_navigation' => $this->input->post('show_image_on_navigation', true)
        );
        return $data;
    }

    //add category
    public function add_category()
    {
        $data = $this->input_values();
        //set slug
        if (empty($data["slug"])) {
            $data["slug"] = str_slug($this->input->post('name_lang_' . $this->general_settings->site_lang, true));
        } else {
            $data["slug"] = remove_special_characters($data["slug"], true);
        }

        //set parent id
        $data["parent_id"] = 0;
        $category_ids_array = $this->input->post('parent_id', true);
        if (!empty($category_ids_array)) {
            foreach ($category_ids_array as $key => $value) {
                if (!empty($value)) {
                    $data["parent_id"] = $value;
                }
            }
        }

        $data["storage"] = "local";
        $this->load->model('upload_model');
        $temp_path = $this->upload_model->upload_temp_image('file');
        $data['image'] = "";
        if (!empty($temp_path)) {
            $data["image"] = $this->upload_model->category_image_upload($temp_path);
            $this->upload_model->delete_temp_image($temp_path);
        }
        //move to s3
        if ($this->storage_settings->storage == "aws_s3") {
            $this->load->model("aws_model");
            $data["storage"] = "aws_s3";
            //move image
            if ($data["image"] != "") {
                $this->aws_model->put_category_object($data["image"], FCPATH . $data["image"]);
                delete_file_from_server($data["image"]);
            }
        }
        $data['is_featured'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->db->insert('categories', $data);
    }

    //add category name
    public function add_category_name($category_id)
    {
        foreach ($this->languages as $language) {
            $data = array(
                'category_id' => clean_number($category_id),
                'lang_id' => $language->id,
                'name' => $this->input->post('name_lang_' . $language->id, true)
            );
            $this->db->insert('categories_lang', $data);
        }
    }

    //update slug
    public function update_slug($id)
    {
        $category = $this->get_category($id);
        if (!empty($category)) {
            if (empty($category->slug) || $category->slug == "-") {
                $data = array(
                    'slug' => $category->id
                );
                $this->db->where('id', $category->id);
                return $this->db->update('categories', $data);
            } else {
                if (!empty($this->check_category_slug($category->slug, $id))) {
                    $data = array(
                        'slug' => $category->slug . "-" . $category->id
                    );
                    $this->db->where('id', $category->id);
                    return $this->db->update('categories', $data);
                }
            }
        }
    }

    //update category
    public function update_category($id)
    {
        $data = $this->input_values();
        //set slug
        if (empty($data["slug"])) {
            $data["slug"] = str_slug($this->input->post('name_lang_' . $this->general_settings->site_lang, true));
        } else {
            $data["slug"] = remove_special_characters($data["slug"], true);
        }

        //set parent id
        $data["parent_id"] = 0;
        $category_ids_array = $this->input->post('parent_id', true);
        if (!empty($category_ids_array)) {
            foreach ($category_ids_array as $key => $value) {
                if (!empty($value)) {
                    $data["parent_id"] = $value;
                }
            }
        }

        $this->load->model('upload_model');
        $temp_path = $this->upload_model->upload_temp_image('file');
        if (!empty($temp_path)) {
            $data["image"] = $this->upload_model->category_image_upload($temp_path);
            $this->upload_model->delete_temp_image($temp_path);
            $category = $this->get_category($id);
            $data["storage"] = "local";
            //move to s3
            if ($this->storage_settings->storage == "aws_s3") {
                $this->load->model("aws_model");
                $data["storage"] = "aws_s3";
                //move image
                $this->aws_model->put_category_object($data["image"], FCPATH . $data["image"]);
                delete_file_from_server($data["image"]);
            }
            //delete old images
            if ($category->storage == "aws_s3") {
                $this->load->model("aws_model");
                $this->aws_model->delete_category_object($category->image);
            } else {
                delete_file_from_server($category->image);
            }
        }

        $this->db->where('id', clean_number($id));
        return $this->db->update('categories', $data);
    }

    //update category name
    public function update_category_name($category_id)
    {
        foreach ($this->languages as $language) {
            $data = array(
                'category_id' => clean_number($category_id),
                'lang_id' => $language->id,
                'name' => $this->input->post('name_lang_' . $language->id, true)
            );
            //check category name exists
            $this->db->where('category_id', clean_number($category_id));
            $this->db->where('lang_id', $language->id);
            $row = $this->db->get('categories_lang')->row();
            if (empty($row)) {
                $this->db->insert('categories_lang', $data);
            } else {
                $this->db->where('category_id', clean_number($category_id));
                $this->db->where('lang_id', $language->id);
                $this->db->update('categories_lang', $data);
            }
        }
    }

    //update settings
    public function update_settings()
    {
        $data = array(
            'sort_categories' => $this->input->post('sort_categories', true),
            'sort_parent_categories_by_order' => $this->input->post('sort_parent_categories_by_order', true)
        );
        if (empty($data['sort_parent_categories_by_order'])) {
            $data['sort_parent_categories_by_order'] = 0;
        }
        $this->db->where('id', 1);
        return $this->db->update('general_settings', $data);
    }

    //check category slug
    public function check_category_slug($slug, $id)
    {
        $sql = "SELECT * FROM categories WHERE categories.slug = ? AND categories.id != ?";
        $query = $this->db->query($sql, array(clean_str($slug), clean_number($id)));
        return $query->row();
    }

    //get category back end
    public function get_category_back_end($id)
    {
        $this->build_query(true);
        $this->db->where('categories.id', clean_number($id));
        $query = $this->db->get('categories');
        return $query->row();
    }

    //get category by lang
    public function get_category_by_lang($id, $lang_id)
    {
        $this->db->where('category_id', clean_number($id));
        $this->db->where('lang_id', clean_number($lang_id));
        $query = $this->db->get('categories_lang');
        return $query->row();
    }

    //get subcategories by parent id except one
    public function get_subcategories_by_parent_id_except_one($parent_id, $except_id)
    {
        $this->build_query(true);
        $this->db->where('visibility', 1)->where('categories.parent_id', clean_number($parent_id))->where('categories.id != ', clean_number($except_id));
        $this->db->order_by('category_order');
        $query = $this->db->get('categories');
        return $query->result();
    }

    //get categories
    public function get_categories()
    {
        $this->build_query(true);
        $this->order_by_categories();
        $query = $this->db->get('categories');
        return $query->result();
    }

    //get subcategories by parent id
    public function get_subcategories_by_parent_id($parent_id)
    {
        $this->build_query(true);
        $this->db->where('categories.parent_id', clean_number($parent_id));
        $this->order_by_categories();
        $query = $this->db->get('categories');
        return $query->result();
    }

    //get parent categories
    public function get_parent_categories()
    {
        $this->build_query(true);
        $this->db->where('parent_id', 0)->where('visibility', 1);
        $this->order_by_categories();
        return $this->db->get('categories')->result();
    }

    //get all parent categories
    public function get_all_parent_categories()
    {
        $this->build_query(true);
        $this->db->where('parent_id', 0);
        $this->order_by_categories();
        return $this->db->get('categories')->result();
    }

    //get all parent categories by lang
    public function get_all_parent_categories_by_lang($lang_id)
    {
        $this->build_query(true, $lang_id);
        $this->db->where('parent_id', 0);
        $this->order_by_categories();
        return $this->db->get('categories')->result();
    }

    //get categories array by lang
    public function get_categories_array_by_lang($lang_id, $parent_id = null)
    {
        $ids = array();
        if (!empty($parent_id)) {
            $categories = $this->get_subcategories_tree($parent_id, false, $lang_id);
            $ids = get_ids_from_array($categories);
        }
        $this->build_query(true, $lang_id);
        if (!empty($ids)) {
            $this->db->where_in('categories.id', $ids);
        }
        $this->order_by_categories();
        $query = $this->db->get('categories');
        $rows = $query->result();
        if (!empty($rows)) {
            $array = array();
            $array_json = array();
            foreach ($rows as $row) {
                $array[$row->parent_id][] = $row;
                $item = array(
                    'id' => $row->id,
                    'parent_id' => '',
                    'index' => ''
                );
                array_push($array_json, $item);
            }
            if ($this->general_settings->sort_parent_categories_by_order == 1 && !empty($array[0])) {
                usort($array[0], function ($a, $b) {
                    return $a->category_order > $b->category_order;
                });
            }
            return ['array' => $array, 'array_json' => json_encode($array_json)];
        }
        return null;
    }

    //get categories count
    public function get_categories_count()
    {
        $this->db->from('categories');
        return $this->db->count_all_results();
    }

    //get categories json
    public function get_categories_json($lang_id)
    {
        $this->build_query(false);
        $this->db->order_by('name');
        $this->db->where('visibility', 1);
        $query = $this->db->get('categories');
        $categories = $query->result();
        $array = array();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $item = array(
                    'id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'name' => category_name($category),
                );
                array_push($array, $item);
            }
        }
        echo json_encode($array);
    }

    //sort categories
    public function sort_categories_json()
    {
        $json_categories = $this->input->post('json_categories', true);
        $json_categories = json_decode($json_categories);
        foreach ($json_categories as $category) {
            $data = array(
                'parent_id' => clean_number($category->parent_id),
                'category_order' => clean_number($category->index)
            );
            $this->db->where('id', $category->id);
            $this->db->update('categories', $data);
        }
    }

    //generate CSV object
    public function generate_csv_object($file_path)
    {
        $array = array();
        $fields = array();
        $txt_name = uniqid() . '.txt';
        $i = 0;
        $handle = fopen($file_path, "r");
        if ($handle) {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($fields)) {
                    $fields = $row;
                    continue;
                }
                foreach ($row as $k => $value) {
                    $array[$i][$fields[$k]] = $value;
                }
                $i++;
            }
            if (!feof($handle)) {
                return false;
            }
            fclose($handle);

            if (!empty($array)) {
                $txt_file = fopen(FCPATH . "uploads/temp/" . $txt_name, "w");
                fwrite($txt_file, serialize($array));
                fclose($txt_file);
                $csv_object = new stdClass();
                $csv_object->number_of_items = count($array);
                $csv_object->txt_file_name = $txt_name;
                @unlink($file_path);
                return $csv_object;
            }
        }
        return false;
    }

    //import csv item
    public function import_csv_item($txt_file_name, $index)
    {
        $file_path = FCPATH . 'uploads/temp/' . $txt_file_name;
        $file = fopen($file_path, 'r');
        $content = fread($file, filesize($file_path));
        $array = @unserialize_data($content);
        if (!empty($array)) {
            $i = 1;
            foreach ($array as $item) {
                if ($i == $index) {
                    $data = array();
                    $name = get_csv_value($item, 'name');
                    $data['slug'] = get_csv_value($item, 'slug') ? get_csv_value($item, 'slug') : str_slug($name);
                    $data['parent_id'] = get_csv_value($item, 'parent_id', 'int');
                    $data['title_meta_tag'] = "";
                    $data['description'] = get_csv_value($item, 'description');
                    $data['keywords'] = get_csv_value($item, 'keywords');
                    $data['category_order'] = get_csv_value($item, 'category_order', 'int');
                    $data['featured_order'] = $data['category_order'];
                    $data['visibility'] = 1;
                    $data['is_featured'] = 0;
                    $data['storage'] = "local";
                    $data['image'] = "";
                    $data['show_image_on_navigation'] = 0;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    @$this->db->close();
                    @$this->db->initialize();
                    if ($this->db->insert('categories', $data)) {
                        //last id
                        $last_id = $this->db->insert_id();
                        //add category  name
                        $data_name = array(
                            'category_id' => $last_id,
                            'lang_id' => $this->selected_lang->id,
                            'name' => $name
                        );
                        $this->db->insert('categories_lang', $data_name);
                        //update slug
                        $this->category_model->update_slug($last_id);
                        return $name;
                    }
                }
                $i++;
            }
        }
    }

    //search categories by name
    public function search_categories_by_name($category_name)
    {
        $this->db->select('categories.id, categories_lang.name AS name');
        $this->db->join('categories_lang', 'categories_lang.category_id = categories.id');
        $this->db->like('name', clean_str($category_name));
        $this->db->order_by('categories.parent_id');
        $this->db->order_by('name');
        $query = $this->db->get('categories');
        return $query->result();
    }

    //set unset featured category
    public function set_unset_featured_category($category_id)
    {
        $category = $this->get_category($category_id);
        if (!empty($category)) {
            if ($this->input->post('is_form') == 1) {
                $data['is_featured'] = 1;
            } else {
                $data['is_featured'] = 0;
            }
            if ($category->is_featured == 0) {
                $data['is_featured'] = 1;
            }
            $this->db->where('id', $category->id);
            return $this->db->update('categories', $data);
        }
        return false;
    }

    //set unset index category
    public function set_unset_index_category($category_id)
    {
        $category = $this->get_category($category_id);
        if (!empty($category)) {
            if ($this->input->post('is_form') == 1) {
                $data['show_products_on_index'] = 1;
            } else {
                $data['show_products_on_index'] = 0;
            }
            if ($category->show_products_on_index == 0) {
                $data['show_products_on_index'] = 1;
            }
            $this->db->where('id', $category->id);
            return $this->db->update('categories', $data);
        }
        return false;
    }

    //update featured categories order
    public function update_featured_categories_order()
    {
        $category_id = $this->input->post('category_id', true);
        $order = clean_number($this->input->post('order', true));
        $category = $this->get_category($category_id);
        if (!empty($category) && !empty($order)) {
            $data['featured_order'] = $order;
            $this->db->where('id', $category->id);
            $this->db->update('categories', $data);
        }
    }

    //update index categories order
    public function update_index_categories_order()
    {
        $category_id = $this->input->post('category_id', true);
        $order = clean_number($this->input->post('order', true));
        $category = $this->get_category($category_id);
        if (!empty($category) && !empty($order)) {
            $data['homepage_order'] = $order;
            $this->db->where('id', $category->id);
            $this->db->update('categories', $data);
        }
    }

    //delete category name
    public function delete_category_name($category_id)
    {
        $this->db->where('category_id', clean_number($category_id));
        $query = $this->db->get('categories_lang');
        $results = $query->result();
        if (!empty($results)) {
            foreach ($results as $result) {
                $this->db->where('id', $result->id);
                $this->db->delete('categories_lang');
            }
        }
    }

    //delete category image
    public function delete_category_image($category_id)
    {
        $category = $this->get_category($category_id);
        if (!empty($category)) {
            delete_file_from_server($category->image);
            $data = array(
                'image' => ""
            );
            $this->db->where('id', $category->id);
            return $this->db->update('categories', $data);
        }
    }

    //delete category
    public function delete_category($id)
    {
        $category = $this->get_category($id);
        if (!empty($category)) {
            //delete from s3
            if ($category->storage == "aws_s3") {
                $this->load->model("aws_model");
                if (!empty($category->image)) {
                    $this->aws_model->delete_category_object($category->image);
                }
            } else {
                delete_file_from_server($category->image);
            }
            //delete category name
            $this->delete_category_name($category->id);
            $this->db->where('id', $category->id);
            return $this->db->delete('categories');
        }
        return false;
    }

}
