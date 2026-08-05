<?php
/**
 * Theme footer template.
 *
 * @package TailPress
 */
?>
        </main>

        <?php do_action('tailpress_content_end'); ?>
    </div>

    <?php do_action('tailpress_content_after'); ?>
    <?php
    $is_portal_page = function_exists('tailpress_is_portal_page') && tailpress_is_portal_page();
    $is_storefront_page = function_exists('tailpress_is_consultora_storefront_request') && tailpress_is_consultora_storefront_request();
    $hide_global_footer = is_page('entrar') || $is_portal_page || $is_storefront_page;
    ?>

    <?php if (!$hide_global_footer): ?>
        <footer id="colophon" class="bg-light/50 mt-12" role="contentinfo">
            <div class="container mx-auto py-12">
                <?php do_action('tailpress_footer'); ?>
                <div class="text-sm text-slate">
                    &copy; <?php echo esc_html(date_i18n('Y')); ?> - <?php bloginfo('name'); ?>
                </div>
            </div>
        </footer>
    <?php endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
