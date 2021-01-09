<?php defined('BASEPATH') or exit('No direct script access allowed');

//get product title
if (!function_exists('get_product_title')) {
    function get_product_title($product)
    {
        if (!empty($product)) {
            if (!empty($product->title)) {
                return $product->title;
            } elseif (!empty($product->second_title)) {
                return $product->second_title;
            }
        }
        return "";
    }
}

//get available product
if (!function_exists('get_active_product')) {
    function get_active_product($id)
    {
        $ci =& get_instance();
        return $ci->product_model->get_active_product($id);
    }
}

//get product details
if (!function_exists('get_product_details')) {
    function get_product_details($id, $lang_id, $get_main_on_null = true)
    {
        $ci =& get_instance();
        return $ci->product_model->get_product_details($id, $lang_id, $get_main_on_null);
    }
}

//product location cache key
if (!function_exists('get_location_cache_key')) {
    function get_location_cache_key()
    {
        $ci =& get_instance();
        $key = "";
        if (!empty($ci->default_location->city_id)) {
            $key .= "cty" . $ci->default_location->city_id;
        }
        if (!empty($ci->default_location->state_id)) {
            $key .= "st" . $ci->default_location->state_id;
        }
        if (!empty($ci->default_location->country_id)) {
            $key .= "ctry" . $ci->default_location->country_id;
        }
        if (!empty($key)) {
            $key = '_' . $key;
        }
        return $key;
    }
}

//get parent categories
if (!function_exists('get_parent_categories')) {
    function get_parent_categories()
    {
        $ci =& get_instance();
        if (!empty($ci->categories_array)) {
            if (isset($ci->categories_array[0])) {
                return $ci->categories_array[0];
            }
        }
        return null;
    }
}

//get subcategories
if (!function_exists('get_subcategories')) {
    function get_subcategories($parent_id)
    {
        $ci =& get_instance();
        if (!empty($ci->categories_array)) {
            if (isset($ci->categories_array[$parent_id])) {
                return $ci->categories_array[$parent_id];
            }
        }
        return null;
    }
}

//get category
if (!function_exists('get_category')) {
    function get_category($categories, $id)
    {
        if (!empty($categories)) {
            return array_filter($categories, function ($item) use ($id) {
                return $item->id == $id;
            });
        }
        return null;
    }
}

//get categories json
if (!function_exists('get_categories_json')) {
    function get_categories_json($lang_id)
    {
        $ci =& get_instance();
        return $ci->category_model->get_categories_json($lang_id);
    }
}

if (!function_exists('get_category_by_id')) {
    function get_category_by_id($id)
    {
        $ci =& get_instance();
        return $ci->category_model->get_category($id);
    }
}

//get category
if (!function_exists('get_category_by_id')) {
    function get_category_by_id($id)
    {
        $ci =& get_instance();
        return $ci->category_model->get_category($id);
    }
}

//get category name
if (!function_exists('category_name')) {
    function category_name($category)
    {
        if (!empty($category)) {
            if (!empty($category->name)) {
                return html_escape($category->name);
            } else {
                if (!empty($category->second_name)) {
                    return html_escape($category->second_name);
                }
            }
        }
        return "";
    }
}

//get category image url
if (!function_exists('get_category_image_url')) {
    function get_category_image_url($category)
    {
        if ($category->storage == "aws_s3") {
            $ci =& get_instance();
            return $ci->aws_base_url . $category->image;
        } else {
            return base_url() . $category->image;
        }
    }
}

//get parent categories tree
if (!function_exists('get_parent_categories_tree')) {
    function get_parent_categories_tree($category_id, $only_visible = true, $lang_id = null)
    {
        $ci =& get_instance();
        return $ci->category_model->get_parent_categories_tree($category_id, $only_visible, $lang_id = null);
    }
}

if (!function_exists('get_ids_from_array')) {
    function get_ids_from_array($array, $column = 'id')
    {
        if (!empty($array)) {
            return get_array_column_values($array, $column);
        }
        return array();
    }
}

//generate ids string
if (!function_exists('generate_ids_string')) {
    function generate_ids_string($array)
    {
        if (empty($array)) {
            return "0";
        } else {
            return implode(',', $array);
        }
    }
}


