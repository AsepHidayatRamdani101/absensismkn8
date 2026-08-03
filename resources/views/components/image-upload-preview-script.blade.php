<script>
    (function() {
        function bindImagePreview(selector) {
            const inputSelector = selector || '.js-image-preview-input';

            $(document).on('change', inputSelector, function() {
                const $input = $(this);
                const wrapSelector = $input.data('preview-wrap');
                const imageSelector = $input.data('preview-image');

                if (!wrapSelector || !imageSelector) {
                    return;
                }

                const $wrap = $(wrapSelector);
                const $image = $(imageSelector);
                const file = this.files && this.files[0] ? this.files[0] : null;

                if (!file) {
                    $wrap.addClass('d-none');
                    $image.attr('src', '');
                    return;
                }

                const objectUrl = URL.createObjectURL(file);
                $image.attr('src', objectUrl);
                $wrap.removeClass('d-none');
            });
        }

        window.bindImagePreview = bindImagePreview;
        bindImagePreview();
    })();
</script>
