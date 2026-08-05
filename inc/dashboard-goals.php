<?php
/**
 * Dashboard monthly sales goals.
 *
 * @package tailpress
 */

function tailpress_dashboard_goal_post_type(): string
{
    return 'lucia_meta_mensal';
}

function tailpress_dashboard_goal_meta_key(string $key): string
{
    return '_lucia_goal_' . $key;
}

function tailpress_dashboard_goal_register_post_type(): void
{
    register_post_type(tailpress_dashboard_goal_post_type(), [
        'labels' => [
            'name' => __('Metas de Vendas', 'tailpress'),
            'singular_name' => __('Meta de Vendas', 'tailpress'),
            'add_new_item' => __('Adicionar Meta de Vendas', 'tailpress'),
            'edit_item' => __('Editar Meta de Vendas', 'tailpress'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 30,
        'menu_icon' => 'dashicons-chart-line',
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false,
    ]);
}
add_action('init', 'tailpress_dashboard_goal_register_post_type');

function tailpress_dashboard_goal_current_month(): string
{
    return current_time('Y-m');
}

function tailpress_dashboard_goal_default(): array
{
    return [
        'configured' => false,
        'month' => tailpress_dashboard_goal_current_month(),
        'metric' => 'pecas',
        'target' => 0,
        'source' => 'none',
    ];
}

function tailpress_dashboard_goal_get_consultoras(): array
{
    return get_users([
        'role__in' => ['consultora'],
        'orderby' => 'display_name',
        'order' => 'ASC',
        'fields' => ['ID', 'display_name', 'user_email'],
    ]);
}

function tailpress_dashboard_goal_normalize_metric($metric): string
{
    return in_array($metric, ['pecas', 'valor'], true) ? $metric : 'pecas';
}

function tailpress_dashboard_goal_normalize_number($value): float
{
    $value = preg_replace('/[^\d,.-]/', '', (string) $value);

    if (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (str_contains($value, ',')) {
        $value = str_replace(',', '.', $value);
    }

    return max(0, (float) $value);
}

function tailpress_dashboard_goal_render_meta_box(WP_Post $post): void
{
    $month = (string) get_post_meta($post->ID, tailpress_dashboard_goal_meta_key('month'), true);
    $metric = tailpress_dashboard_goal_normalize_metric(get_post_meta($post->ID, tailpress_dashboard_goal_meta_key('metric'), true));
    $global_target = (float) get_post_meta($post->ID, tailpress_dashboard_goal_meta_key('global_target'), true);
    $overrides = get_post_meta($post->ID, tailpress_dashboard_goal_meta_key('consultora_targets'), true);
    $overrides = is_array($overrides) ? $overrides : [];

    if ($month === '') {
        $month = tailpress_dashboard_goal_current_month();
    }

    wp_nonce_field('tailpress_dashboard_goal_save', 'tailpress_dashboard_goal_nonce');
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="lucia_goal_month"><?php esc_html_e('Mês da meta', 'tailpress'); ?></label>
            </th>
            <td>
                <input type="month" id="lucia_goal_month" name="lucia_goal_month" value="<?php echo esc_attr($month); ?>" required>
                <p class="description"><?php esc_html_e('A meta será aplicada ao mês selecionado.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Medir meta por', 'tailpress'); ?></th>
            <td>
                <label>
                    <input type="radio" name="lucia_goal_metric" value="pecas" <?php checked($metric, 'pecas'); ?>>
                    <?php esc_html_e('Peças vendidas', 'tailpress'); ?>
                </label>
                <br>
                <label>
                    <input type="radio" name="lucia_goal_metric" value="valor" <?php checked($metric, 'valor'); ?>>
                    <?php esc_html_e('Valor vendido', 'tailpress'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="lucia_goal_global_target"><?php esc_html_e('Meta global', 'tailpress'); ?></label>
            </th>
            <td>
                <input type="text" id="lucia_goal_global_target" name="lucia_goal_global_target" value="<?php echo esc_attr((string) $global_target); ?>" class="regular-text" inputmode="decimal">
                <p class="description"><?php esc_html_e('Usada por todas as consultoras sem meta própria.', 'tailpress'); ?></p>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e('Metas por consultora', 'tailpress'); ?></h3>
    <p><?php esc_html_e('Opcional. Se preenchida, substitui a meta global para a consultora.', 'tailpress'); ?></p>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Consultora', 'tailpress'); ?></th>
                <th><?php esc_html_e('Meta específica', 'tailpress'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (tailpress_dashboard_goal_get_consultoras() as $consultora): ?>
                <?php $value = isset($overrides[(string) $consultora->ID]) ? (float) $overrides[(string) $consultora->ID] : ''; ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($consultora->display_name); ?></strong>
                        <br>
                        <span class="description"><?php echo esc_html($consultora->user_email); ?></span>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="lucia_goal_consultora_targets[<?php echo esc_attr((string) $consultora->ID); ?>]"
                            value="<?php echo esc_attr((string) $value); ?>"
                            inputmode="decimal"
                            class="regular-text"
                        >
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function tailpress_dashboard_goal_add_meta_boxes(): void
{
    add_meta_box(
        'tailpress_dashboard_goal_settings',
        __('Configuração da meta mensal', 'tailpress'),
        'tailpress_dashboard_goal_render_meta_box',
        tailpress_dashboard_goal_post_type(),
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'tailpress_dashboard_goal_add_meta_boxes');

function tailpress_dashboard_goal_save(int $post_id): void
{
    if (!isset($_POST['tailpress_dashboard_goal_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tailpress_dashboard_goal_nonce'])), 'tailpress_dashboard_goal_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== tailpress_dashboard_goal_post_type() || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $month = isset($_POST['lucia_goal_month']) ? sanitize_text_field(wp_unslash($_POST['lucia_goal_month'])) : tailpress_dashboard_goal_current_month();

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = tailpress_dashboard_goal_current_month();
    }

    $metric = isset($_POST['lucia_goal_metric'])
        ? tailpress_dashboard_goal_normalize_metric(sanitize_key(wp_unslash($_POST['lucia_goal_metric'])))
        : 'pecas';
    $global_target = isset($_POST['lucia_goal_global_target'])
        ? tailpress_dashboard_goal_normalize_number(wp_unslash($_POST['lucia_goal_global_target']))
        : 0;
    $targets = [];

    if (isset($_POST['lucia_goal_consultora_targets']) && is_array($_POST['lucia_goal_consultora_targets'])) {
        foreach (wp_unslash($_POST['lucia_goal_consultora_targets']) as $user_id => $value) {
            $target = tailpress_dashboard_goal_normalize_number($value);

            if ($target > 0) {
                $targets[(string) absint($user_id)] = $target;
            }
        }
    }

    update_post_meta($post_id, tailpress_dashboard_goal_meta_key('month'), $month);
    update_post_meta($post_id, tailpress_dashboard_goal_meta_key('metric'), $metric);
    update_post_meta($post_id, tailpress_dashboard_goal_meta_key('global_target'), $global_target);
    update_post_meta($post_id, tailpress_dashboard_goal_meta_key('consultora_targets'), $targets);
}
add_action('save_post_' . tailpress_dashboard_goal_post_type(), 'tailpress_dashboard_goal_save');

function tailpress_dashboard_goal_for_user(int $user_id, ?string $month = null): array
{
    $month = $month ?: tailpress_dashboard_goal_current_month();
    $posts = get_posts([
        'post_type' => tailpress_dashboard_goal_post_type(),
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => tailpress_dashboard_goal_meta_key('month'),
                'value' => $month,
            ],
        ],
    ]);

    if (empty($posts)) {
        return tailpress_dashboard_goal_default();
    }

    $post_id = (int) $posts[0]->ID;
    $metric = tailpress_dashboard_goal_normalize_metric(get_post_meta($post_id, tailpress_dashboard_goal_meta_key('metric'), true));
    $global_target = (float) get_post_meta($post_id, tailpress_dashboard_goal_meta_key('global_target'), true);
    $overrides = get_post_meta($post_id, tailpress_dashboard_goal_meta_key('consultora_targets'), true);
    $overrides = is_array($overrides) ? $overrides : [];
    $user_key = (string) $user_id;
    $target = isset($overrides[$user_key]) && (float) $overrides[$user_key] > 0
        ? (float) $overrides[$user_key]
        : $global_target;

    if ($target <= 0) {
        return array_merge(tailpress_dashboard_goal_default(), [
            'month' => $month,
            'metric' => $metric,
        ]);
    }

    return [
        'configured' => true,
        'month' => $month,
        'metric' => $metric,
        'target' => $target,
        'source' => isset($overrides[$user_key]) && (float) $overrides[$user_key] > 0 ? 'consultora' : 'global',
    ];
}
