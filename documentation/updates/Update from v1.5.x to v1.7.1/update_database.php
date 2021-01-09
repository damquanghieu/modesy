<?php
define('BASEPATH', "/");
define('ENVIRONMENT', 'production');
require_once "application/config/database.php";
$license_code = '';
$purchase_code = '';
if (file_exists('license.php')) {
    include 'license.php';
}
if (!function_exists('curl_init')) {
    $error = 'cURL is not available on your server! Please enable cURL to continue the installation. You can read the documentation for more information.';
    exit();
}

//set database credentials
$database = $db['default'];
$db_host = $database['hostname'];
$db_name = $database['database'];
$db_user = $database['username'];
$db_password = $database['password'];

/* Connect */
$connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);
$connection->query("SET CHARACTER SET utf8");
$connection->query("SET NAMES utf8");
if (!$connection) {
    $error = "Connect failed! Please check your database credentials.";
}

if (isset($_POST["btn_submit"])) {
    if (!empty($license_array) && !empty($license_array["purchase_code"]) && !empty($license_array["license_code"])) {
        update($license_array["license_code"], $license_array["purchase_code"], $connection);
        sleep(1);
        /* close connection */
        mysqli_close($connection);
        $success = 'The update has been successfully completed!';
    } else {
        $input_code = trim($_POST['license_code']);
        //current URL
        $http = 'http';
        if (isset($_SERVER['HTTPS'])) {
            $http = 'https';
        }
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $current_url = $http . '://' . htmlentities($host) . '/' . htmlentities($requestUri);
        //check license
        $url = "https://codingest.net/api/verify-modesy-license?license_code=" . $input_code . "&domain=" . $current_url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if (empty($response)) {
            $url = "http://codingest.net/api/verify-modesy-license?license_code=" . $input_code . "&domain=" . $current_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        $data = json_decode($response);
        if (!empty($data)) {
            if ($data->code == "error") {
                $error = "Invalid License Code!";
            } else {
                $license_code = $input_code;
                $purchase_code = $data->code;
                update($license_code, $purchase_code, $connection);
                sleep(1);
                /* close connection */
                mysqli_close($connection);
                $success = 'The update has been successfully completed! Please delete the "update_database.php" file.';
            }
        } else {
            $error = "Invalid License Code!";
        }
    }
}

function update($license_code, $purchase_code, $connection)
{
    update_15_to_16($license_code, $purchase_code, $connection);
    sleep(1);
    update_16_to_17($license_code, $purchase_code, $connection);
}

function update_16_to_17($license_code, $purchase_code, $connection)
{
    //check version
    $result = mysqli_query($connection, "SELECT * FROM general_settings WHERE id = 1");
    while ($row = mysqli_fetch_array($result)) {
        if (empty($row['version'])) {
            mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `version` VARCHAR(30) DEFAULT '1.7';");
            mysqli_query($connection, "ALTER TABLE products ADD COLUMN `stock_unlimited` TINYINT(1) DEFAULT 0;");
            mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `image_mobile` VARCHAR(255);");
            mysqli_query($connection, "ALTER TABLE earnings ADD COLUMN `order_product_id` INT;");
            $array = array();
            $array["unlimited_stock"] = "Unlimited Stock";
            $array["send_test_email"] = "Send Test Email";
            $array["send_test_email_exp"] = "You can send a test mail to check if your mail server is working.";
            add_lang_trans($connection, $array);
            mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'digital_product_stock_exp'");
            mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'smtp'");
        }
    }

    mysqli_query($connection, "RENAME TABLE user_payout_accounts TO users_payout_accounts;");
    mysqli_query($connection, "DROP TABLE `routes`;");

    $table_blog_images = "CREATE TABLE `blog_images` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `image_path` varchar(255) DEFAULT NULL,
      `image_path_thumb` varchar(255) DEFAULT NULL,
      `storage` varchar(20) DEFAULT 'local',
      `user_id` int(11) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_homepage_banners = "CREATE TABLE `homepage_banners` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `banner_url` varchar(1000) DEFAULT NULL,
      `banner_image_path` varchar(255) DEFAULT NULL,
      `banner_order` int(11) NOT NULL DEFAULT 1,
      `banner_width` double DEFAULT NULL,
      `banner_location` varchar(100) DEFAULT 'featured_products'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_membership_plans = "CREATE TABLE `membership_plans` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title_array` text DEFAULT NULL,
      `number_of_ads` int(11) DEFAULT NULL,
      `number_of_days` int(11) DEFAULT NULL,
      `price` bigint(20) DEFAULT NULL,
      `is_free` tinyint(1) DEFAULT 0,
      `is_unlimited_number_of_ads` tinyint(1) DEFAULT 0,
      `is_unlimited_time` tinyint(1) DEFAULT 0,
      `features_array` text DEFAULT NULL,
      `plan_order` smallint(6) DEFAULT 1,
      `is_popular` tinyint(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_membership_transactions = "CREATE TABLE `membership_transactions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int(11) DEFAULT NULL,
      `plan_id` int(11) DEFAULT NULL,
      `plan_title` varchar(500) DEFAULT NULL,
      `payment_method` varchar(100) DEFAULT NULL,
      `payment_id` varchar(255) DEFAULT NULL,
      `payment_amount` varchar(50) DEFAULT NULL,
      `currency` varchar(20) DEFAULT NULL,
      `payment_status` varchar(50) DEFAULT NULL,
      `ip_address` varchar(100) DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_users_membership_plans = "CREATE TABLE `users_membership_plans` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int(11) DEFAULT NULL,
      `plan_title` varchar(500) DEFAULT NULL,
      `number_of_ads` int(11) DEFAULT NULL,
      `number_of_days` int(11) DEFAULT NULL,
      `price` bigint(20) DEFAULT NULL,
      `currency` varchar(20) DEFAULT 'USD',
      `is_free` tinyint(1) DEFAULT 0,
      `is_unlimited_number_of_ads` tinyint(1) DEFAULT 0,
      `is_unlimited_time` tinyint(1) DEFAULT 0,
      `payment_method` varchar(50) DEFAULT NULL,
      `payment_status` varchar(50) DEFAULT NULL,
      `plan_status` tinyint(1) DEFAULT 0,
      `plan_start_date` timestamp NULL DEFAULT NULL,
      `plan_end_date` timestamp NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_routes = "CREATE TABLE `routes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `route_key` varchar(100) DEFAULT NULL,
      `route` varchar(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_blog_images);
    mysqli_query($connection, $table_homepage_banners);
    mysqli_query($connection, $table_membership_plans);
    mysqli_query($connection, $table_membership_transactions);
    mysqli_query($connection, $table_users_membership_plans);
    mysqli_query($connection, $table_routes);

    sleep(1);
    mysqli_query($connection, "ALTER TABLE categories ADD COLUMN `featured_order` mediumint(9) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE categories CHANGE `show_on_homepage` `is_featured` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE categories ADD COLUMN `show_products_on_index` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE categories DROP INDEX idx_show_on_homepage;");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_is_featured (is_featured);");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_show_products_on_index (show_products_on_index);");

    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_conditions`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_conditions_required`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `quantity`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `quantity_required`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_location_required`;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `membership_plans_system` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings CHANGE `index_categories` `featured_categories` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `sort_categories` varchar(30) DEFAULT 'category_order';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `sort_parent_categories_by_order` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `pwa_status` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `last_cron_update` timestamp NULL DEFAULT NULL");

    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_email` varchar(255);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_phone_number` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_country` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_state` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_city` varchar(100);");
    mysqli_query($connection, "ALTER TABLE languages CHANGE `ckeditor_lang` `text_editor_lang` varchar(10) DEFAULT 'en';");

    mysqli_query($connection, "ALTER TABLE payment_settings CHANGE `default_product_currency` `default_currency` varchar(30) DEFAULT 'USD';");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `promoted_products_payment_currency`;");
    mysqli_query($connection, "ALTER TABLE payment_settings ADD COLUMN `stripe_locale` varchar(30) DEFAULT 'auto';");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `iyzico_type`;");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `iyzico_submerchant_key`;");

    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `is_membership_plan_expired` TINYINT(1) DEFAULT 0;");

    /*==============================UPDATE PRODUCTS==============================*/
    $table_product_details = "CREATE TABLE `product_details` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `product_id` int(11) DEFAULT NULL,
      `lang_id` tinyint(4) DEFAULT NULL,
      `title` varchar(500) DEFAULT NULL,
      `description` longtext DEFAULT NULL,
      `seo_title` varchar(500) DEFAULT NULL,
      `seo_description` varchar(500) DEFAULT NULL,
      `seo_keywords` varchar(500) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    mysqli_query($connection, $table_product_details);
    //update products
    $lang_id = 1;
    $products = mysqli_query($connection, "SELECT * FROM products");
    while ($product = mysqli_fetch_array($products)) {
        $languages = mysqli_query($connection, "SELECT * FROM languages ORDER BY id");
        while ($language = mysqli_fetch_array($languages)) {
            mysqli_query($connection, "INSERT INTO `product_details` (`product_id`, `lang_id`, `title`, `description`, `seo_title`, `seo_description`, `seo_keywords`) 
        VALUES (" . $product['id'] . ", '" . $language['id'] . "', '" . $product['title'] . "', '" . $product['description'] . "', '', '', '');");
        }
    }

    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `title`;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `description`;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `product_condition`;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `is_special_offer` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `special_offer_date` timestamp NULL DEFAULT NULL");
    mysqli_query($connection, "ALTER TABLE products CHANGE `hit` `pageviews` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `stock_unlimited`;");
    mysqli_query($connection, "ALTER TABLE product_details ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE product_details ADD INDEX idx_lang_id (lang_id);");
    /*==============================UPDATE PRODUCTS END==============================*/


    /*==============================UPDATE CUSTOM FIELDS==============================*/
    mysqli_query($connection, "RENAME TABLE custom_fields_options TO custom_fields_options1;");
    $table_custom_fields_options = "CREATE TABLE `custom_fields_options` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `field_id` int(11) DEFAULT NULL,
      `option_key` varchar(500) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_options_lang = "CREATE TABLE `custom_fields_options_lang` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `option_id` int(11) DEFAULT NULL,
      `lang_id` int(11) DEFAULT NULL,
      `option_name` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_custom_fields_options);
    mysqli_query($connection, $table_options_lang);
    mysqli_query($connection, "ALTER TABLE custom_fields ADD COLUMN `name_array` text;");
    mysqli_query($connection, "ALTER TABLE custom_fields ADD COLUMN `sort_options` varchar(30) DEFAULT 'alphabetically';");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD COLUMN `selected_option_id` INT;");

    //update custom fields name_array
    $custom_fields = mysqli_query($connection, "SELECT * FROM custom_fields");
    if (!empty($custom_fields)) {
        while ($custom_field = mysqli_fetch_array($custom_fields)) {
            $custom_field_langs = mysqli_query($connection, "SELECT * FROM custom_fields_lang WHERE field_id=" . $custom_field['id']);
            $array_names = array();
            if (!empty($custom_field_langs)) {
                while ($custom_field_lang = mysqli_fetch_array($custom_field_langs)) {
                    $item = array(
                        'lang_id' => intval(@$custom_field_lang["lang_id"]),
                        'name' => @$custom_field_lang["name"]
                    );
                    array_push($array_names, $item);
                }
            }
            if (!empty($array_names)) {
                $array_names = @serialize($array_names);
                mysqli_query($connection, "UPDATE `custom_fields` SET `name_array`='" . $array_names . "' WHERE id=" . $custom_field['id']);
            }
        }
    }
    mysqli_query($connection, "DROP TABLE `custom_fields_lang`;");

    //fill custom_fields_options_lang table
    $common_ids = mysqli_query($connection, "SELECT common_id FROM custom_fields_options1 GROUP BY common_id");
    while ($common_id = mysqli_fetch_array($common_ids)) {
        $fields_options = mysqli_query($connection, "SELECT * FROM custom_fields_options1 WHERE common_id='" . $common_id['common_id'] . "'");
        $i = 0;
        $last_insert_id = 0;
        while ($fields_option = mysqli_fetch_array($fields_options)) {
            if ($i == 0) {
                $option_key = $fields_option['field_option'];
                mysqli_query($connection, "INSERT INTO `custom_fields_options` (`field_id`, `option_key`) VALUES (" . $fields_option['field_id'] . ", '" . $option_key . "');");
                $last_insert_id = mysqli_insert_id($connection);
            }
            mysqli_query($connection, "INSERT INTO `custom_fields_options_lang` (`option_id`, `lang_id`, `option_name`) VALUES (" . $last_insert_id . ", '" . $fields_option['lang_id'] . "', '" . $fields_option['field_option'] . "');");
            $i++;
        }
        //update selected options id
        mysqli_query($connection, "UPDATE `custom_fields_product` SET `selected_option_id` = " . $last_insert_id . " WHERE selected_option_common_id= '" . $common_id['common_id'] . "';");
    }
    mysqli_query($connection, "DROP TABLE `custom_fields_options1`;");
    mysqli_query($connection, "ALTER TABLE custom_fields_product DROP COLUMN `selected_option_common_id`;");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_option_key (option_key);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options_lang ADD INDEX idx_option_id (option_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options_lang ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD INDEX idx_selected_option_id (selected_option_id);");
    /*==============================UPDATE CUSTOM FIELDS END==============================*/

    //add routes
    $sql_routes = "INSERT INTO `routes` (`id`, `route_key`, `route`) VALUES(1, 'admin', 'admin'), (2, 'blog', 'blog'), (3, 'tag', 'tag'), (4, 'quote_requests', 'quote-requests'), (5, 'cart', 'cart'), (6, 'shipping', 'shipping'), (7, 'payment_method', 'payment-method'), (8, 'payment', 'payment'), (9, 'promote_payment_completed', 'promote-payment-completed'),
(10, 'orders', 'orders'), (11, 'order_details', 'order-details'), (12, 'order_completed', 'order-completed'), (13, 'completed_orders', 'completed-orders'), (14, 'messages', 'messages'), (15, 'conversation', 'conversation'), (16, 'dashboard', 'dashboard'), (17, 'profile', 'profile'), (18, 'wishlist', 'wishlist'),
(19, 'settings', 'settings'), (20, 'update_profile', 'update-profile'), (21, 'followers', 'followers'), (22, 'following', 'following'), (23, 'sales', 'sales'), (24, 'sale', 'sale'), (25, 'product', 'product'), (26, 'add_product', 'add-product'), (27, 'start_selling', 'start-selling'), (28, 'products', 'products'),
(29, 'product_details', 'product-details'), (30, 'edit_product', 'edit-product'), (31, 'pending_products', 'pending-products'), (32, 'hidden_products', 'hidden-products'), (33, 'latest_products', 'latest-products'), (34, 'featured_products', 'featured-products'), (35, 'drafts', 'drafts'), (36, 'bulk_product_upload', 'bulk-product-upload'),
(37, 'downloads', 'downloads'), (38, 'seller', 'seller'), (39, 'earnings', 'earnings'), (40, 'withdraw_money', 'withdraw-money'), (41, 'payouts', 'payouts'), (42, 'set_payout_account', 'set-payout-account'), (43, 'comments', 'comments'), (44, 'reviews', 'reviews'), (45, 'category', 'category'), (46, 'completed_sales', 'completed-sales'), (47, 'shop_settings', 'shop-settings'),
(48, 'personal_information', 'personal-information'), (49, 'shipping_address', 'shipping-address'), (50, 'social_media', 'social-media'), (51, 'search', 'search'), (52, 'register', 'register'), (53, 'members', 'members'), (54, 'forgot_password', 'forgot-password'), (55, 'change_password', 'change-password'), (56, 'reset_password', 'reset-password'),
(57, 'rss_feeds', 'rss-feeds'), (58, 'terms_conditions', 'terms-conditions'), (59, 'contact', 'contact'), (60, 'select_membership_plan', 'select-membership-plan'), (61, 'membership_payment_completed', 'membership-payment-completed'), (62, 'payment_history', 'payment-history'), (63, 'expired_products', 'expired-products');";
    mysqli_query($connection, $sql_routes);

    //delete translations
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'add_iframe'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'add_product_condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'checkout_form'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'company_title'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'create_key'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'default_product_currency'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'edit_product_condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'free_listing'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'free_plan'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'good'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'homepage_order'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_blog_slider'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_categories'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_featured_products'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_latest_products'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_featured_products_count'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_latest_products_count'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'new_with_tags'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'product_conditions'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'satisfactory'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'sent_quote_requests'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'show_on_homepage'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'submerchant'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'submerchant_key'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'tax_number'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'tax_office'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'very_good'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'view_profile'");

    //add translations
    $t = array();
    $t['time_limit_for_plan'] = "A time limit for the plan";
    $t['add_banner'] = "Add Banner";
    $t['add_feature'] = "Add Feature";
    $t['add_to_special_offers'] = "Add to Special Offers";
    $t['address_2'] = "Address 2";
    $t['alphabetically'] = "Alphabetically";
    $t['banner_width'] = "Banner Width";
    $t['blog_slider'] = "Blog Slider";
    $t['bulk_category_upload'] = "Bulk Category Upload";
    $t['bulk_product_upload'] = "Bulk Product Upload";
    $t['by_category_order'] = "by Category Order";
    $t['by_date'] = "by Date";
    $t['category_id_finder'] = "Category Id Finder";
    $t['create_new_plan'] = "Create a New Plan";
    $t['csv_file'] = "CSV File";
    $t['current_plan'] = "Current Plan";
    $t['dashboard'] = "Dashboard";
    $t['data_type'] = "Data Type";
    $t['default_currency'] = "Default Currency";
    $t['digital_file_required'] = "Digital file is required!";
    $t['documentation'] = "Documentation";
    $t['download_csv_example'] = "Download CSV Example";
    $t['download_csv_template'] = "Download CSV Template";
    $t['draft_added'] = "Draft added successfully!";
    $t['duration'] = "Duration";
    $t['edit_banner'] = "Edit Banner";
    $t['edit_plan'] = "Edit Plan";
    $t['email_status'] = "Email Status";
    $t['example'] = "Example";
    $t['expired_products'] = "Expired Products";
    $t['export'] = "Export";
    $t['feature'] = "Feature";
    $t['featured_categories'] = "Featured Categories";
    $t['featured_products_system'] = "Featured Products System";
    $t['features'] = "Features";
    $t['field'] = "Field";
    $t['general_information'] = "General Information";
    $t['generate'] = "Generate";
    $t['go_back_to_products'] = "Go Back to the Products Page";
    $t['go_back_to_shop_settings'] = "Go Back to the Shop Settings";
    $t['help_documents'] = "Help Documents";
    $t['hi'] = "Hi";
    $t['homepage_banners'] = "Homepage Banners";
    $t['homepage_manager'] = "Homepage Manager";
    $t['pwa_warning'] = "If you enable PWA option, read \'Progressive Web App (PWA)\' section from the documentation to make the necessary settings.";
    $t['import_language'] = "Import Language";
    $t['json_language_file'] = "JSON Language File";
    $t['latest_sales'] = "Latest Sales";
    $t['left_to_right'] = "Left to Right (LTR)";
    $t['management_tools'] = "Management Tools";
    $t['membership'] = "Membership";
    $t['membership_payments'] = "Membership Payments";
    $t['membership_plan'] = "Membership Plan";
    $t['membership_plan_payment'] = "Membership Plan Payment";
    $t['membership_plans'] = "Membership Plans";
    $t['membership_transactions'] = "Membership Transactions";
    $t['monthly_sales'] = "Monthly sales";
    $t['membership_number_of_ads'] = "Number of Active Ads";
    $t['number_of_ads'] = "Number of Ads";
    $t['number_of_days'] = "Number of Days";
    $t['number_of_entries'] = "Number of Entries";
    $t['number_featured_products'] = "Number of Featured Products to Show";
    $t['number_latest_products'] = "Number of Latest Products to Show";
    $t['number_remaining_ads'] = "Number of Remaining Ads";
    $t['product_approve_published'] = "Once it is approved, it will be published on the site.";
    $t['payment_history'] = "Payment History";
    $t['plan_expiration_date'] = "Plan Expiration Date";
    $t['popular'] = "Popular";
    $t['product_added'] = "Product added successfully!";
    $t['product_promotion'] = "Product Promotion";
    $t['products_by_category'] = "Products by Category";
    $t['pwa'] = "Progressive Web App (PWA)";
    $t['promotion_payments'] = "Promotion Payments";
    $t['remove_from_special_offers'] = "Remove from Special Offers";
    $t['renew_your_plan'] = "Renew Your Plan";
    $t['reset'] = "Reset";
    $t['reset_filters'] = "Reset Filters";
    $t['reset_location'] = "Reset Location";
    $t['right_to_left'] = "Right to Left (RTL)";
    $t['sale_id'] = "Sale Id";
    $t['products_by_category_exp'] = "Select the categories you want to show their products";
    $t['featured_categories_exp'] = "Select the categories you want to show under the slider";
    $t['select_your_plan_exp'] = "Select your membership plan to continue";
    $t['select_your_plan'] = "Select Your Plan";
    $t['seo'] = "SEO";
    $t['set_as_default'] = "Set as Default";
    $t['sold'] = "Sold";
    $t['sort_categories'] = "Sort Categories";
    $t['sort_options'] = "Sort Options";
    $t['sort_parent_categories_by_category_order'] = "Sort Parent Categories by Category Order";
    $t['warning_category_sort'] = "Sorting with drag and drop will be active only when the 'by Category Order' option is selected.";
    $t['special_offers'] = "Special Offers";
    $t['text_direction'] = "Text Direction";
    $t['banner_location_exp'] = "The banner will be added under the selected section";
    $t['the_operation_completed'] = "The operation completed successfully!";
    $t['msg_request_sent'] = "The request has been sent successfully!";
    $t['iyzico_warning'] = 'This is the "Checkout Form" integration, not the "Marketplace" integration.';
    $t['msg_product_slug_used'] = "This slug is used by another product!";
    $t['unlimited'] = "Unlimited";
    $t['vendor'] = "Vendor";
    $t['msg_expired_plan'] = 'When your plan expires, if you do not renew your plan within 3 days, your ads will be added to the "Expired Products" section and will not be displayed on the site.';
    $t['bulk_category_upload_exp'] = "You can add your categories with a CSV file from this section";
    $t['bulk_product_upload_exp'] = "You can add your products with a CSV file from this section";
    $t['homepage_banners_exp'] = "You can manage the product banners on the homepage from this section";
    $t['help_documents_exp'] = "You can use these documents to generate your CSV file";
    $t['category_id_finder_exp'] = "You can use this section to find out the Id of a category";
    $t['do_not_have_membership_plan'] = "You do not have a membership plan. Click the button below to buy a membership plan.";
    $t['msg_reached_ads_limit'] = "You have reached your ad adding limit! If you want to add more ads, you can upgrade your current plan by clicking the button below.";
    $t['msg_accept_terms'] = "You have to accept the terms!";
    $t['msg_membership_renewed'] = "Your membership plan has been successfully renewed!";
    $t['msg_plan_expired'] = "Your membership plan has expired!";
    add_lang_trans($connection, $t);
    mysqli_query($connection, "UPDATE general_settings SET version='1.7.1' WHERE id='1'");
}

function update_15_to_16($license_code, $purchase_code, $connection)
{
    $table_sessions = "CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id` varchar(128) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `timestamp` int(10) unsigned DEFAULT 0 NOT NULL,
    `data` blob NOT NULL,
    KEY `ci_sessions_timestamp` (`timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_fonts = "CREATE TABLE `fonts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `font_name` varchar(255) DEFAULT NULL,
    `font_url` varchar(2000) DEFAULT NULL,
    `font_family` varchar(500) DEFAULT NULL,
    `is_default` tinyint(1) DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_images_variation = "CREATE TABLE `images_variation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` int(11) DEFAULT NULL,
    `variation_option_id` int(11) DEFAULT '0',
    `image_default` varchar(255) DEFAULT NULL,
    `image_big` varchar(255) DEFAULT NULL,
    `image_small` varchar(255) DEFAULT NULL,
    `is_main` tinyint(1) DEFAULT '0',
    `storage` varchar(20) DEFAULT 'local'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_invoices = "CREATE TABLE `invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` bigint(20) DEFAULT NULL,
    `order_number` bigint(20) DEFAULT NULL,
    `client_username` varchar(255) DEFAULT NULL,
    `client_first_name` varchar(100) DEFAULT NULL,
    `client_last_name` varchar(100) DEFAULT NULL,
    `client_address` varchar(500) DEFAULT NULL,
    `invoice_items` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_language_translations = "CREATE TABLE `language_translations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lang_id` smallint(6) DEFAULT NULL,
    `label` varchar(255) DEFAULT NULL,
    `translation` varchar(500) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_routes = "CREATE TABLE `routes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin` varchar(100) DEFAULT 'admin',
    `blog` varchar(100) DEFAULT 'blog',
    `tag` varchar(100) DEFAULT 'tag',
    `quote_requests` varchar(100) DEFAULT 'quote-requests',
    `sent_quote_requests` varchar(100) DEFAULT 'sent-quote-requests',
    `cart` varchar(100) DEFAULT 'cart',
    `shipping` varchar(100) DEFAULT 'shipping',
    `payment_method` varchar(100) DEFAULT 'payment-method',
    `payment` varchar(100) DEFAULT 'payment',
    `promote_payment_completed` varchar(100) DEFAULT 'promote-payment-completed',
    `orders` varchar(100) DEFAULT 'orders',
    `order_details` varchar(100) DEFAULT 'order',
    `order_completed` varchar(100) DEFAULT 'order-completed',
    `completed_orders` varchar(100) DEFAULT 'completed-orders',
    `messages` varchar(100) DEFAULT 'messages',
    `conversation` varchar(100) DEFAULT 'conversation',
    `profile` varchar(100) DEFAULT 'profile',
    `wishlist` varchar(100) DEFAULT 'wishlist',
    `settings` varchar(100) DEFAULT 'settings',
    `update_profile` varchar(100) DEFAULT 'update-profile',
    `followers` varchar(100) DEFAULT 'followers',
    `following` varchar(100) DEFAULT 'following',
    `sales` varchar(100) DEFAULT 'sales',
    `sale` varchar(100) DEFAULT 'sale',
    `sell_now` varchar(100) DEFAULT 'sell-now',
    `start_selling` varchar(100) DEFAULT 'start-selling',
    `products` varchar(100) DEFAULT 'products',
    `product_details` varchar(100) DEFAULT 'product-details',
    `edit_product` varchar(100) DEFAULT 'edit_product',
    `promote_product` varchar(100) DEFAULT 'promote-product',
    `pending_products` varchar(100) DEFAULT 'pending-products',
    `hidden_products` varchar(100) DEFAULT 'hidden-products',
    `latest_products` varchar(100) DEFAULT 'latest-products',
    `featured_products` varchar(100) DEFAULT 'featured-products',
    `drafts` varchar(100) DEFAULT 'drafts',
    `downloads` varchar(100) DEFAULT 'downloads',
    `seller` varchar(100) DEFAULT 'seller',
    `earnings` varchar(100) DEFAULT 'earnings',
    `payouts` varchar(100) DEFAULT 'payouts',
    `set_payout_account` varchar(100) DEFAULT 'set-payout-account',
    `pricing` varchar(100) DEFAULT 'pricing',
    `reviews` varchar(100) DEFAULT 'reviews',
    `category` varchar(100) DEFAULT 'category',
    `completed_sales` varchar(100) DEFAULT 'completed-sales',
    `shop_settings` varchar(100) DEFAULT 'shop-settings',
    `personal_information` varchar(100) DEFAULT 'personal-information',
    `shipping_address` varchar(100) DEFAULT 'shipping-address',
    `social_media` varchar(100) DEFAULT 'social-media',
    `search` varchar(100) DEFAULT 'search',
    `register` varchar(100) DEFAULT 'register',
    `members` varchar(100) DEFAULT 'members',
    `forgot_password` varchar(100) DEFAULT 'forgot-password',
    `change_password` varchar(100) DEFAULT 'change-password',
    `reset_password` varchar(100) DEFAULT 'reset-password',
    `rss_feeds` varchar(100) DEFAULT 'rss-feeds'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_variations = "CREATE TABLE `variations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` int(11) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `parent_id` int(11) DEFAULT '0',
    `label_names` text,
    `variation_type` varchar(50) DEFAULT NULL,
    `insert_type` varchar(10) DEFAULT 'new',
    `option_display_type` varchar(30) DEFAULT 'text',
    `show_images_on_slider` tinyint(1) DEFAULT '0',
    `use_different_price` tinyint(1) DEFAULT '0',
    `is_visible` tinyint(1) DEFAULT '1'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_variation_options = "CREATE TABLE `variation_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `variation_id` int(11) DEFAULT NULL,
    `parent_id` int(11) DEFAULT '0',
    `option_names` text,
    `stock` int(11) DEFAULT NULL,
    `color` varchar(30) DEFAULT NULL,
    `price` bigint(20) DEFAULT NULL,
    `discount_rate` smallint(3) DEFAULT NULL,
    `is_default` tinyint(1) DEFAULT '0',
    `use_default_price` tinyint(1) NOT NULL DEFAULT '0',
    `no_discount` tinyint(1) NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_sessions);
    mysqli_query($connection, $table_fonts);
    mysqli_query($connection, $table_images_variation);
    mysqli_query($connection, $table_invoices);
    mysqli_query($connection, $table_language_translations);
    mysqli_query($connection, $table_routes);
    mysqli_query($connection, $sql_variations);
    mysqli_query($connection, $sql_variation_options);
    sleep(1);

    mysqli_query($connection, "UPDATE `ad_spaces` SET `ad_space`='product_bottom' WHERE ad_space='product_sidebar';");
    mysqli_query($connection, "ALTER TABLE blog_comments ADD COLUMN `ip_address` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE blog_comments ADD COLUMN `status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE categories CHANGE `image_1` `image` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE categories DROP COLUMN `image_2`;");
    mysqli_query($connection, "ALTER TABLE comments ADD COLUMN `ip_address` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE comments ADD COLUMN `status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "RENAME TABLE countries TO location_countries;");
    mysqli_query($connection, "ALTER TABLE location_countries ADD COLUMN `status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "RENAME TABLE favorites TO wishlist;");
    mysqli_query($connection, "ALTER TABLE form_settings ADD COLUMN `digital_allowed_file_extensions` VARCHAR(500) DEFAULT 'zip';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `vat_status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings CHANGE `head_code` `custom_css_codes` MEDIUMTEXT;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `custom_javascript_codes` MEDIUMTEXT;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `vk_app_id` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `vk_secure_key` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings DROP COLUMN `default_product_location`;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `comment_approval_system` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings CHANGE `product_reviews` `reviews` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings DROP COLUMN `user_reviews`;");
    mysqli_query($connection, "DROP TABLE `user_reviews`;");
    mysqli_query($connection, "ALTER TABLE general_settings CHANGE `index_slider` `slider_status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `slider_type` VARCHAR(30) DEFAULT 'full_width';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `slider_effect` VARCHAR(30) DEFAULT 'fade';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `hide_vendor_contact_information` TINYINT(1) DEFAULT 0;");

    mysqli_query($connection, "RENAME TABLE cities TO location_cities;");
    mysqli_query($connection, "RENAME TABLE states TO location_states;");

    mysqli_query($connection, "ALTER TABLE orders ADD COLUMN `price_vat` BIGINT(20) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE order_products ADD COLUMN `product_vat_rate` SMALLINT(3) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE order_products ADD COLUMN `product_vat` BIGINT(20) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE order_products ADD COLUMN `variation_option_ids` VARCHAR(255);");

    mysqli_query($connection, "ALTER TABLE pages ADD COLUMN `page_default_name` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE pages DROP COLUMN `link`;");
    mysqli_query($connection, "ALTER TABLE pages ADD COLUMN `is_custom` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE pages CHANGE `page_content` `page_content` LONGTEXT;");

    mysqli_query($connection, "ALTER TABLE payment_settings ADD COLUMN `space_between_money_currency` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE payment_settings ADD COLUMN `iyzico_type` VARCHAR(50) DEFAULT 'checkout_form';");
    mysqli_query($connection, "ALTER TABLE payment_settings ADD COLUMN `iyzico_submerchant_key` VARCHAR(255);");


    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `sku` VARCHAR(100);");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `discount_rate` SMALLINT(3) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `vat_rate` SMALLINT(3) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `shipping_cost_additional` BIGINT(20) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products CHANGE `quantity` `stock` INT(11) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `is_sold`;");
    mysqli_query($connection, "ALTER TABLE products CHANGE `description` `description` LONGTEXT;");

    mysqli_query($connection, "ALTER TABLE reviews ADD COLUMN `ip_address` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE settings ADD COLUMN `site_font` SMALLINT(6) DEFAULT 19;");

    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `title` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `description` VARCHAR(1000);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `button_text` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `animation_title` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `animation_description` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `animation_button` VARCHAR(50);");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `text_color` VARCHAR(30) DEFAULT '#ffffff';");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `button_color` VARCHAR(30) DEFAULT '#222222';");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `button_text_color` VARCHAR(30) DEFAULT '#ffffff';");
    mysqli_query($connection, "ALTER TABLE slider DROP COLUMN `image_small`;");

    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `vkontakte_id` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `first_name` VARCHAR(100);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `last_name` VARCHAR(100);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `version` VARCHAR(30) DEFAULT '1.6.2';");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `stock_unlimited` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `image_mobile` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE earnings ADD COLUMN `order_product_id` INT;");
    sleep(1);

    $sql_fonts = "INSERT INTO `fonts` (`id`, `font_name`, `font_url`, `font_family`, `is_default`) VALUES
