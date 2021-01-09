<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
</section>
<!-- /.content -->
</div>

</div>
<!-- ./wrapper -->
<!-- jQuery UI 1.11.4 -->
<script src="<?php echo base_url(); ?>assets/admin/js/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button);
    var thousands_separator = "<?php echo $this->thousands_separator; ?>";
</script>
<!-- Bootstrap 3.3.7 -->
<script src="<?php echo base_url(); ?>assets/admin/vendor/bootstrap/js/bootstrap.min.js"></script>
<!-- DataTables js -->
<script src="<?php echo base_url(); ?>assets/admin/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/admin/vendor/datatables/dataTables.bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo base_url(); ?>assets/admin/js/adminlte.min.js"></script>
<!-- iCheck js -->
<script src="<?php echo base_url(); ?>assets/admin/vendor/icheck/icheck.min.js"></script>
<!-- Pace -->
<script src="<?php echo base_url(); ?>assets/admin/vendor/pace/pace.min.js"></script>
<!-- Tagsinput js -->
<script src="<?php echo base_url(); ?>assets/admin/vendor/tagsinput/jquery.tagsinput.min.js"></script>
<!-- Plugins JS-->
<script src="<?php echo base_url(); ?>assets/admin/js/plugins.js"></script>

<script src="<?php echo base_url(); ?>assets/admin/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
<!-- Custom js -->
<script src="<?php echo base_url(); ?>assets/admin/js/main-1.7.js"></script>
<script src="<?php echo base_url(); ?>assets/admin/js/dashboard-1.7.js"></script>

<!--tinyMCE-->
<script src="<?php echo base_url(); ?>assets/vendor/tinymce/jquery.tinymce.min.js"></script>
<script src="<?php echo base_url(); ?>assets/vendor/tinymce/tinymce.min.js"></script>

<script src="<?php echo base_url(); ?>assets/vendor/file-manager/file-manager.js"></script>

<script>
    function init_tinymce(selector, min_height) {
        var menu_bar = 'file edit view insert format tools table help';
        if (selector == '.tinyMCEsmall') {
            menu_bar = false;
        }
        tinymce.init({
            selector: selector,
            min_height: min_height,
            valid_elements: '*[*]',
            relative_urls: false,
            remove_script_host: false,
            directionality: directionality,
            language: '<?php echo $this->selected_lang->text_editor_lang; ?>',
            menubar: menu_bar,
            plugins: [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code codesample fullscreen",
                "insertdatetime media table paste imagetools"
            ],
            toolbar: 'fullscreen code preview | bold italic underline strikethrough | fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | numlist bullist | forecolor backcolor removeformat | table image media link | outdent indent superscript subscript',
            content_css: ['<?php echo base_url(); ?>assets/vendor/tinymce/editor_content.css'],
        });
        tinymce.DOM.loadCSS('<?php echo base_url(); ?>assets/vendor/tinymce/editor_ui.css');
    }
    if ($('.tinyMCE').length > 0) {
        init_tinymce('.tinyMCE', 500);
    }
    if ($('.tinyMCEsmall').length > 0) {
        init_tinymce('.tinyMCEsmall', 300);
    }
</script>
<script>
    var txt_processing = "<?= trans("processing"); ?>";
    var sweetalert_ok = "<?= trans("ok"); ?>";
    var sweetalert_cancel = "<?= trans("cancel"); ?>";
</script>
</body>
</html>