//product form data
if (!function_exists('get_product_form_data')) {
    function get_product_form_data($product)
    {
        $ci =& get_instance();
        $data = new stdClass();
        $data->add_to_cart_url = "";
        $data->button = "";

        if (!empty($product)) {
            $disabled = "";
            if (!check_product_stock($product)) {
                $disabled = " disabled";
            }
            if ($product->listing_type == 'sell_on_site') {
                if ($product->is_free_product != 1) {
                    $data->add_to_cart_url = base_url() . 'add-to-cart';
                    $data->button = '<button class="btn btn-md btn-block btn-product-cart"' . $disabled . '><i class="icon-cart-solid"></i>' . trans("add_to_cart") . '</button>';
                }
            } elseif ($product->listing_type == 'bidding') {
                $data->add_to_cart_url = base_url() . 'request-quote';
                $data->button = '<button class="btn btn-md btn-block btn-product-cart"' . $disabled . '>' . trans("request_a_quote") . '</button>';
                if (!$ci->auth_check && $product->listing_type == 'bidding') {
                    $data->button = '<button type="button" data-toggle="modal" data-target="#loginModal" class="btn btn-md btn-block btn-product-cart"' . $disabled . '>' . trans("request_a_quote") . '</button>';
                }
            } else {
                if (!empty($product->external_link)) {
                    $data->button = '<a href="' . $product->external_link . '" class="btn btn-md btn-block" target="_blank" rel="nofollow">' . trans("buy_now") . '</a>';
                }
            }
        }
        return $data;
    }
}

//get product item image
if (!function_exists('get_product_item_image')) {
    function get_product_item_image($product, $get_second = false)
    {
        $ci =& get_instance();
        if (!empty($product)) {
            $image = $product->image;
            if (!empty($product->image_second) && $get_second == true) {
                $image = $product->image_second;
            }
            if (!empty($image)) {
                $image_array = explode("::", $image);
                if (!empty($image_array[0]) && !empty($image_array[1])) {
                    if ($image_array[0] == "aws_s3") {
                        return $ci->aws_base_url . "uploads/images/" . $image_array[1];
                    } else {
                        return base_url() . "uploads/images/" . $image_array[1];
                    }
                }
            }
        }
        return base_url() . 'assets/img/no-image.jpg';
    }
}

//get latest products
if (!function_exists('get_latest_products')) {
    function get_latest_products($limit)
    {
        $ci =& get_instance();
        if ($ci->general_settings->cache_system == 1) {
            $key = "latest_products" . get_location_cache_key();
            $latest_products = get_cached_data($key);
            if (empty($latest_products)) {
                $latest_products = $ci->product_model->get_products_limited($limit);
                set_cache_data($key, $latest_products);
            }
            return $latest_products;
        } else {
            return $ci->product_model->get_products_limited($limit);
        }
    }
}

//get promoted products
if (!function_exists('get_promoted_products')) {
    function get_promoted_products($per_page, $offset)
    {
        $ci =& get_instance();
        if ($ci->general_settings->cache_system == 1) {
            $key = "promoted_products" . get_location_cache_key() . "_" . $per_page . "_" . $offset;
            $promoted_products = get_cached_data($key);
            if (empty($promoted_products)) {
                $promoted_products = $ci->product_model->get_promoted_products_limited($per_page, $offset);
                set_cache_data($key, $promoted_products);
            }
            return $promoted_products;
        } else {
            return $ci->product_model->get_promoted_products_limited($per_page, $offset);
        }
    }
}

//get promoted products count
if (!function_exists('get_promoted_products_count')) {
    function get_promoted_products_count()
    {
        $ci =& get_instance();
        if ($ci->general_settings->cache_system == 1) {
            $key = "promoted_products_count" . get_location_cache_key();
            $count = get_cached_data($key);
            if (empty($count)) {
                $count = $ci->product_model->get_promoted_products_count();
                set_cache_data($key, $count);
            }
            return $count;
        } else {
            return $ci->product_model->get_promoted_products_count();
        }
    }
}