(1, 'Arial', NULL, 'font-family: Arial, Helvetica, sans-serif', 1),
(2, 'Arvo', '<link href=\"https://fonts.googleapis.com/css?family=Arvo:400,700&display=swap\" rel=\"stylesheet\">\r\n', 'font-family: \"Arvo\", Helvetica, sans-serif', 0),
(3, 'Averia Libre', '<link href=\"https://fonts.googleapis.com/css?family=Averia+Libre:300,400,700&display=swap\" rel=\"stylesheet\">\r\n', 'font-family: \"Averia Libre\", Helvetica, sans-serif', 0),
(4, 'Bitter', '<link href=\"https://fonts.googleapis.com/css?family=Bitter:400,400i,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Bitter\", Helvetica, sans-serif', 0),
(5, 'Cabin', '<link href=\"https://fonts.googleapis.com/css?family=Cabin:400,500,600,700&display=swap&subset=latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Cabin\", Helvetica, sans-serif', 0),
(6, 'Cherry Swash', '<link href=\"https://fonts.googleapis.com/css?family=Cherry+Swash:400,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Cherry Swash\", Helvetica, sans-serif', 0),
(7, 'Encode Sans', '<link href=\"https://fonts.googleapis.com/css?family=Encode+Sans:300,400,500,600,700&display=swap&subset=latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Encode Sans\", Helvetica, sans-serif', 0),
(8, 'Helvetica', NULL, 'font-family: Helvetica, sans-serif', 1),
(9, 'Hind', '<link href=\"https://fonts.googleapis.com/css?family=Hind:300,400,500,600,700&display=swap&subset=devanagari,latin-ext\" rel=\"stylesheet\">', 'font-family: \"Hind\", Helvetica, sans-serif', 0),
(10, 'Josefin Sans', '<link href=\"https://fonts.googleapis.com/css?family=Josefin+Sans:300,400,600,700&display=swap&subset=latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Josefin Sans\", Helvetica, sans-serif', 0),
(11, 'Kalam', '<link href=\"https://fonts.googleapis.com/css?family=Kalam:300,400,700&display=swap&subset=devanagari,latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Kalam\", Helvetica, sans-serif', 0),
(12, 'Khula', '<link href=\"https://fonts.googleapis.com/css?family=Khula:300,400,600,700&display=swap&subset=devanagari,latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Khula\", Helvetica, sans-serif', 0),
(13, 'Lato', '<link href=\"https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">', 'font-family: \"Lato\", Helvetica, sans-serif', 0),
(14, 'Lora', '<link href=\"https://fonts.googleapis.com/css?family=Lora:400,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Lora\", Helvetica, sans-serif', 0),
(15, 'Merriweather', '<link href=\"https://fonts.googleapis.com/css?family=Merriweather:300,400,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Merriweather\", Helvetica, sans-serif', 0),
(16, 'Montserrat', '<link href=\"https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Montserrat\", Helvetica, sans-serif', 0),
(17, 'Mukta', '<link href=\"https://fonts.googleapis.com/css?family=Mukta:300,400,500,600,700&display=swap&subset=devanagari,latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Mukta\", Helvetica, sans-serif', 0),
(18, 'Nunito', '<link href=\"https://fonts.googleapis.com/css?family=Nunito:300,400,600,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Nunito\", Helvetica, sans-serif', 0),
(19, 'Open Sans', '<link href=\"https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese\" rel=\"stylesheet\">', 'font-family: \"Open Sans\", Helvetica, sans-serif', 0),
(20, 'Oswald', '<link href=\"https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese\" rel=\"stylesheet\">', 'font-family: \"Oswald\", Helvetica, sans-serif', 0),
(21, 'Oxygen', '<link href=\"https://fonts.googleapis.com/css?family=Oxygen:300,400,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Oxygen\", Helvetica, sans-serif', 0),
(22, 'Poppins', '<link href=\"https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap&subset=devanagari,latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Poppins\", Helvetica, sans-serif', 0),
(23, 'PT Sans', '<link href=\"https://fonts.googleapis.com/css?family=PT+Sans:400,700&display=swap&subset=cyrillic,cyrillic-ext,latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"PT Sans\", Helvetica, sans-serif', 0),
(24, 'Raleway', '<link href=\"https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">\r\n', 'font-family: \"Raleway\", Helvetica, sans-serif', 0),
(25, 'Roboto', '<link href=\"https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese\" rel=\"stylesheet\">', 'font-family: \"Roboto\", Helvetica, sans-serif', 0),
(26, 'Roboto Condensed', '<link href=\"https://fonts.googleapis.com/css?family=Roboto+Condensed:300,400,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Roboto Condensed\", Helvetica, sans-serif', 0),
(27, 'Roboto Slab', '<link href=\"https://fonts.googleapis.com/css?family=Roboto+Slab:300,400,500,600,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Roboto Slab\", Helvetica, sans-serif', 0),
(28, 'Rokkitt', '<link href=\"https://fonts.googleapis.com/css?family=Rokkitt:300,400,500,600,700&display=swap&subset=latin-ext,vietnamese\" rel=\"stylesheet\">\r\n', 'font-family: \"Rokkitt\", Helvetica, sans-serif', 0),
(29, 'Source Sans Pro', '<link href=\"https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese\" rel=\"stylesheet\">', 'font-family: \"Source Sans Pro\", Helvetica, sans-serif', 0),
(30, 'Titillium Web', '<link href=\"https://fonts.googleapis.com/css?family=Titillium+Web:300,400,600,700&display=swap&subset=latin-ext\" rel=\"stylesheet\">', 'font-family: \"Titillium Web\", Helvetica, sans-serif', 0),
(31, 'Ubuntu', '<link href=\"https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700&display=swap&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext\" rel=\"stylesheet\">', 'font-family: \"Ubuntu\", Helvetica, sans-serif', 0),
(32, 'Verdana', NULL, 'font-family: Verdana, Helvetica, sans-serif', 1),
(33, 'Work Sans', '<link href=\"https://fonts.googleapis.com/css?family=Work+Sans:300,400,500,600&display=swap&subset=latin-ext,vietnamese\" rel=\"stylesheet\"> ', 'font-family: \"Work Sans\", Helvetica, sans-serif', 0),
(34, 'Libre Baskerville', '<link href=\"https://fonts.googleapis.com/css?family=Libre+Baskerville:400,400i&display=swap&subset=latin-ext\" rel=\"stylesheet\"> ', 'font-family: \"Libre Baskerville\", Helvetica, sans-serif', 0),
(35, 'Signika', '<link href=\"https://fonts.googleapis.com/css2?family=Signika:wght@300;400;600;700&display=swap\" rel=\"stylesheet\">', 'font-family: \'Signika\', sans-serif;', 0);";
    mysqli_query($connection, $sql_fonts);

    $sql_routes = "INSERT INTO `routes` (`id`, `admin`, `blog`, `tag`, `quote_requests`, `sent_quote_requests`, `cart`, `shipping`, `payment_method`, `payment`, `promote_payment_completed`, `orders`, `order_details`, `order_completed`, `completed_orders`, `messages`, `conversation`, `profile`, `wishlist`, `settings`, `update_profile`, `followers`, `following`, `sales`, `sale`, `sell_now`, `start_selling`, `products`, `product_details`, `edit_product`, `promote_product`, `pending_products`, `hidden_products`, `latest_products`, `featured_products`, `drafts`, `downloads`, `seller`, `earnings`, `payouts`, `set_payout_account`, `pricing`, `reviews`, `category`, `completed_sales`, `shop_settings`, `personal_information`, `shipping_address`, `social_media`, `search`, `register`, `members`, `forgot_password`, `change_password`, `reset_password`, `rss_feeds`) VALUES
