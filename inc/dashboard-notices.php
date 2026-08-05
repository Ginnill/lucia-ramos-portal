<?php
/**
 * Dashboard notices managed in WordPress admin.
 *
 * @package tailpress
 */

function tailpress_dashboard_notice_post_type(): string
{
    return 'lucia_aviso';
}

function tailpress_dashboard_notice_flash_key(): string
{
    return 'tailpress_dashboard_notice_flash_' . get_current_user_id();
}

function tailpress_dashboard_notice_flash(string $message, string $type = 'error'): void
{
    if (get_current_user_id() <= 0 || $message === '') {
        return;
    }

    set_transient(tailpress_dashboard_notice_flash_key(), [
        'message' => $message,
        'type' => $type,
    ], MINUTE_IN_SECONDS);
}

function tailpress_dashboard_notice_render_flash(): void
{
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== tailpress_dashboard_notice_post_type()) {
        return;
    }

    $notice = get_transient(tailpress_dashboard_notice_flash_key());

    if (!is_array($notice) || empty($notice['message'])) {
        return;
    }

    delete_transient(tailpress_dashboard_notice_flash_key());

    $type = in_array($notice['type'] ?? '', ['success', 'info', 'warning', 'error'], true)
        ? (string) $notice['type']
        : 'error';

    printf(
        '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr($type),
        esc_html((string) $notice['message'])
    );
}
add_action('admin_notices', 'tailpress_dashboard_notice_render_flash');

function tailpress_dashboard_notice_register_post_type(): void
{
    register_post_type(tailpress_dashboard_notice_post_type(), [
        'labels' => [
            'name' => __('Avisos do Portal', 'tailpress'),
            'singular_name' => __('Aviso do Portal', 'tailpress'),
            'menu_name' => __('Avisos do Portal', 'tailpress'),
            'all_items' => __('Todos os Avisos do Portal', 'tailpress'),
            'add_new_item' => __('Adicionar aviso', 'tailpress'),
            'edit_item' => __('Editar aviso', 'tailpress'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-megaphone',
        'menu_position' => 31,
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
        'show_in_rest' => false,
    ]);
}
add_action('init', 'tailpress_dashboard_notice_register_post_type');

function tailpress_dashboard_notice_enforce_limit(int $post_id, WP_Post $post): void
{
    if (
        wp_is_post_revision($post_id)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || get_post_type($post_id) !== tailpress_dashboard_notice_post_type()
        || !current_user_can('edit_post', $post_id)
        || $post->post_status !== 'publish'
    ) {
        return;
    }

    $published_ids = get_posts([
        'post_type' => tailpress_dashboard_notice_post_type(),
        'post_status' => 'publish',
        'numberposts' => 3,
        'fields' => 'ids',
        'post__not_in' => [$post_id],
    ]);

    if (count($published_ids) < 3) {
        return;
    }

    remove_action('save_post_' . tailpress_dashboard_notice_post_type(), 'tailpress_dashboard_notice_enforce_limit', 10);

    wp_update_post([
        'ID' => $post_id,
        'post_status' => 'draft',
    ]);

    add_action('save_post_' . tailpress_dashboard_notice_post_type(), 'tailpress_dashboard_notice_enforce_limit', 10, 2);

    tailpress_dashboard_notice_flash(
        __('Você pode manter no máximo 3 avisos publicados ao mesmo tempo. O aviso salvo foi movido para rascunho.', 'tailpress')
    );
}
add_action('save_post_' . tailpress_dashboard_notice_post_type(), 'tailpress_dashboard_notice_enforce_limit', 10, 2);

function tailpress_dashboard_notice_items(int $limit = 3): array
{
    $limit = max(1, min(3, $limit));
    $posts = get_posts([
        'post_type' => tailpress_dashboard_notice_post_type(),
        'post_status' => 'publish',
        'numberposts' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    return array_values(array_map(static function (WP_Post $post): array {
        $description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $post->post_content)) ?? '');

        return [
            'titulo' => get_the_title($post),
            'descricao' => $description,
            'data' => wp_date('d M Y', get_post_timestamp($post)),
        ];
    }, $posts));
}
