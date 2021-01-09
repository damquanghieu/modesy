<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<footer id="footer">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="footer-top">
                    <div class="row">
                        <div class="col-12 col-md-3 footer-widget">
                            <div class="row-custom">
                                <div class="footer-logo">
                                    <a href="<?php echo lang_base_url(); ?>"><img src="<?php echo get_logo($this->general_settings); ?>" alt="logo"></a>
                                </div>
                            </div>
                            <div class="row-custom">
                                <div class="footer-about">
                                    <?php echo html_escape($this->settings->about_footer); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 footer-widget">
                            <div class="nav-footer">
                                <div class="row-custom">
                                    <h4 class="footer-title"><?php echo trans("footer_quick_links"); ?></h4>
                                </div>
                                <div class="row-custom">
                                    <ul>
                                        <li><a href="<?php echo lang_base_url(); ?>"><?php echo trans("home"); ?></a></li>
                                        <?php if (!empty($this->menu_links)):
                                            foreach ($this->menu_links as $menu_link):
                                                if ($menu_link->location == 'quick_links'):
                                                    $item_link = generate_menu_item_url($menu_link);
                                                    if (!empty($menu_link->page_default_name)):
                                                        $item_link = generate_url($menu_link->page_default_name);
                                                    endif; ?>
                                                    <li><a href="<?= $item_link; ?>"><?php echo html_escape($menu_link->title); ?></a></li>
                                                <?php endif;
                                            endforeach;
                                        endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 footer-widget">
                            <div class="nav-footer">
                                <div class="row-custom">
                                    <h4 class="footer-title"><?php echo trans("footer_information"); ?></h4>
                                </div>
                                <div class="row-custom">
                                    <ul>
                                        <?php if (!empty($this->menu_links)):
                                            foreach ($this->menu_links as $menu_link):
                                                if ($menu_link->location == 'information'):
                                                    $item_link = generate_menu_item_url($menu_link);
                                                    if (!empty($menu_link->page_default_name)):
                                                        $item_link = generate_url($menu_link->page_default_name);
                                                    endif; ?>
                                                    <li><a href="<?= $item_link; ?>"><?php echo html_escape($menu_link->title); ?></a></li>
                                                <?php endif;
                                            endforeach;
                                        endif; ?>

                                        <?php if (!empty($this->menu_links)):
                                            foreach ($this->menu_links as $menu_link):
                                                if ($menu_link->location == 'information'):?>
                                                <?php endif;
                                            endforeach;
                                        endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 footer-widget">
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="footer-title"><?php echo trans("follow_us"); ?></h4>
                                    <div class="footer-social-links">
                                        <!--include social links-->
                                        <?php $this->load->view('partials/_social_links', ['show_rss' => true]); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="newsletter">
                                        <h4 class="footer-title"><?php echo trans("newsletter"); ?></h4>
                                        <?php echo form_open('add-to-subscribers-post', ['id' => 'form_validate_newsletter']); ?>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="newsletter-inner">
                                                    <div class="d-table-cell">
                                                        <input type="email" class="form-control" name="email" placeholder="<?php echo trans("enter_email"); ?>" required>
                                                    </div>
                                                    <div class="d-table-cell align-middle">
                                                        <button class="btn btn-default"><?php echo trans("subscribe"); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo form_close(); ?>

                                        <div class="row">
                                            <div class="col-12">
                                                <div id="newsletter" class="m-t-5">
                                                    <?php if ($this->session->flashdata('news_error')):
                                                        echo '<span class="text-danger">' . $this->session->flashdata('news_error') . '</span>';
                                                    endif;
                                                    if ($this->session->flashdata('news_success')):
                                                        echo '<span class="text-success">' . $this->session->flashdata('news_success') . '</span>';
                                                    endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="footer-bottom">
                <div class="container">
                    <div class="copyright">
                        <?php echo html_escape($this->settings->copyright); ?>
                    </div>
                    <div class="footer-payment-icons">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo base_url(); ?>assets/img/payment/visa.svg" alt="visa" class="lazyload">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo base_url(); ?>assets/img/payment/mastercard.svg" alt="mastercard" class="lazyload">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo base_url(); ?>assets/img/payment/maestro.svg" alt="maestro" class="lazyload">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo base_url(); ?>assets/img/payment/amex.svg" alt="amex" class="lazyload">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo base_url(); ?>assets/img/payment/discover.svg" alt="discover" class="lazyload">
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<?php if (!isset($_COOKIE["modesy_cookies_warning"]) && $this->settings->cookies_warning): ?>
    <div class="cookies-warning">
        <div class="text"><?php echo $this->settings->cookies_warning_text; ?></div>
        <a href="javascript:void(0)" onclick="hide_cookies_warning();" class="icon-cl"> <i class="icon-close"></i></a>
    </div>