(1, 'admin', 'blog', 'tag', 'quote-requests', 'sent-quote-requests', 'cart', 'shipping', 'payment-method', 'payment', 'promote-payment-completed', 'orders', 'order-details', 'order-completed', 'completed-orders', 'messages', 'conversation', 'profile', 'wishlist', 'settings', 'update-profile', 'followers', 'following', 'sales', 'sale', 'sell-now', 'start-selling', 'products', 'product-details', 'edit-product', 'promote-product', 'pending-products', 'hidden-products', 'latest-products', 'featured-products', 'drafts', 'downloads', 'seller', 'earnings', 'payouts', 'set-payout-account', 'pricing', 'reviews', 'category', 'completed-sales', 'shop-settings', 'personal-information', 'shipping-address', 'social-media', 'search', 'register', 'members', 'forgot-password', 'change-password', 'reset-password', 'rss-feeds');";
    mysqli_query($connection, $sql_routes);


    //add pages
    $sql = "SELECT * FROM languages ORDER BY id";
    $result = mysqli_query($connection, $sql);
    while ($row = mysqli_fetch_array($result)) {
        $sql_pages = "INSERT INTO `pages` (`lang_id`, `title`, `slug`, `description`, `keywords`, `page_content`, `page_order`, `visibility`, `title_active`, `location`, `is_custom`, `page_default_name`) VALUES
(" . $row['id'] . ", 'Contact', 'contact', 'Contact Page', 'Contact, Page', NULL, 1, 1, 1, 'top_menu', 0, 'contact'),
(" . $row['id'] . ", 'Blog', 'blog', 'Blog Page', 'Blog, Page', NULL, 1, 1, 1, 'quick_links', 0, 'blog');";
        mysqli_query($connection, $sql_pages);
    }
    mysqli_query($connection, "UPDATE `pages` SET `page_default_name`='terms_conditions', `is_custom`=0 WHERE slug='terms-conditions';");

    //add variations
    $sql = "SELECT * FROM product_variations ORDER BY id";
    $result = mysqli_query($connection, $sql);
    $used_common_ids = array();
    while ($row = mysqli_fetch_array($result)) {
        if (!in_array($row['common_id'], $used_common_ids)) {
            array_push($used_common_ids, $row['common_id']);
            $label_names = generate_variation_label_array($connection, $row['common_id']);

            $insert_variation = "INSERT INTO `variations` (`product_id`, `user_id`, `parent_id`, `label_names`, `variation_type`, `insert_type`, `option_display_type`, `show_images_on_slider`, `is_visible`) 
            VALUES (" . $row['product_id'] . ", " . $row['user_id'] . ", 0, '" . $label_names . "', '" . $row['variation_type'] . "', '" . $row['insert_type'] . "', 'text', 0, " . $row['visible'] . ")";
            mysqli_query($connection, $insert_variation);

            $last_variation_id = $connection->insert_id;

            //add variation options
            $sql_option = "SELECT * FROM product_variations_options WHERE variation_common_id = '" . $row['common_id'] . "'  ORDER BY id";
            $result_option = mysqli_query($connection, $sql_option);
            $used_option_common_ids = array();
            while ($row_option = mysqli_fetch_array($result_option)) {

                if (!in_array($row_option['option_common_id'], $used_option_common_ids)) {
                    array_push($used_option_common_ids, $row_option['option_common_id']);
                    $option_names = generate_variation_option_names_array($connection, $row_option['option_common_id']);

                    $insert_variation_option = "INSERT INTO `variation_options` (`variation_id`, `parent_id`, `option_names`, `stock`, `color`, `price`, `discount_rate`, `is_default`, `use_default_price`, `no_discount`) 
                    VALUES (" . $last_variation_id . ", 0, '" . $option_names . "', 10, '', 0, 0, 0, 1, 0)";
                    mysqli_query($connection, $insert_variation_option);
                }
            }
        }
    }
    sleep(1);
    mysqli_query($connection, "DROP TABLE `product_variations`;");
    mysqli_query($connection, "DROP TABLE `product_variations_options`;");

    //add language translations
    $sql = "SELECT * FROM languages ORDER BY id";
    $result = mysqli_query($connection, $sql);
    while ($row = mysqli_fetch_array($result)) {
        $path = "application/language/" . $row["folder_name"] . "/site_lang.php";
        if (file_exists($path)) {
            require_once $path;
            if (!empty($lang)) {
                foreach ($lang as $key => $value) {

                    $insert_translation = "INSERT INTO `language_translations` (`lang_id`, `label`, `translation`) 
                    VALUES (" . $row["id"] . ", '" . $key . "' , '" . $value . "')";
                    mysqli_query($connection, $insert_translation);

                }
            }
        }
    }
    mysqli_query($connection, "ALTER TABLE languages DROP COLUMN `folder_name`;");
    mysqli_query($connection, "ALTER TABLE languages ADD COLUMN `flag_path` VARCHAR(255);");
    //add indexes
    mysqli_query($connection, "ALTER TABLE blog_categories ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE blog_comments ADD INDEX idx_post_id (post_id);");
    mysqli_query($connection, "ALTER TABLE blog_comments ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE blog_posts ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE blog_posts ADD INDEX idx_category_id(category_id);");
    mysqli_query($connection, "ALTER TABLE blog_tags ADD INDEX idx_post_id (post_id);");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_parent_id (parent_id);");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_visibility (visibility);");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_show_on_homepage (show_on_homepage);");
    mysqli_query($connection, "ALTER TABLE categories_lang ADD INDEX idx_category_id (category_id);");
    mysqli_query($connection, "ALTER TABLE categories_lang ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE comments ADD INDEX idx_parent_id (parent_id);");
    mysqli_query($connection, "ALTER TABLE comments ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE comments ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE conversations ADD INDEX idx_sender_id (sender_id);");
    mysqli_query($connection, "ALTER TABLE conversations ADD INDEX idx_receiver_id (receiver_id);");
    mysqli_query($connection, "ALTER TABLE conversation_messages ADD INDEX idx_conversation_id (conversation_id);");
    mysqli_query($connection, "ALTER TABLE conversation_messages ADD INDEX idx_sender_id (sender_id);");
    mysqli_query($connection, "ALTER TABLE conversation_messages ADD INDEX idx_receiver_id (receiver_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_category ADD INDEX idx_category_id (category_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_category ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_lang ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_lang ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE digital_files ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE digital_files ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE digital_sales ADD INDEX idx_order_id (order_id);");
    mysqli_query($connection, "ALTER TABLE digital_sales ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE earnings ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE followers ADD INDEX idx_following_id (following_id);");
    mysqli_query($connection, "ALTER TABLE followers ADD INDEX idx_follower_id (follower_id);");
    mysqli_query($connection, "ALTER TABLE images ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE images ADD INDEX idx_is_main (is_main);");
    mysqli_query($connection, "ALTER TABLE images_file_manager ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE images_variation ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE images_variation ADD INDEX idx_variation_option_id (variation_option_id);");
    mysqli_query($connection, "ALTER TABLE images_variation ADD INDEX idx_is_main (is_main);");
    mysqli_query($connection, "ALTER TABLE invoices ADD INDEX idx_order_id (order_id);");
    mysqli_query($connection, "ALTER TABLE language_translations ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE location_states ADD INDEX idx_country_id (country_id);");
    mysqli_query($connection, "ALTER TABLE location_cities ADD INDEX idx_country_id (country_id);");
    mysqli_query($connection, "ALTER TABLE location_cities ADD INDEX idx_state_id (state_id);");
    mysqli_query($connection, "ALTER TABLE media ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE orders ADD INDEX idx_order_number (order_number);");
    mysqli_query($connection, "ALTER TABLE orders ADD INDEX idx_buyer_id (buyer_id);");
    mysqli_query($connection, "ALTER TABLE orders ADD INDEX idx_status (status);");
    mysqli_query($connection, "ALTER TABLE order_products ADD INDEX idx_order_id (order_id);");
    mysqli_query($connection, "ALTER TABLE order_products ADD INDEX idx_seller_id (seller_id);");
    mysqli_query($connection, "ALTER TABLE order_products ADD INDEX idx_buyer_id (buyer_id);");
    mysqli_query($connection, "ALTER TABLE order_products ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE order_shipping ADD INDEX idx_order_id (order_id);");
    mysqli_query($connection, "ALTER TABLE payments ADD INDEX idx_payment_id (payment_id);");
    mysqli_query($connection, "ALTER TABLE payments ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE payments ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE payouts ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_category_id (category_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_country_id (country_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_state_id (state_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_city_id (city_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_status (status);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_is_promoted (is_promoted);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_visibility (visibility);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_is_deleted (is_deleted);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_is_draft (is_draft);");
    mysqli_query($connection, "ALTER TABLE products ADD INDEX idx_created_at (created_at);");
    mysqli_query($connection, "ALTER TABLE product_license_keys ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE quote_requests ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE quote_requests ADD INDEX idx_seller_id (seller_id);");
    mysqli_query($connection, "ALTER TABLE quote_requests ADD INDEX idx_buyer_id (buyer_id);");
    mysqli_query($connection, "ALTER TABLE reviews ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE reviews ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE transactions ADD INDEX idx_payment_id (payment_id);");
    mysqli_query($connection, "ALTER TABLE transactions ADD INDEX idx_order_id (order_id);");
    mysqli_query($connection, "ALTER TABLE transactions ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE user_payout_accounts ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE variations ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE variations ADD INDEX idx_user_id (user_id);");
    mysqli_query($connection, "ALTER TABLE variations ADD INDEX idx_parent_id (parent_id);");
    mysqli_query($connection, "ALTER TABLE variations ADD INDEX idx_is_visible (is_visible);");
    mysqli_query($connection, "ALTER TABLE variation_options ADD INDEX idx_variation_id (variation_id);");
    mysqli_query($connection, "ALTER TABLE variation_options ADD INDEX idx_parent_id (parent_id);");
    mysqli_query($connection, "ALTER TABLE wishlist ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE wishlist ADD INDEX idx_user_id (user_id);");
    //add new phrases
    $p = array();
    $p["button"] = "Button";
    $p["button_text"] = "Button Text";
    $p["animations"] = "Animations";
    $p["slider_settings"] = "Slider Settings";
    $p["effect"] = "Effect";
    $p["boxed"] = "Boxed";
    $p["top_menu"] = "Top Menu";
    $p["warning"] = "Warning";
    $p["route_settings"] = "Route Settings";
    $p["route_settings_warning"] = "You cannot use special characters in routes. If your language contains special characters, please be careful when editing routes. If you enter an invalid route, you will not be able to access the related page.";
    $p["text_color"] = "Text Color";
    $p["button_color"] = "Button Color";
    $p["button_text_color"] = "Button Text Color";
    $p["font_settings"] = "Font Settings";
    $p["site_font"] = "Site Font";
    $p["fonts"] = "Fonts";
    $p["add_font"] = "Add Font";
    $p["font_family"] = "Font Family";
    $p["update_font"] = "Update Font";
    $p["vat"] = "VAT";
    $p["vat_exp"] = "Value-Added Tax";
    $p["add_to_wishlist"] = "Add to wishlist";
    $p["remove_from_wishlist"] = "Remove from wishlist";
    $p["additional_information"] = "Additional Information";
    $p["shipping_location"] = "Shipping & Location";
    $p["you_may_also_like"] = "You may also like";
    $p["wishlist"] = "Wishlist";
    $p["allowed_file_extensions"] = "Allowed File Extensions";
    $p["type_extension"] = "Type an extension and hit enter";
    $p["invalid_file_type"] = "Invalid file type!";
    $p["flag"] = "Flag";
    $p["add_a_comment"] = "Add a comment";
    $p["comment_approval_system"] = "Comment Approval System";
    $p["pending_comments"] = "Pending Comments";
    $p["approved_comments"] = "Approved Comments";
    $p["msg_comment_approved"] = "Comment successfully approved!";
    $p["msg_comment_sent_successfully"] = "Your comment has been sent. It will be published after being reviewed by the site management.";
    $p["no_comments_found"] = "No comments found for this product. Be the first to comment!";
    $p["no_reviews_found"] = "No reviews found.";
    $p["rate_this_product"] = "Rate this product";
    $p["msg_review_added"] = "Your review has been successfully added!";
    $p["vat_included"] = "VAT Included";
    $p["product_price"] = "Product Price";
    $p["discount_rate"] = "Discount Rate";
    $p["no_discount"] = "No Discount";
    $p["calculated_price"] = "Calculated Price";
    $p["add_space_between_money_currency"] = "Add Space Between Money and Currency";
    $p["view_invoice"] = "View Invoice";
    $p["invoice"] = "Invoice";
    $p["personal_information"] = "Personal Information";
    $p["client_information"] = "Client Information";
    $p["invoice_currency_warning"] = "All amounts shown on this invoice are in";
    $p["print"] = "Print";
    $p["invoices"] = "Invoices";
    $p["view_options"] = "View Options";
    $p["option_name"] = "Option Name";
    $p["msg_option_added"] = "Option added successfully!";
    $p["use_default_price"] = "Use default price";
    $p["color"] = "Color";
    $p["stock"] = "Stock";
    $p["add_product"] = "Add Product";
    $p["edit_option"] = "Edit Option";
    $p["msg_option_exists"] = "This option already exists!";
    $p["default_option"] = "Default Option";
    $p["default_option_exp"] = "This option will be selected by default. It will use the default images and price";
    $p["sku"] = "SKU";
    $p["product_code"] = "Product Code";
    $p["option_display_type"] = "Option Display Type";
    $p["show_option_images_on_slider"] = "Show Option Images on Slider When an Option is Selected";
    $p["in_stock"] = "In Stock";
    $p["out_of_stock"] = "Out of Stock";
    $p["parent_variation"] = "Parent Variation";
    $p["parent_option"] = "Parent Option";
    $p["use_different_price_for_options"] = "Use Different Price for Options";
    $p["location_exp"] = "Modesy allows you to shop from anywhere in the world.";
    $p["select_location"] = "Select Location";
    $p["update_location"] = "Update Location";
    $p["show_all"] = "Show All";
    $p["search_products"] = "Search Products";
    $p["activate_all"] = "Activate All";
    $p["inactivate_all"] = "Inactivate All";
    $p["hide_vendor_contact_information"] = "Hide Vendor Contact Information on the Site";
    $p["online"] = "Online";
    $p["checkout_form"] = "Checkout Form";
    $p["marketplace"] = "Marketplace";
    $p["identity_number"] = "Identity Number";
    $p["submerchant"] = "Submerchant";
    $p["tax_office"] = "Tax Office";
    $p["tax_number"] = "Tax Number";
    $p["company_title"] = "Company Title";
    $p["create_key"] = "Create Key";
    $p["submerchant_key"] = "Submerchant Key";
    $p["vk_login"] = "VKontakte Login";
    $p["secure_key"] = "Secure Key";
    $p["connect_with_vk"] = "Connect with VKontakte";
    $p["edit_user"] = "Edit User";
    $p["digital_product_stock_exp"] = "Enter a high stock value for products with unlimited quantity.";
    $p["shipping_cost_per_additional_product"] = "Shipping Cost for Per Additional Product";
    $p["shipping_cost_per_additional_product_exp"] = "The shipping cost for per additional product if a buyer buys more than one of the same product";
    $p["new_arrivals"] = "New Arrivals";
    $p["featured_products"] = "Featured Products";
    $p["featured_products_exp"] = "Last added featured products";
    $p["featured"] = "Featured";
    $p["featured_badge"] = "Featured Badge";
    $p["index_featured_products"] = "Index Featured Products";
    $p["index_featured_products_count"] = "Index Number of Featured Products";
    $p["add_to_featured"] = "Add to Featured";
    $p["remove_from_featured"] = "Remove from Featured";
    $p["featured_products_transactions"] = "Featured Products Transactions";
    $p["featured_products_payment_currency"] = "Featured Products Payment Currency";
    $p["add_review"] = "Add Review";
    $p["more_from"] = "More from";
    $p["product_bottom_ad_space"] = "Product Bottom Ad Space";
    $p["adsense_head_exp"] = "The codes you add here will be added in the <head></head> tags.";
    $p["custom_css_codes"] = "Custom CSS Codes";
    $p["custom_javascript_codes"] = "Custom JavaScript Codes";
    $p["custom_javascript_codes_exp"] = "These codes will be added to the footer of the site.";
    $p["custom_css_codes_exp"] = "These codes will be added to the header of the site.";
    $p["unlimited_stock"] = "Unlimited Stock";
    $p["send_test_email"] = "Send Test Email";
    $p["send_test_email_exp"] = "You can send a test mail to check if your mail server is working.";
    add_lang_trans($connection, $p);
}

