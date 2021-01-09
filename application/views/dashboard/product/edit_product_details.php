<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Datepicker -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/datepicker/css/bootstrap-datepicker.standalone.css">
<script src="<?php echo base_url(); ?>assets/vendor/datepicker/js/bootstrap-datepicker.min.js"></script>

<!-- Plyr JS-->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/vendor/plyr/plyr.css">
<script src="<?php echo base_url(); ?>assets/vendor/plyr/plyr.min.js"></script>
<script src="<?php echo base_url(); ?>assets/vendor/plyr/plyr.polyfilled.min.js"></script>

<?php $back_url = generate_dash_url("edit_product") . "/" . $product->id; ?>
<script type="text/javascript">
    history.pushState(null, null, '<?php echo $_SERVER["REQUEST_URI"]; ?>');
    window.addEventListener('popstate', function (event) {
        window.location.assign('<?php echo $back_url; ?>');
    });
</script>

<?php if ($product->is_draft == 1): ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="wizard-product">
                <h1 class="product-form-title"><?= trans("add_product"); ?></h1>
                <div class="row">
                    <div class="col-md-12 wizard-add-product">
                        <ul class="wizard-progress">
                            <li class="active" id="step_general"><strong><?= trans("general_information"); ?></strong></li>
                            <li class="active" id="step_dedails"><strong><?= trans("details"); ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-sm-12">
        <div class="box box-add-product">
            <div class="box-body">
                <?php if ($product->is_draft != 1): ?>
                    <h1 class="product-form-title"><?= trans("edit_product"); ?></h1>
                <?php endif; ?>
                <div class="alert-message-lg aler-product-form">
                    <?php $this->load->view('dashboard/includes/_messages'); ?>
                </div>
                <?php if ($product->product_type == 'digital'): ?>
                    <div class="row-custom">
                        <?php $this->load->view("dashboard/product/_digital_files_upload_box"); ?>
                    </div>
                <?php endif; ?>

                <?php echo form_open('edit-product-details-post', ['id' => 'form_product_details', 'class' => 'validate_price', 'class' => 'validate_terms', 'onkeypress' => "return event.keyCode != 13;"]); ?>
                <input type="hidden" name="id" value="<?php echo $product->id; ?>">

                <?php if ($product->product_type == 'digital'): ?>
                    <?php $this->load->view("dashboard/product/license/_license_keys", ['product' => $product, 'license_keys' => $license_keys]); ?>

                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title">
                                <?php echo trans('files_included'); ?><br>
                                <small><?php echo trans("files_included_ext"); ?></small>
                            </h4>
                        </div>
                        <div class="form-box-body">
                            <input type="text" name="files_included" class="form-control form-input" value="<?php echo html_escape($product->files_included); ?>" placeholder="<?php echo trans("files_included"); ?>" required>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($custom_fields)): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title"><?php echo trans('details'); ?></h4>
                        </div>
                        <div class="form-box-body">
                            <div class="form-group">
                                <div class="row" id="custom_fields_container">
                                    <?php $this->load->view("dashboard/product/_custom_fields", ["custom_fields" => $custom_fields, "product" => $product]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($product->product_type != 'digital' && $product->listing_type != 'ordinary_listing'): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title"><?php echo trans('stock'); ?></h4>
                        </div>
                        <div class="form-box-body">
                            <div class="form-group">
                                <input type="number" name="stock" class="form-control form-input max-perc-50" min="0" max="999999999" value="<?php echo $product->stock; ?>" placeholder="<?php echo trans("stock"); ?>" required>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="stock" value="<?= $product->stock; ?>">
                <?php endif; ?>

                <?php $this->load->view("dashboard/product/_edit_price"); ?>

                <?php if (($product->product_type == 'physical' && $this->form_settings->physical_demo_url == 1) || ($product->product_type == 'digital' && $this->form_settings->digital_demo_url == 1)): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title">
                                <?php echo trans('demo_url'); ?><br>
                                <small><?php echo trans("demo_url_exp"); ?></small>
                            </h4>
                        </div>
                        <div class="form-box-body">
                            <input type="text" name="demo_url" class="form-control form-input" value="<?= html_escape($product->demo_url); ?>" placeholder="<?= trans("demo_url"); ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <?php $show_video_prev = false;
                $show_audio_prev = false;
                if (($product->product_type == 'physical' && $this->form_settings->physical_video_preview == 1) || ($product->product_type == 'digital' && $this->form_settings->digital_video_preview == 1)):
                    $show_video_prev = true;
                endif;
                if (($product->product_type == 'physical' && $this->form_settings->physical_audio_preview == 1) || ($product->product_type == 'digital' && $this->form_settings->digital_audio_preview == 1)):
                    $show_audio_prev = true;
                endif; ?>
                <?php if ($show_video_prev || $show_audio_prev): ?>
                    <div class="form-box form-box-preview">
                        <div class="form-box-head">
                            <h4 class="title"><?php echo trans('preview'); ?></h4>
                        </div>
                        <div class="form-box-body">
                            <div class="row">
                                <?php if ($show_video_prev): ?>
                                    <div class="col-sm-12 col-sm-6 m-b-30">
                                        <label><?php echo trans("video_preview"); ?></label>
                                        <small>(<?php echo trans("video_preview_exp"); ?>)</small>
                                        <?php $this->load->view("dashboard/product/_video_upload_box"); ?>
                                    </div>
                                <?php endif;
                                if ($show_audio_prev):?>
                                    <div class="col-sm-12 col-sm-6 m-b-30">
                                        <label><?php echo trans("audio_preview"); ?></label>
                                        <small>(<?php echo trans("audio_preview_exp"); ?>)</small>
                                        <?php $audio = $this->file_model->get_product_audio($product->id);
                                        $this->load->view("dashboard/product/_audio_upload_box", ['audio' => $audio]); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($product->listing_type == 'ordinary_listing' && $this->form_settings->external_link == 1): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title">
                                <?php echo trans('external_link'); ?><br>
                                <small><?php echo trans("external_link_exp"); ?></small>
                            </h4>
                        </div>
                        <div class="form-box-body">
                            <input type="text" name="external_link" class="form-control form-input" value="<?php echo html_escape($product->external_link); ?>" placeholder="<?php echo trans("external_link"); ?>">
                        </div>
                    </div>
                <?php endif; ?>


                <?php if ($this->form_settings->variations == 1 && $product->listing_type != 'ordinary_listing'): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title">
                                <?php echo trans('variations'); ?>
                                <small><?php echo trans("variations_exp"); ?></small>
                            </h4>
                        </div>
                        <div class="form-box-body">
                            <div class="row">
                                <div id="response_product_variations" class="col-sm-12">
                                    <?php $this->load->view("dashboard/product/variation/_response_variations", ["product_variations" => $product_variations]); ?>
                                </div>
                                <div class="col-sm-12">
                                    <button type="button" class="btn btn-md btn-info btn-variation" data-toggle="modal" data-target="#addVariationModal">
                                        <?php echo trans("add_variation"); ?>
                                    </button>
                                    <button type="button" class="btn btn-md btn-secondary btn-variation" data-toggle="modal" data-target="#variationModalSelect">
                                        <?php echo trans("select_existing_variation"); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($this->form_settings->shipping == 1 && $product->product_type == 'physical'): ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title"><?php echo trans('shipping'); ?></h4>
                        </div>
                        <div class="form-box-body">
                            <div class="row">
                                <?php
                                $show_shipping_cost_input = $this->settings_model->is_shipping_option_require_cost($product->shipping_cost_type);
                                $shipping_options = get_grouped_shipping_options();
                                if (!empty($shipping_options)): ?>
                                    <div class="col-sm-12 col-md-6 m-b-sm-15">
                                        <label><?php echo trans('shipping_cost'); ?></label>
                                        <select id="select_shipping_cost" name="shipping_cost_type" class="form-control custom-select" <?php echo ($this->form_settings->shipping_required == 1) ? 'required' : ''; ?>>
                                            <option value=""><?php echo trans("select_option"); ?></option>
                                            <?php foreach ($shipping_options as $option):
                                                $shipping_option = get_shipping_option_by_lang($option->common_id, $this->selected_lang->id);
                                                if ($shipping_option->is_visible == 1): ?>
                                                    <option value="<?php echo $shipping_option->option_key; ?>" data-shipping-cost="<?php echo $shipping_option->shipping_cost; ?>" <?php echo ($product->shipping_cost_type == $shipping_option->option_key) ? 'selected' : ''; ?>><?php echo $shipping_option->option_label; ?></option>
                                                <?php endif;
                                                if ($product->shipping_cost_type == $shipping_option->option_key) {
                                                    $show_shipping_cost_input = $shipping_option->shipping_cost;
                                                }
                                            endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="col-sm-12 col-md-6">
                                    <label><?php echo trans('shipping_time'); ?></label>
                                    <select name="shipping_time" class="form-control custom-select" <?php echo ($this->form_settings->shipping_required == 1) ? 'required' : ''; ?>>
                                        <option value=""><?php echo trans("select_option"); ?></option>
                                        <option value="1_business_day" <?php echo ($product->shipping_time == "1_business_day") ? 'selected' : ''; ?>><?php echo trans("1_business_day"); ?></option>
                                        <option value="2_3_business_days" <?php echo ($product->shipping_time == "2_3_business_days") ? 'selected' : ''; ?>><?php echo trans("2_3_business_days"); ?></option>
                                        <option value="4_7_business_days" <?php echo ($product->shipping_time == "4_7_business_days") ? 'selected' : ''; ?>><?php echo trans("4_7_business_days"); ?></option>
                                        <option value="8_15_business_days" <?php echo ($product->shipping_time == "8_15_business_days") ? 'selected' : ''; ?>><?php echo trans("8_15_business_days"); ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-6 m-t-15 shipping-cost-container" style="<?= $show_shipping_cost_input == 1 ? 'display:block;' : 'display:none;'; ?>">
                                    <label><?php echo trans('shipping_cost'); ?></label>
                                    <div class="input-group">
                                        <?php if ($this->payment_settings->default_currency != "all"): ?>
                                            <span class="input-group-addon"><?php echo get_currency($this->payment_settings->default_currency); ?></span>
                                        <?php endif; ?>
                                        <input type="text" name="shipping_cost" aria-describedby="basic-addon3" class="form-control form-input price-input" value="<?php echo $product->shipping_cost != 0 ? get_price($product->shipping_cost, 'input') : ''; ?>" placeholder="<?php echo $this->input_initial_price; ?>" onpaste="return false;" maxlength="32" <?= $show_shipping_cost_input == 1 ? 'required' : ''; ?>>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-6 m-t-15 shipping-cost-container" style="<?= $show_shipping_cost_input == 1 ? 'display:block;' : 'display:none;'; ?>">
                                    <label><?php echo trans('shipping_cost_per_additional_product'); ?></label>
                                    <div class="input-group">
                                        <?php if ($this->payment_settings->default_currency != "all"): ?>
                                            <span class="input-group-addon"><?php echo get_currency($this->payment_settings->default_currency); ?></span>
                                        <?php endif; ?>
                                        <input type="text" name="shipping_cost_additional" aria-describedby="basic-addon3" class="form-control form-input price-input" value="<?php echo get_price($product->shipping_cost_additional, 'input'); ?>" placeholder="<?php echo $this->input_initial_price; ?>" onpaste="return false;" maxlength="32" <?= $show_shipping_cost_input == 1 ? 'required' : ''; ?>>
                                    </div>
                                    <small><?php echo trans("shipping_cost_per_additional_product_exp"); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($this->form_settings->product_location == 1 && $product->product_type == 'physical'):
                    if ($product->country_id == 0) {
                        $country_id = $this->auth_user->country_id;
                        $state_id = $this->auth_user->state_id;
                        $city_id = $this->auth_user->city_id;
                        $address = $this->auth_user->address;
                        $zip_code = $this->auth_user->zip_code;
                    } else {
                        $country_id = $product->country_id;
                        $state_id = $product->state_id;
                        $city_id = $product->city_id;
                        $address = $product->address;
                        $zip_code = $product->zip_code;
                    }
                    ?>
                    <div class="form-box">
                        <div class="form-box-head">
                            <h4 class="title"><?php echo trans('location'); ?></h4>
                        </div>
                        <div class="form-box-body">
                            <div class="form-group m-0">
                                <?php $this->load->view("partials/_location", ['countries' => $this->countries, 'country_id' => $country_id, 'state_id' => $state_id, 'city_id' => $city_id, 'map' => true]); ?>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-sm-12 col-sm-6 m-b-sm-15">
                                        <input type="text" name="address" id="address_input" class="form-control form-input" value="<?php echo html_escape($address); ?>" placeholder="<?php echo trans("address") ?>">
                                    </div>

                                    <div class="col-sm-12 col-sm-3">
                                        <input type="text" name="zip_code" id="zip_code_input" class="form-control form-input" value="<?php echo html_escape($zip_code); ?>" placeholder="<?php echo trans("zip_code") ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div id="map-result">
                                    <!--load map-->
                                    <?php if ($product->country_id == 0) {
                                        $this->load->view("product/_load_map", ["map_address" => get_location($this->auth_user)]);
                                    } else {
                                        $this->load->view("product/_load_map", ["map_address" => get_location($product)]);
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-sm-12 text-left m-t-15 m-b-15">
        <div class="form-group">
            <div class="custom-control custom-checkbox custom-control-validate-input">
                <?php if ($product->is_draft == 1): ?>
                    <input type="checkbox" class="custom-control-input" name="terms_conditions" id="terms_conditions" value="1" required>
                <?php else: ?>
                    <input type="checkbox" class="custom-control-input" name="terms_conditions" id="terms_conditions" value="1" checked>
                <?php endif; ?>
                <label for="terms_conditions" class="custom-control-label"><?php echo trans("terms_conditions_exp"); ?>&nbsp;
                    <?php $page_terms = get_page_by_default_name("terms_conditions", $this->selected_lang->id);
                    if (!empty($page_terms)): ?>
                        <a href="<?= generate_url($page_terms->page_default_name); ?>" class="link-terms" target="_blank"><strong><?= html_escape($page_terms->title); ?></strong></a>
                    <?php endif; ?>
                </label>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group m-t-15">
            <a href="<?php echo generate_dash_url("edit_product") . "/" . $product->id; ?>" class="btn btn-lg btn-dark pull-left"><?php echo trans("back"); ?></a>
            <?php if ($product->is_draft == 1): ?>
                <button type="submit" name="submit" value="submit" class="btn btn-lg btn-success btn-form-product-details pull-right"><?php echo trans("submit"); ?></button>
                <button type="submit" name="submit" value="save_as_draft" class="btn btn-lg btn-secondary btn-form-product-details m-r-10 pull-right"><?php echo trans("save_as_draft"); ?></button>
            <?php else: ?>
                <button type="submit" name="submit" value="save_changes" class="btn btn-lg btn-success btn-form-product-details pull-right"><?php echo trans("save_changes"); ?></button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php echo form_close(); ?>

<?php $this->load->view("dashboard/product/variation/_form_variations"); ?>

<script>
    const player = new Plyr('#player');
    $(document).ajaxStop(function () {
        const player = new Plyr('#player');
    });
    const audio_player = new Plyr('#audio_player');
    $(document).ajaxStop(function () {
        const player = new Plyr('#audio_player');
    });
    $(window).on("load", function () {
        $(".li-dm-media-preview").css("visibility", "visible");
    });
</script>

<script>
    $.fn.datepicker.dates['en'] = {
        days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        daysMin: ["<?= substr(trans("monday"), 0, 3); ?>",
            "<?= substr(trans("tuesday"), 0, 3); ?>",
            "<?= substr(trans("wednesday"), 0, 3); ?>",
            "<?= substr(trans("thursday"), 0, 3); ?>",
            "<?= substr(trans("friday"), 0, 3); ?>",
            "<?= substr(trans("saturday"), 0, 3); ?>",
            "<?= substr(trans("sunday"), 0, 3); ?>"],
        months: ['<?php echo trans("january"); ?>',
            "<?= trans("february"); ?>",
            "<?= trans("march"); ?>",
            "<?= trans("april"); ?>",
            "<?= trans("may"); ?>",
            "<?= trans("june"); ?>",
            "<?= trans("july"); ?>",
            "<?= trans("august"); ?>",
            "<?= trans("september"); ?>",
            "<?= trans("october"); ?>",
            "<?= trans("november"); ?>",
            "<?= trans("december"); ?>"],
        monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        today: "Today",
        clear: "Clear",
        format: "mm/dd/yyyy",
        titleFormat: "MM yyyy",
        weekStart: 0
    };
    $('.datepicker').datepicker({
        language: 'en'
    });

    //validate checkbox
    $(document).on("click", ".btn-form-product-details ", function () {
        $('.checkbox-options-container').each(function () {
            var field_id = $(this).attr('data-custom-field-id');
            var element = "#checkbox_options_container_" + field_id + " .required-checkbox";
            if (!$(element).is(':checked')) {
                $(element).prop('required', true);
            } else {
                $(element).prop('required', false);
            }
        });
    });
</script>