//get index categories products
if (!function_exists('get_index_categories_products_array')) {
    function get_index_categories_products_array($categories)
    {
        $ci =& get_instance();
        $products = null;
        if ($ci->general_settings->cache_system == 1) {
            $key = "index_category_products" . get_location_cache_key();
            $products = get_cached_data($key);
            if (empty($products)) {
                $products = $ci->product_model->get_index_categories_products($categories);
                set_cache_data($key, $products);
            }
        } else {
            $products = $ci->product_model->get_index_categories_products($categories);
        }
        $array = array();
        if (!empty($products)) {
            foreach ($products as $product) {
                if (!empty($product->category_id)) {
                    if (!isset($array[$product->category_id]) || (isset($array[$product->category_id]) && item_count($array[$product->category_id]) < 20)) {
                        $array[$product->category_id][] = $product;
                    }
                }
            }
        }
        return $array;
    }
}

//is product in wishlist
if (!function_exists('is_product_in_wishlist')) {
    function is_product_in_wishlist($product)
    {
        $ci =& get_instance();
        if ($ci->auth_check) {
            if (!empty($product->is_in_wishlist)) {
                return true;
            }
        } else {
            $wishlist = $ci->session->userdata('mds_guest_wishlist');
            if (!empty($wishlist)) {
                if (in_array($product->id, $wishlist)) {
                    return true;
                }
            }
        }
        return false;
    }
}

//get currency
if (!function_exists('get_currency')) {
    function get_currency($currency_key)
    {
        $ci =& get_instance();
        if (!empty($ci->currencies)) {
            if (isset($ci->currencies[$currency_key])) {
                return $ci->currencies[$currency_key]["hex"];
            }
        }
        return "";
    }
}

//get currency sign
if (!function_exists('get_currency_sign')) {
    function get_currency_sign($currency_key)
    {
        $ci =& get_instance();
        if (!empty($ci->currencies)) {
            if (isset($ci->currencies[$currency_key])) {
                return $ci->currencies[$currency_key]["symbol"];
            }
        }
        return "";
    }
}

//calculate product price
if (!function_exists('calculate_product_price')) {
    function calculate_product_price($price, $discount_rate)
    {
        if (!empty($price)) {
            if (!empty($discount_rate)) {
                $price = $price - (($price * $discount_rate) / 100);
            }
            return $price;
        }
        return 0;
    }
}

//calculate vat
if (!function_exists('calculate_vat')) {
    function calculate_vat($price_calculated, $vat_rate)
    {
        return ($price_calculated * $vat_rate) / 100;
    }
}

//calculate product vat
if (!function_exists('calculate_product_vat')) {
    function calculate_product_vat($product)
    {
        if (!empty($product)) {
            if (!empty($product->vat_rate)) {
                $price = calculate_product_price($product->price, $product->discount_rate);
                return ($price * $product->vat_rate) / 100;
            }
        }
        return 0;
    }
}

//calculate earned amount
if (!function_exists('calculate_earned_amount')) {
    function calculate_earned_amount($product)
    {
        $ci =& get_instance();
        if (!empty($product)) {
            $price = calculate_product_price($product->price, $product->discount_rate) + calculate_product_vat($product);
            return $price - (($price * $ci->general_settings->commission_rate) / 100);
        }
        return 0;
    }
}

//price formatted
if (!function_exists('price_formatted')) {
    function price_formatted($price, $currency, $format = null)
    {
        $ci =& get_instance();
        $price = $price / 100;
        $dec_point = '.';
        $thousands_sep = ',';
        if ($ci->thousands_separator != '.') {
            $dec_point = ',';
            $thousands_sep = '.';
        }

        if (is_int($price)) {
            $price = number_format($price, 0, $dec_point, $thousands_sep);
        } else {
            $price = number_format($price, 2, $dec_point, $thousands_sep);
        }
        $price = price_currency_format($price, $currency);
        return $price;
    }
}

