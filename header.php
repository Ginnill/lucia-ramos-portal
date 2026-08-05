<?php
/**
 * Theme header template.
 *
 * @package TailPress
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@600;900&display=swap" rel="stylesheet">
    <?php $force_light_theme = is_page('entrar'); ?>
    <script>
        (function () {
            try {
                var key = 'lucia-theme';
                var forcedTheme = <?php echo wp_json_encode($force_light_theme ? 'light' : ''); ?>;
                var saved = localStorage.getItem(key);
                var theme = forcedTheme || ((saved === 'dark' || saved === 'light')
                    ? saved
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
                if (forcedTheme) {
                    document.documentElement.dataset.luciaForcedTheme = forcedTheme;
                }
                document.documentElement.classList.toggle('dark', theme === 'dark');
            } catch (e) {}
        })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-light text-dark antialiased'); ?>>
<?php do_action('tailpress_site_before'); ?>
<?php
$is_portal_page = function_exists('tailpress_is_portal_page') && tailpress_is_portal_page();
$is_storefront_page = function_exists('tailpress_is_consultora_storefront_request') && tailpress_is_consultora_storefront_request();
$hide_global_header = is_page('entrar') || $is_portal_page || $is_storefront_page;
?>

<div id="page" class="min-h-screen flex flex-col">
    <?php do_action('tailpress_header'); ?>

    <?php if (!$hide_global_header): ?>
        <header class="container mx-auto py-6">
            <div class="md:flex md:justify-between md:items-center">
                <div class="flex justify-between items-center">
                    <div>
                        <?php if (has_custom_logo()): ?>
                            <?php the_custom_logo(); ?>
                        <?php else: ?>
                            <div class="flex items-center gap-2">
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="!no-underline lowercase font-medium text-lg">
                                    <?php bloginfo('name'); ?>
                                </a>
                                <?php if ($description = get_bloginfo('description')): ?>
                                    <span class="text-sm font-light text-dark/80">|</span>
                                    <span class="text-sm font-light text-dark/80"><?php echo esc_html($description); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (has_nav_menu('primary')): ?>
                        <div class="md:hidden">
                            <button type="button" aria-label="Toggle navigation" id="primary-menu-toggle">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="primary-navigation" class="hidden md:flex md:bg-transparent gap-6 items-center border border-light md:border-none rounded-xl p-4 md:p-0">
                    <nav>
                        <?php if (current_user_can('administrator') && !has_nav_menu('primary')): ?>
                            <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" class="text-sm text-zinc-600"><?php esc_html_e('Edit Menus', 'tailpress'); ?></a>
                        <?php else: ?>
                            <?php
                            wp_nav_menu([
                                'container_id'    => 'primary-menu',
                                'container_class' => '',
                                'menu_class'      => 'md:flex md:-mx-4 [&_a]:!no-underline',
                                'theme_location'  => 'primary',
                                'li_class'        => 'md:mx-4',
                                'fallback_cb'     => false,
                            ]);
                            ?>
                        <?php endif; ?>
                    </nav>

                    <div class="inline-block mt-4 md:mt-0"><?php get_search_form(); ?></div>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <div id="content" class="site-content grow">
        <?php do_action('tailpress_content_start'); ?>
        <main>