/*====================== HELPERS ======================*/
//add language translations
function add_lang_trans($connection, $array_translations)
{
    $languages = mysqli_query($connection, "SELECT * FROM languages ORDER BY language_order;");
    if (!empty($languages)) {
        while ($language = mysqli_fetch_array($languages)) {
            if (!empty($array_translations)) {
                foreach ($array_translations as $key => $value) {
                    $trans = mysqli_query($connection, "SELECT * FROM language_translations WHERE label ='" . $key . "' AND lang_id = " . $language['id']);
                    if (mysqli_num_rows($trans) < 1) {
                        mysqli_query($connection, "INSERT INTO `language_translations` (`lang_id`, `label`, `translation`) VALUES (" . $language['id'] . ", '" . $key . "', '" . $value . "');");
                    }
                }
            }
        }
    }
}

function generate_variation_label_array($connection, $common_id)
{
    $array_names = array();
    $sql = "SELECT * FROM product_variations WHERE common_id='" . $common_id . "' ORDER BY id";
    $result = mysqli_query($connection, $sql);
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $item = array(
                'lang_id' => intval(@$row["lang_id"]),
                'label' => @$row["label"]
            );
            array_push($array_names, $item);
        }
    }
    return @serialize($array_names);
}

function generate_variation_option_names_array($connection, $option_common_id)
{
    $array_names = array();
    $sql = "SELECT * FROM product_variations_options WHERE option_common_id='" . $option_common_id . "' ORDER BY id";
    $result = mysqli_query($connection, $sql);
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $item = array(
                'lang_id' => intval(@$row["lang_id"]),
                'option_name' => @$row["option_text"]
            );
            array_push($array_names, $item);
        }
    }
    return @serialize($array_names);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modesy - Update Wizard</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700" rel="stylesheet">
    <!-- Font-awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #444 !important;
            font-size: 14px;

            background: #007991; /* fallback for old browsers */
            background: -webkit-linear-gradient(to left, #007991, #6fe7c2); /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to left, #007991, #6fe7c2); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */

        }

        .logo-cnt {
            text-align: center;
            color: #fff;
            padding: 60px 0 60px 0;
        }

        .logo-cnt .logo {
            font-size: 42px;
            line-height: 42px;
        }

        .logo-cnt p {
            font-size: 22px;
        }

        .install-box {
            width: 100%;
            padding: 30px;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            background-color: #fff;
            border-radius: 4px;
            display: block;
            float: left;
            margin-bottom: 100px;
        }

        .form-input {
            box-shadow: none !important;
            border: 1px solid #ddd;
            height: 44px;
            line-height: 44px;
            padding: 0 20px;
        }

        .form-input:focus {
            border-color: #239CA1 !important;
        }

        .btn-custom {
            background-color: #239CA1 !important;
            border-color: #239CA1 !important;
            border: 0 none;
            border-radius: 4px;
            box-shadow: none;
            color: #fff !important;
            font-size: 16px;
            font-weight: 300;
            height: 40px;
            line-height: 40px;
            margin: 0;
            min-width: 105px;
            padding: 0 20px;
            text-shadow: none;
            vertical-align: middle;
        }

        .btn-custom:hover, .btn-custom:active, .btn-custom:focus {
            background-color: #239CA1;
            border-color: #239CA1;
            opacity: .8;
        }

        .tab-content {
            width: 100%;
            float: left;
            display: block;
        }

        .tab-footer {
            width: 100%;
            float: left;
            display: block;
        }

        .buttons {
            display: block;
            float: left;
            width: 100%;
            margin-top: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            margin-top: 0;
            text-align: center;
        }

        .sub-title {
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 30px;
            margin-top: 0;
            text-align: center;
        }

        .alert {
            text-align: center;
        }

        .alert strong {
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-sm-12 col-md-offset-2">
            <div class="row">
                <div class="col-sm-12 logo-cnt">
                    <h1>Modesy</h1>
                    <p>Welcome to the Update Wizard</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="install-box">
                        <h2 class="title">Update from v1.5.x to v1.7.1</h2>
                        <br><br>
                        <div class="messages">
                            <?php if (!empty($error)) { ?>
                                <div class="alert alert-danger">
                                    <strong><?php echo $error; ?></strong>
                                </div>
                            <?php } ?>
                            <?php if (!empty($success)) { ?>
                                <div class="alert alert-success">
                                    <strong><?php echo $success; ?></strong>
                                    <style>.alert-info {
                                            display: none;
                                        }</style>
                                </div>
                                <?php @unlink(__FILE__); ?>
                            <?php } ?>
                        </div>
                        <?php
                        if (empty($success)):
                            if (empty($license_array) || empty($license_array["purchase_code"]) || empty($license_array["license_code"])): ?>
                                <div class="alert alert-info" role="alert">
                                    You can get your license code from our support system: <a href="https://codingest.net/" target="_blank"><strong>https://codingest.net</strong></a>
                                </div>
                            <?php endif;
                        endif; ?>
                        <div class="step-contents">
                            <div class="tab-1">
                                <?php if (empty($success)): ?>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                        <div class="tab-content">
                                            <div class="tab_1">
                                                <?php if (empty($license_array) || empty($license_array["purchase_code"]) || empty($license_array["license_code"])): ?>
                                                    <div class="form-group">
                                                        <label for="email">License Code</label>
                                                        <textarea name="license_code" class="form-control form-input" style="resize: vertical; min-height: 80px; height: 80px; line-height: 24px;padding: 10px;" placeholder="Enter License Code" required><?php echo $license_code; ?></textarea>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="tab-footer text-center">
                                            <button type="submit" name="btn_submit" class="btn-custom">Update My Database</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