//price currency format
if (!function_exists('price_currency_format')) {
    function price_currency_format($price, $currency)
    {
        $ci =& get_instance();
        $currency = get_currency($currency);
        $space = "";
        if ($ci->payment_settings->space_between_money_currency == 1) {
            $space = " ";
        }
        if ($ci->payment_settings->currency_symbol_format == "left") {
            $price = "<span>" . $currency . "</span>" . $space . $price;
        } else {
            $price = $price . $space . "<span>" . $currency . "</span>";
        }
        return $price;
    }
}

//get price
if (!function_exists('get_price')) {
    function get_price($price, $format_type)
    {
        $ci =& get_instance();

        if ($format_type == "input") {
            $price = $price / 100;
            if (is_int($price)) {
                $price = number_format($price, 0, ".", "");
            } else {
                $price = number_format($price, 2, ".", "");
            }
            if ($ci->thousands_separator == ',') {
                $price = str_replace('.', ',', $price);
            }
            return $price;
        } elseif ($format_type == "decimal") {
            $price = $price / 100;
            return number_format($price, 2, ".", "");
        } elseif ($format_type == "database") {
            $price = str_replace(',', '.', $price);
            $price = floatval($price);
            $price = number_format($price, 2, '.', '') * 100;
            return $price;
        } elseif ($format_type == "separator_format") {
            $price = $price / 100;
            $dec_point = '.';
            $thousands_sep = ',';
            if ($ci->thousands_separator != '.') {
                $dec_point = ',';
                $thousands_sep = '.';
            }
            return number_format($price, 2, $dec_point, $thousands_sep);
        }
    }
}

//get variation label
if (!function_exists('get_variation_label')) {
    function get_variation_label($label_array, $lang_id)
    {
        $ci =& get_instance();
        $label = "";
        if (!empty($label_array)) {
            $label_array = unserialize_data($label_array);
            foreach ($label_array as $item) {
                if ($lang_id == $item['lang_id']) {
                    $label = $item['label'];
                    break;
                }
            }
            if (empty($label)) {
                foreach ($label_array as $item) {
                    if ($ci->general_settings->site_lang == $item['lang_id']) {
                        $label = $item['label'];
                        break;
                    }
                }
            }
        }
        return $label;
    }
}

//get variation option name
if (!function_exists('get_variation_option_name')) {
    function get_variation_option_name($names_array, $lang_id)
    {
        $ci =& get_instance();
        $name = "";
        if (!empty($names_array)) {
            $names_array = unserialize_data($names_array);
            foreach ($names_array as $item) {
                if ($lang_id == $item['lang_id']) {
                    $name = $item['option_name'];
                    break;
                }
            }
            if (empty($name)) {
                foreach ($names_array as $item) {
                    if ($ci->general_settings->site_lang == $item['lang_id']) {
                        $name = $item['option_name'];
                        break;
                    }
                }
            }
        }
        return $name;
    }
}

//get variation default option
if (!function_exists('get_variation_default_option')) {
    function get_variation_default_option($variation_id)
    {
        $ci =& get_instance();
        return $ci->variation_model->get_variation_default_option($variation_id);
    }
}

//get variation sub options
if (!function_exists('get_variation_sub_options')) {
    function get_variation_sub_options($parent_id)
    {
        $ci =& get_instance();
        return $ci->variation_model->get_variation_sub_options($parent_id);
    }
}

//is there variation uses different price
if (!function_exists('is_there_variation_uses_different_price')) {
    function is_there_variation_uses_different_price($product_id, $except_id = null)
    {
        $ci =& get_instance();
        return $ci->variation_model->is_there_variation_uses_different_price($product_id, $except_id);
    }
}

//discount rate format
if (!function_exists('discount_rate_format')) {
    function discount_rate_format($discount_rate)
    {
        return $discount_rate . "%";
    }
}

//check product stock
if (!function_exists('check_product_stock')) {
    function check_product_stock($product)
    {
        if (!empty($product)) {
            if ($product->product_type == 'digital') {
                return true;
            }
            if ($product->stock > 0) {
                return true;
            }
        }
        return false;
    }
}