<?php endif; ?>
<!-- Scroll Up Link -->
<a href="javascript:void(0)" class="scrollup"><i class="icon-arrow-up"></i></a>

<?php if (!empty($this->session->userdata('mds_send_email_data'))): ?>
    <script>
        $(document).ready(function () {
            var data = JSON.parse(<?php echo json_encode($this->session->userdata("mds_send_email_data"));?>);
            if (data) {
                data[csfr_token_name] = $.cookie(csfr_cookie_name);
                data["sys_lang_id"] = sys_lang_id;
                $.ajax({
                    type: "POST",
                    url: base_url + "send-email-post",
                    data: data,
                    success: function (response) {
                    }
                });
            }
        });
    </script>
<?php endif;
$this->session->unset_userdata('mds_send_email_data'); ?>

<?php if (check_cron_time()): ?>
    <script>$.ajax({type: "POST", url: "<?php echo base_url(); ?>run-internal-cron"});</script>
<?php endif; ?>

<script src="<?php echo base_url(); ?>assets/vendor/bootstrap/js/popper.min.js"></script>
<script src="<?php echo base_url(); ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/plugins-1.7.js"></script>
<script src="<?php echo base_url(); ?>assets/js/script-1.7.min.js"></script>
<script>
    $('<input>').attr({type: 'hidden', name: 'sys_lang_id', value: '<?php echo $this->selected_lang->id; ?>'}).appendTo('form[method="post"]');
</script>
<script>
    var sys_lang_id = "<?= $this->selected_lang->id; ?>";var base_url = "<?= base_url(); ?>";var lang_base_url = "<?= lang_base_url(); ?>";var thousands_separator = "<?= $this->thousands_separator; ?>";var fb_app_id = "<?= $this->general_settings->facebook_app_id; ?>";var csfr_token_name = "<?= $this->security->get_csrf_token_name(); ?>";var csfr_cookie_name = "<?= $this->config->item('csrf_cookie_name'); ?>";var is_recaptcha_enabled = false;var txt_all = "<?= trans("all"); ?>";var sweetalert_ok = "<?= trans("ok"); ?>";var sweetalert_cancel = "<?= trans("cancel"); ?>";var msg_accept_terms = "<?= trans("msg_accept_terms"); ?>";var slider_fade_effect = "<?= ($this->general_settings->slider_effect == "fade") ? 1 : 0; ?>";
    <?php if ($recaptcha_status == true): ?>is_recaptcha_enabled = true;<?php endif; ?><?php if (!empty($index_categories)):foreach ($index_categories as $category):?>
if ($('#category_products_slider_<?= $category->id; ?>').length != 0) {
        $('#category_products_slider_<?= $category->id; ?>').slick({autoplay: false, autoplaySpeed: 4900, infinite: true, speed: 200, swipeToSlide: true, rtl: rtl, cssEase: 'linear', lazyLoad: 'progressive', prevArrow: $('#category-products-slider-nav-<?= $category->id; ?> .prev'), nextArrow: $('#category-products-slider-nav-<?= $category->id; ?> .next'), slidesToShow: 5, slidesToScroll: 1, responsive: [{breakpoint: 992, settings: {slidesToShow: 4, slidesToScroll: 1}}, {breakpoint: 768, settings: {slidesToShow: 3, slidesToScroll: 1}}, {breakpoint: 576, settings: {slidesToShow: 2, slidesToScroll: 1}}]});}
    <?php endforeach;
    endif; ?>
    <?php if ($this->general_settings->pwa_status == 1): ?>
if ('serviceWorker' in navigator) {window.addEventListener('load', function () {navigator.serviceWorker.register('<?= base_url();?>pwa-sw.js').then(function (registration) {}, function (err) {console.log('ServiceWorker registration failed: ', err);}).catch(function (err) {console.log(err);});});} else {console.log('service worker is not supported');}
    <?php endif; ?>
</script>
<?php echo $this->general_settings->google_analytics; ?>
<?php echo $this->general_settings->custom_javascript_codes; ?>
</body>
</html>