//get query string array
if (!function_exists('get_query_string_array')) {
    function get_query_string_array($custom_filters)
    {
        $array_filter_keys = get_array_column_values($custom_filters, 'product_filter_key');
        array_push($array_filter_keys, "p_min");
        array_push($array_filter_keys, "p_max");
        array_push($array_filter_keys, "product_type");
        array_push($array_filter_keys, "sort");
        array_push($array_filter_keys, "search");

        $queries = array();
        $array_queries = array();
        $str = $_SERVER["QUERY_STRING"];
        $str = str_replace('<', '', $str);
        $str = str_replace('>', '', $str);
        $str = str_replace('*', '', $str);
        $str = str_replace('"', '', $str);
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        @parse_str($str, $queries);
        if (!empty($queries)) {
            foreach ($queries as $key => $value) {
                if (in_array($key, $array_filter_keys)) {
                    $key = str_slug($key);
                    $array_values = explode(',', $value);
                    for ($i = 0; $i < item_count($array_values); $i++) {
                        $array_values[$i] = remove_forbidden_characters($array_values[$i]);
                    }
                    $array_queries[$key] = $array_values;
                }
            }
        }
        return $array_queries;
    }
}

//generate filter url
if (!function_exists('generate_filter_url')) {
    function generate_filter_url($query_string_array, $key, $value)
    {
        $key = @urlencode($key);
        $query = "";
        if (!empty($key)) {
            if (empty($query_string_array) || !is_array($query_string_array)) {
                return "?" . $key . "=" . @urlencode($value);
            }

            //add remove the key value
            if (!empty($query_string_array[$key])) {
                if ($key == "sort") {
                    $query_string_array[$key] = [$value];
                } else {
                    if (in_array($value, $query_string_array[$key])) {
                        $new_array = array();
                        foreach ($query_string_array[$key] as $item) {
                            if (!empty($item) && $item != $value) {
                                $new_array[] = $item;
                            }
                        }
                        $query_string_array[$key] = $new_array;
                    } else {
                        $query_string_array[$key][] = $value;
                    }
                }
            } else {
                $query_string_array[$key][] = $value;
            }
        }

        //generate query string
        $i = 0;
        foreach ($query_string_array as $key => $array_values) {
            if (!empty($array_values)) {
                if ($i == 0) {
                    $query = "?" . generate_filter_string($key, $array_values);
                } else {
                    $query .= "&" . generate_filter_string($key, $array_values);
                }
                $i++;
            }
        }
        return $query;
    }
}

//generate filter string
if (!function_exists('generate_filter_string')) {
    function generate_filter_string($key, $array_values)
    {
        $str = "";
        $j = 0;
        if (!empty($array_values)) {
            foreach ($array_values as $value) {
                if (!empty($value) && !is_array($value)) {
                    $value = urlencode($value);
                    if ($j == 0) {
                        $str = $value;
                    } else {
                        $str .= "," . $value;
                    }
                    $j++;
                }
            }
            $str = $key . "=" . $str;
        }
        return $str;
    }
}

//get query string array to array of objects
if (!function_exists('convert_query_string_to_object_array')) {
    function convert_query_string_to_object_array($query_string_array)
    {
        $array = array();
        if (!empty($query_string_array)) {
            foreach ($query_string_array as $key => $array_values) {
                if (!empty($array_values)) {
                    foreach ($array_values as $value) {
                        $obj = new stdClass();
                        $obj->key = $key;
                        $obj->value = $value;
                        array_push($array, $obj);
                    }
                }
            }
        }
        return $array;
    }
}

//is custom field option selected
if (!function_exists('is_custom_field_option_selected')) {
    function is_custom_field_option_selected($query_string_object_array, $key, $value)
    {
        if (!empty($query_string_object_array)) {
            foreach ($query_string_object_array as $item) {
                if ($item->key == $key && $item->value == $value) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }
}

//generate price filter url
if (!function_exists('generate_price_filter_url')) {
    function generate_price_filter_url($query_string_object_array)
    {
        $query_array = array();
        foreach ($query_string_object_array as $item) {
            if ($item->key != 'p_min' && $item->key != 'p_max') {
                $query_array[] = urlencode($item->key) . '=' . urlencode($item->value);
            }
        }
        return implode('&', $query_array);
    }
}
?>