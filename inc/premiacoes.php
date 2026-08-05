<?php
/**
 * Awards campaigns, consultant opt-ins and admin participant reporting.
 *
 * @package tailpress
 */

function tailpress_premiacoes_post_type(): string
{
    return 'lucia_premiacao';
}

function tailpress_premiacoes_meta_key(string $key): string
{
    return '_lucia_premiacao_' . $key;
}

function tailpress_premiacoes_table(): string
{
    global $wpdb;

    return $wpdb->prefix . 'lucia_premiacao_participantes';
}

function tailpress_premiacoes_register_post_type(): void
{
    register_post_type(tailpress_premiacoes_post_type(), [
        'labels' => [
            'name' => __('Premiações', 'tailpress'),
            'singular_name' => __('Premiação', 'tailpress'),
            'add_new_item' => __('Adicionar premiação', 'tailpress'),
            'edit_item' => __('Editar premiação', 'tailpress'),
            'new_item' => __('Nova premiação', 'tailpress'),
            'search_items' => __('Buscar premiações', 'tailpress'),
            'not_found' => __('Nenhuma premiação encontrada.', 'tailpress'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 31,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'manage_options',
            'read_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'delete_private_posts' => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts' => 'manage_options',
            'edit_private_posts' => 'manage_options',
            'edit_published_posts' => 'manage_options',
            'create_posts' => 'manage_options',
        ],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'tailpress_premiacoes_register_post_type');

function tailpress_premiacoes_install_table(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = tailpress_premiacoes_table();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        premiacao_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        accepted_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY premiacao_user (premiacao_id,user_id),
        KEY user_id (user_id),
        KEY accepted_at (accepted_at)
    ) {$charset_collate};";

    dbDelta($sql);

    $installed_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

    if ($installed_table === $table) {
        update_option('tailpress_premiacoes_db_version', '2');
    } else {
        delete_option('tailpress_premiacoes_db_version');
    }
}

function tailpress_premiacoes_maybe_install_table(): void
{
    if (get_option('tailpress_premiacoes_db_version') !== '2') {
        tailpress_premiacoes_install_table();
    }
}
add_action('after_switch_theme', 'tailpress_premiacoes_install_table');
add_action('init', 'tailpress_premiacoes_maybe_install_table', 5);

function tailpress_premiacoes_normalize_number($value): float
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

function tailpress_premiacoes_parse_date(string $date): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone());
    $errors = DateTimeImmutable::getLastErrors();
    $has_errors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

    if (!$parsed || $has_errors || $parsed->format('Y-m-d') !== $date) {
        return null;
    }

    return $parsed;
}

function tailpress_premiacoes_format_date(string $date): string
{
    $parsed = tailpress_premiacoes_parse_date($date);

    return $parsed ? wp_date('d M Y', $parsed->getTimestamp(), wp_timezone()) : '';
}

function tailpress_premiacoes_format_local_datetime(string $datetime): string
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $datetime, wp_timezone());

    return $parsed ? wp_date('d/m/Y H:i', $parsed->getTimestamp(), wp_timezone()) : '';
}

function tailpress_premiacoes_render_meta_box(WP_Post $post): void
{
    $type = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('type'), true) ?: 'sorteio';
    $description = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('description'), true);
    $goal_label = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('goal_label'), true);
    $goal_value = (float) get_post_meta($post->ID, tailpress_premiacoes_meta_key('goal_value'), true);
    $goal_metric = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('goal_metric'), true) ?: 'valor';
    $goal_mode = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('goal_mode'), true);
    $goal_mode = in_array($goal_mode, ['manual', 'api', 'custom'], true)
        ? $goal_mode
        : ($goal_metric === 'nenhum' ? 'custom' : 'manual');
    $start_date = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('start_date'), true);
    $end_date = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('end_date'), true);
    $status = (string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('status'), true) ?: 'aberto';
    $archived = get_post_meta($post->ID, tailpress_premiacoes_meta_key('archived'), true) === '1';
    $prizes = get_post_meta($post->ID, tailpress_premiacoes_meta_key('prizes'), true);
    $rules = get_post_meta($post->ID, tailpress_premiacoes_meta_key('rules'), true);
    $prizes = is_array($prizes) && $prizes ? $prizes : [['position' => 'Prêmio', 'description' => '']];
    $rules = is_array($rules) && $rules ? $rules : [''];

    wp_nonce_field('tailpress_premiacoes_save', 'tailpress_premiacoes_nonce');
    ?>
    <style>
        .lucia-repeatable-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
        .lucia-repeatable-row input[type="text"] { flex:1; }
        .lucia-repeatable-row .lucia-position { max-width:150px; }
    </style>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="lucia_premiacao_type"><?php esc_html_e('Tipo', 'tailpress'); ?></label></th>
            <td>
                <select id="lucia_premiacao_type" name="lucia_premiacao_type">
                    <option value="sorteio" <?php selected($type, 'sorteio'); ?>><?php esc_html_e('Sorteio', 'tailpress'); ?></option>
                    <option value="bonificacao" <?php selected($type, 'bonificacao'); ?>><?php esc_html_e('Bonificação', 'tailpress'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lucia_premiacao_description"><?php esc_html_e('Descrição', 'tailpress'); ?></label></th>
            <td><textarea id="lucia_premiacao_description" name="lucia_premiacao_description" rows="5" class="large-text" required><?php echo esc_textarea($description); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="lucia_premiacao_goal_label"><?php esc_html_e('Texto da meta', 'tailpress'); ?></label></th>
            <td><input type="text" id="lucia_premiacao_goal_label" name="lucia_premiacao_goal_label" value="<?php echo esc_attr($goal_label); ?>" class="large-text" placeholder="Ex.: R$ 1.500 em vendas no mês"></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Progresso', 'tailpress'); ?></th>
            <td>
                <select id="lucia_premiacao_goal_mode" name="lucia_premiacao_goal_mode">
                    <option value="manual" <?php selected($goal_mode, 'manual'); ?>><?php esc_html_e('Meta numérica definida pelo admin', 'tailpress'); ?></option>
                    <option value="api" <?php selected($goal_mode, 'api'); ?>><?php esc_html_e('Meta individual trazida pela API', 'tailpress'); ?></option>
                    <option value="custom" <?php selected($goal_mode, 'custom'); ?>><?php esc_html_e('Meta personalizada sem valor', 'tailpress'); ?></option>
                </select>
                <div id="lucia-manual-goal-settings" style="margin-top: 10px;">
                    <select name="lucia_premiacao_goal_metric">
                        <option value="valor" <?php selected($goal_metric, 'valor'); ?>><?php esc_html_e('Valor das pré-vendas', 'tailpress'); ?></option>
                        <option value="pecas" <?php selected($goal_metric, 'pecas'); ?>><?php esc_html_e('Quantidade de pré-vendas', 'tailpress'); ?></option>
                    </select>
                    <input type="text" name="lucia_premiacao_goal_value" value="<?php echo esc_attr((string) $goal_value); ?>" inputmode="decimal" class="regular-text" placeholder="Valor numérico da meta">
                    <p class="description"><?php esc_html_e('A barra usa as pré-vendas registradas no período da campanha.', 'tailpress'); ?></p>
                </div>
                <p id="lucia-api-goal-description" class="description"><?php esc_html_e('A meta será o valor_sugerido da consignação ativa de cada consultora. O progresso continuará considerando apenas o período da campanha.', 'tailpress'); ?></p>
                <p id="lucia-custom-goal-description" class="description"><?php esc_html_e('Use o campo “Texto da meta” para explicar a condição. Não será exibida barra de progresso automática.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lucia_premiacao_start_date"><?php esc_html_e('Data de início', 'tailpress'); ?></label></th>
            <td>
                <input type="date" id="lucia_premiacao_start_date" name="lucia_premiacao_start_date" value="<?php echo esc_attr($start_date); ?>" required>
                <p class="description"><?php esc_html_e('Somente pré-vendas registradas a partir desta data entram no progresso.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lucia_premiacao_end_date"><?php esc_html_e('Data de encerramento', 'tailpress'); ?></label></th>
            <td><input type="date" id="lucia_premiacao_end_date" name="lucia_premiacao_end_date" value="<?php echo esc_attr($end_date); ?>" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="lucia_premiacao_status"><?php esc_html_e('Status', 'tailpress'); ?></label></th>
            <td>
                <select id="lucia_premiacao_status" name="lucia_premiacao_status">
                    <option value="aberto" <?php selected($status, 'aberto'); ?>><?php esc_html_e('Inscrições abertas', 'tailpress'); ?></option>
                    <option value="encerrado" <?php selected($status, 'encerrado'); ?>><?php esc_html_e('Encerrada', 'tailpress'); ?></option>
                    <option value="ganhador" <?php selected($status, 'ganhador'); ?>><?php esc_html_e('Encerrada com ganhador', 'tailpress'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Visibilidade', 'tailpress'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="lucia_premiacao_archived" value="1" <?php checked($archived); ?>>
                    <?php esc_html_e('Arquivar e ocultar do portal', 'tailpress'); ?>
                </label>
                <p class="description"><?php esc_html_e('A campanha e suas participantes continuam disponíveis no admin. Campanhas encerradas também são ocultadas automaticamente 30 dias após o término.', 'tailpress'); ?></p>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e('Prêmios', 'tailpress'); ?></h3>
    <p><?php esc_html_e('Adicione quantos prêmios forem necessários, inclusive mais de um para a mesma colocação.', 'tailpress'); ?></p>
    <div id="lucia-prizes">
        <?php foreach ($prizes as $index => $prize): ?>
            <div class="lucia-repeatable-row">
                <input class="lucia-position" type="text" name="lucia_premiacao_prizes[<?php echo esc_attr((string) $index); ?>][position]" value="<?php echo esc_attr((string) ($prize['position'] ?? '')); ?>" placeholder="Ex.: 1º lugar">
                <input type="text" name="lucia_premiacao_prizes[<?php echo esc_attr((string) $index); ?>][description]" value="<?php echo esc_attr((string) ($prize['description'] ?? '')); ?>" placeholder="Descrição do prêmio">
                <button type="button" class="button lucia-remove-row"><?php esc_html_e('Remover', 'tailpress'); ?></button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="lucia-add-prize"><?php esc_html_e('Adicionar prêmio', 'tailpress'); ?></button>

    <h3><?php esc_html_e('Regras', 'tailpress'); ?></h3>
    <div id="lucia-rules">
        <?php foreach ($rules as $index => $rule): ?>
            <div class="lucia-repeatable-row">
                <input type="text" name="lucia_premiacao_rules[<?php echo esc_attr((string) $index); ?>]" value="<?php echo esc_attr((string) $rule); ?>" placeholder="Regra da campanha">
                <button type="button" class="button lucia-remove-row"><?php esc_html_e('Remover', 'tailpress'); ?></button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="lucia-add-rule"><?php esc_html_e('Adicionar regra', 'tailpress'); ?></button>

    <script>
    (() => {
        const prizes = document.getElementById('lucia-prizes');
        const rules = document.getElementById('lucia-rules');
        const goalMode = document.getElementById('lucia_premiacao_goal_mode');
        const manualGoalSettings = document.getElementById('lucia-manual-goal-settings');
        const apiGoalDescription = document.getElementById('lucia-api-goal-description');
        const customGoalDescription = document.getElementById('lucia-custom-goal-description');
        const syncGoalMode = () => {
            manualGoalSettings.hidden = goalMode.value !== 'manual';
            apiGoalDescription.hidden = goalMode.value !== 'api';
            customGoalDescription.hidden = goalMode.value !== 'custom';
        };
        goalMode.addEventListener('change', syncGoalMode);
        syncGoalMode();
        const bindRemove = (root) => root.addEventListener('click', (event) => {
            if (event.target.classList.contains('lucia-remove-row')) event.target.closest('.lucia-repeatable-row').remove();
        });
        bindRemove(prizes);
        bindRemove(rules);
        document.getElementById('lucia-add-prize').addEventListener('click', () => {
            const index = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
            prizes.insertAdjacentHTML('beforeend', `<div class="lucia-repeatable-row"><input class="lucia-position" type="text" name="lucia_premiacao_prizes[${index}][position]" placeholder="Ex.: 1º lugar"><input type="text" name="lucia_premiacao_prizes[${index}][description]" placeholder="Descrição do prêmio"><button type="button" class="button lucia-remove-row">Remover</button></div>`);
        });
        document.getElementById('lucia-add-rule').addEventListener('click', () => {
            const index = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
            rules.insertAdjacentHTML('beforeend', `<div class="lucia-repeatable-row"><input type="text" name="lucia_premiacao_rules[${index}]" placeholder="Regra da campanha"><button type="button" class="button lucia-remove-row">Remover</button></div>`);
        });
    })();
    </script>
    <?php
}

function tailpress_premiacoes_add_meta_boxes(): void
{
    add_meta_box('tailpress_premiacoes_settings', __('Configuração da premiação', 'tailpress'), 'tailpress_premiacoes_render_meta_box', tailpress_premiacoes_post_type(), 'normal', 'high');
}
add_action('add_meta_boxes', 'tailpress_premiacoes_add_meta_boxes');

function tailpress_premiacoes_save(int $post_id): void
{
    if (!isset($_POST['tailpress_premiacoes_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tailpress_premiacoes_nonce'])), 'tailpress_premiacoes_save')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || get_post_type($post_id) !== tailpress_premiacoes_post_type() || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $type = isset($_POST['lucia_premiacao_type']) ? sanitize_key(wp_unslash($_POST['lucia_premiacao_type'])) : 'sorteio';
    $type = in_array($type, ['sorteio', 'bonificacao'], true) ? $type : 'sorteio';
    $status = isset($_POST['lucia_premiacao_status']) ? sanitize_key(wp_unslash($_POST['lucia_premiacao_status'])) : 'aberto';
    $status = in_array($status, ['aberto', 'encerrado', 'ganhador'], true) ? $status : 'aberto';
    $archived = isset($_POST['lucia_premiacao_archived']) && wp_unslash($_POST['lucia_premiacao_archived']) === '1';
    $goal_mode = isset($_POST['lucia_premiacao_goal_mode']) ? sanitize_key(wp_unslash($_POST['lucia_premiacao_goal_mode'])) : 'manual';
    $goal_mode = in_array($goal_mode, ['manual', 'api', 'custom'], true) ? $goal_mode : 'manual';
    $metric = isset($_POST['lucia_premiacao_goal_metric']) ? sanitize_key(wp_unslash($_POST['lucia_premiacao_goal_metric'])) : 'valor';
    $metric = in_array($metric, ['valor', 'pecas'], true) ? $metric : 'valor';
    $goal_value = tailpress_premiacoes_normalize_number(wp_unslash($_POST['lucia_premiacao_goal_value'] ?? 0));

    if ($goal_mode === 'api') {
        $metric = 'valor';
        $goal_value = 0;
    } elseif ($goal_mode === 'custom') {
        $metric = 'nenhum';
        $goal_value = 0;
    }
    $start_date = isset($_POST['lucia_premiacao_start_date']) ? sanitize_text_field(wp_unslash($_POST['lucia_premiacao_start_date'])) : '';
    $start_date = tailpress_premiacoes_parse_date($start_date) ? $start_date : '';
    $end_date = isset($_POST['lucia_premiacao_end_date']) ? sanitize_text_field(wp_unslash($_POST['lucia_premiacao_end_date'])) : '';
    $end_date = tailpress_premiacoes_parse_date($end_date) ? $end_date : '';

    if ($start_date !== '' && $end_date !== '' && $start_date > $end_date) {
        $start_date = '';
    }
    $prizes = [];
    $rules = [];

    if (isset($_POST['lucia_premiacao_prizes']) && is_array($_POST['lucia_premiacao_prizes'])) {
        foreach (wp_unslash($_POST['lucia_premiacao_prizes']) as $prize) {
            if (!is_array($prize)) continue;
            $position = sanitize_text_field($prize['position'] ?? '');
            $description = sanitize_text_field($prize['description'] ?? '');
            if ($description !== '') $prizes[] = ['position' => $position, 'description' => $description];
        }
    }

    if (isset($_POST['lucia_premiacao_rules']) && is_array($_POST['lucia_premiacao_rules'])) {
        foreach (wp_unslash($_POST['lucia_premiacao_rules']) as $rule) {
            $rule = sanitize_text_field($rule);
            if ($rule !== '') $rules[] = $rule;
        }
    }

    update_post_meta($post_id, tailpress_premiacoes_meta_key('type'), $type);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('description'), sanitize_textarea_field(wp_unslash($_POST['lucia_premiacao_description'] ?? '')));
    update_post_meta($post_id, tailpress_premiacoes_meta_key('goal_label'), sanitize_text_field(wp_unslash($_POST['lucia_premiacao_goal_label'] ?? '')));
    update_post_meta($post_id, tailpress_premiacoes_meta_key('goal_mode'), $goal_mode);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('goal_value'), $goal_value);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('goal_metric'), $metric);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('start_date'), $start_date);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('end_date'), $end_date);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('status'), $status);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('archived'), $archived ? '1' : '0');
    update_post_meta($post_id, tailpress_premiacoes_meta_key('prizes'), $prizes);
    update_post_meta($post_id, tailpress_premiacoes_meta_key('rules'), $rules);
}
add_action('save_post_' . tailpress_premiacoes_post_type(), 'tailpress_premiacoes_save');

function tailpress_premiacoes_effective_status(int $post_id): string
{
    $status = (string) get_post_meta($post_id, tailpress_premiacoes_meta_key('status'), true);
    $status = in_array($status, ['aberto', 'encerrado', 'ganhador'], true) ? $status : 'aberto';
    $end_date = (string) get_post_meta($post_id, tailpress_premiacoes_meta_key('end_date'), true);
    $parsed_end_date = tailpress_premiacoes_parse_date($end_date);

    if ($status === 'aberto' && $parsed_end_date && $parsed_end_date->format('Y-m-d') < current_time('Y-m-d')) {
        return 'encerrado';
    }

    return $status;
}

function tailpress_premiacoes_is_visible_in_portal(WP_Post $post): bool
{
    if (get_post_meta($post->ID, tailpress_premiacoes_meta_key('archived'), true) === '1') {
        return false;
    }

    $status = tailpress_premiacoes_effective_status((int) $post->ID);

    if ($status !== 'encerrado') {
        return true;
    }

    $end_date = tailpress_premiacoes_parse_date((string) get_post_meta($post->ID, tailpress_premiacoes_meta_key('end_date'), true));

    if (!$end_date) {
        return true;
    }

    $hide_after = $end_date->modify('+30 days')->format('Y-m-d');

    return current_time('Y-m-d') <= $hide_after;
}

function tailpress_premiacoes_campaign_progress(array $items, string $metric, string $start_date, string $end_date): float
{
    $progress = 0.0;

    foreach ($items as $item) {
        if (!is_array($item) || ($item['pre_venda'] ?? 'N') !== 'S') {
            continue;
        }

        $item_date = substr((string) ($item['data_prevenda'] ?? ''), 0, 10);

        if (!tailpress_premiacoes_parse_date($item_date) || $item_date < $start_date || $item_date > $end_date) {
            continue;
        }

        $progress += $metric === 'pecas' ? 1 : (float) ($item['valor_unitario'] ?? 0);
    }

    return $progress;
}

function tailpress_premiacoes_frontend_payload(int $user_id): array
{
    global $wpdb;

    $posts = get_posts([
        'post_type' => tailpress_premiacoes_post_type(),
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_key' => tailpress_premiacoes_meta_key('end_date'),
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ]);
    $posts = array_values(array_filter($posts, 'tailpress_premiacoes_is_visible_in_portal'));
    $ids = array_map(static fn($post) => (int) $post->ID, $posts);
    $participant_counts = [];
    $accepted_ids = [];

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $table = tailpress_premiacoes_table();
        $count_query = $wpdb->prepare("SELECT premiacao_id, COUNT(*) AS total FROM {$table} WHERE premiacao_id IN ({$placeholders}) GROUP BY premiacao_id", ...$ids);
        foreach ((array) $wpdb->get_results($count_query) as $row) {
            $participant_counts[(int) $row->premiacao_id] = (int) $row->total;
        }
        $accepted_query = $wpdb->prepare("SELECT premiacao_id FROM {$table} WHERE user_id = %d AND premiacao_id IN ({$placeholders})", $user_id, ...$ids);
        $accepted_ids = array_map('intval', (array) $wpdb->get_col($accepted_query));
    }

    $campaign_contexts = [];
    $needs_sales_data = false;

    foreach ($posts as $post) {
        $id = (int) $post->ID;
        $status = tailpress_premiacoes_effective_status($id);
        $accepted = in_array($id, $accepted_ids, true);
        $metric = (string) get_post_meta($id, tailpress_premiacoes_meta_key('goal_metric'), true) ?: 'valor';
        $goal_mode = (string) get_post_meta($id, tailpress_premiacoes_meta_key('goal_mode'), true);
        $goal_mode = in_array($goal_mode, ['manual', 'api', 'custom'], true)
            ? $goal_mode
            : ($metric === 'nenhum' ? 'custom' : 'manual');
        $goal_value = (float) get_post_meta($id, tailpress_premiacoes_meta_key('goal_value'), true);
        $start_date = (string) get_post_meta($id, tailpress_premiacoes_meta_key('start_date'), true);
        $end_date = (string) get_post_meta($id, tailpress_premiacoes_meta_key('end_date'), true);

        if (!tailpress_premiacoes_parse_date($start_date)) {
            $start_date = get_post_time('Y-m-d', false, $post);
        }

        $campaign_contexts[$id] = compact('status', 'accepted', 'metric', 'goal_mode', 'goal_value', 'start_date', 'end_date');

        $has_numeric_goal = $goal_mode === 'api' || ($goal_mode === 'manual' && $goal_value > 0);
        if ($accepted && $status === 'aberto' && $has_numeric_goal && tailpress_premiacoes_parse_date($end_date)) {
            $needs_sales_data = true;
        }
    }

    $sales_items = [];
    $api_goal_value = 0.0;
    if ($needs_sales_data && function_exists('tailpress_consignacao_get_current_user_data')) {
        $consignacao = tailpress_consignacao_get_current_user_data();
        if (!empty($consignacao['ok']) && is_array($consignacao['itens_ativos'] ?? null)) {
            $sales_items = $consignacao['itens_ativos'];
            $api_goal_value = (float) ($consignacao['totais']['valor_sugerido'] ?? 0);
        }
    }

    $campaigns = [];
    foreach ($posts as $post) {
        $id = (int) $post->ID;
        $context = $campaign_contexts[$id];
        $status = $context['status'];
        $accepted = $context['accepted'];
        $metric = $context['metric'];
        $goal_mode = $context['goal_mode'];
        $goal_value = $goal_mode === 'api' ? $api_goal_value : $context['goal_value'];
        $start_date = $context['start_date'];
        $end_date = $context['end_date'];
        $progress = $accepted && $status === 'aberto' && $metric !== 'nenhum' && $goal_value > 0 && $end_date !== ''
            ? tailpress_premiacoes_campaign_progress($sales_items, $metric, $start_date, $end_date)
            : 0;
        $prizes = get_post_meta($id, tailpress_premiacoes_meta_key('prizes'), true);
        $rules = get_post_meta($id, tailpress_premiacoes_meta_key('rules'), true);
        $goal_label = (string) get_post_meta($id, tailpress_premiacoes_meta_key('goal_label'), true);

        if ($goal_label === '' && $goal_mode === 'api') {
            $goal_label = __('Meta individual definida pela consignação', 'tailpress');
        }

        $campaigns[] = [
            'id' => $id,
            'type' => (string) get_post_meta($id, tailpress_premiacoes_meta_key('type'), true) ?: 'sorteio',
            'title' => get_the_title($id),
            'description' => (string) get_post_meta($id, tailpress_premiacoes_meta_key('description'), true),
            'prizes' => is_array($prizes) ? array_values($prizes) : [],
            'goalLabel' => $goal_label,
            'goalMode' => $goal_mode,
            'goalValue' => $goal_value,
            'goalMetric' => $metric,
            'progress' => $progress,
            'endDate' => tailpress_premiacoes_format_date($end_date),
            'endDateValue' => $end_date,
            'status' => $accepted && $status === 'aberto' ? 'participando' : $status,
            'participants' => (int) ($participant_counts[$id] ?? 0),
            'rules' => is_array($rules) ? array_values($rules) : [],
        ];
    }

    $status_order = ['participando' => 0, 'aberto' => 1, 'ganhador' => 2, 'encerrado' => 3];
    usort($campaigns, static function (array $first, array $second) use ($status_order): int {
        $status_comparison = ($status_order[$first['status']] ?? 4) <=> ($status_order[$second['status']] ?? 4);

        return $status_comparison !== 0 ? $status_comparison : strcmp($first['endDateValue'], $second['endDateValue']);
    });

    foreach ($campaigns as &$campaign) {
        unset($campaign['endDateValue']);
    }
    unset($campaign);

    return ['campaigns' => $campaigns];
}

function tailpress_premiacoes_rest_permission()
{
    if (!is_user_logged_in()) return new WP_Error('lucia_not_logged_in', __('Faça login para continuar.', 'tailpress'), ['status' => 401]);
    if (!tailpress_user_is_consultora()) return new WP_Error('lucia_forbidden', __('Apenas consultoras podem participar.', 'tailpress'), ['status' => 403]);

    return true;
}

function tailpress_premiacoes_rest_accept(WP_REST_Request $request)
{
    global $wpdb;

    $premiacao_id = absint($request->get_param('premiacao_id'));
    if (!$premiacao_id || get_post_type($premiacao_id) !== tailpress_premiacoes_post_type() || get_post_status($premiacao_id) !== 'publish') {
        return new WP_Error('lucia_premiacao_invalid', __('Premiação não encontrada.', 'tailpress'), ['status' => 404]);
    }
    if (tailpress_premiacoes_effective_status($premiacao_id) !== 'aberto') {
        return new WP_Error('lucia_premiacao_closed', __('As inscrições desta campanha estão encerradas.', 'tailpress'), ['status' => 409]);
    }

    $result = $wpdb->query($wpdb->prepare(
        'INSERT IGNORE INTO ' . tailpress_premiacoes_table() . ' (premiacao_id, user_id, accepted_at) VALUES (%d, %d, %s)',
        $premiacao_id,
        get_current_user_id(),
        current_time('mysql')
    ));

    if ($result === false) return new WP_Error('lucia_premiacao_db', __('Não foi possível registrar sua participação.', 'tailpress'), ['status' => 500]);

    return rest_ensure_response(array_merge(
        ['success' => true, 'alreadyParticipating' => $result === 0],
        tailpress_premiacoes_frontend_payload(get_current_user_id())
    ));
}

function tailpress_premiacoes_register_rest_routes(): void
{
    register_rest_route('lucia-portal/v1', '/premiacoes', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => static fn() => rest_ensure_response(tailpress_premiacoes_frontend_payload(get_current_user_id())),
        'permission_callback' => 'tailpress_premiacoes_rest_permission',
    ]);
    register_rest_route('lucia-portal/v1', '/premiacoes/participar', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'tailpress_premiacoes_rest_accept',
        'permission_callback' => 'tailpress_premiacoes_rest_permission',
        'args' => ['premiacao_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1]],
    ]);
}
add_action('rest_api_init', 'tailpress_premiacoes_register_rest_routes');

function tailpress_premiacoes_admin_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=' . tailpress_premiacoes_post_type(),
        __('Participantes', 'tailpress'),
        __('Participantes', 'tailpress'),
        'manage_options',
        'lucia-premiacoes-participantes',
        'tailpress_premiacoes_render_participants_page'
    );
}
add_action('admin_menu', 'tailpress_premiacoes_admin_menu');

function tailpress_premiacoes_render_participants_page(): void
{
    global $wpdb;

    if (!current_user_can('manage_options')) wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'tailpress'));

    $campaign_id = isset($_GET['premiacao_id']) ? absint($_GET['premiacao_id']) : 0;
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
    $per_page = 25;
    $offset = ($paged - 1) * $per_page;
    $table = tailpress_premiacoes_table();
    $where = ['1=1'];
    $params = [];

    if ($campaign_id) {
        $where[] = 'p.premiacao_id = %d';
        $params[] = $campaign_id;
    }
    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
        $params[] = $like;
        $params[] = $like;
    }

    $where_sql = implode(' AND ', $where);
    $from_sql = "FROM {$table} p INNER JOIN {$wpdb->users} u ON u.ID = p.user_id INNER JOIN {$wpdb->posts} c ON c.ID = p.premiacao_id";
    $count_sql = "SELECT COUNT(*) {$from_sql} WHERE {$where_sql}";
    $total = (int) $wpdb->get_var($params ? $wpdb->prepare($count_sql, ...$params) : $count_sql);
    $list_sql = "SELECT p.accepted_at, p.premiacao_id, u.ID AS user_id, u.display_name, u.user_email, c.post_title {$from_sql} WHERE {$where_sql} ORDER BY p.accepted_at DESC LIMIT %d OFFSET %d";
    $list_params = array_merge($params, [$per_page, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($list_sql, ...$list_params));
    $campaigns = get_posts(['post_type' => tailpress_premiacoes_post_type(), 'post_status' => ['publish', 'draft'], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Participantes das premiações', 'tailpress'); ?></h1>
        <form method="get">
            <input type="hidden" name="post_type" value="<?php echo esc_attr(tailpress_premiacoes_post_type()); ?>">
            <input type="hidden" name="page" value="lucia-premiacoes-participantes">
            <select name="premiacao_id">
                <option value="0"><?php esc_html_e('Todas as campanhas', 'tailpress'); ?></option>
                <?php foreach ($campaigns as $campaign): ?>
                    <option value="<?php echo esc_attr((string) $campaign->ID); ?>" <?php selected($campaign_id, $campaign->ID); ?>><?php echo esc_html($campaign->post_title); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Nome ou e-mail">
            <button class="button"><?php esc_html_e('Filtrar', 'tailpress'); ?></button>
        </form>
        <p class="description"><?php echo esc_html(sprintf(_n('%d participação encontrada.', '%d participações encontradas.', $total, 'tailpress'), $total)); ?></p>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Consultora', 'tailpress'); ?></th><th><?php esc_html_e('Campanha', 'tailpress'); ?></th><th><?php esc_html_e('Aceite', 'tailpress'); ?></th></tr></thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="3"><?php esc_html_e('Nenhuma participante encontrada para estes filtros.', 'tailpress'); ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?php echo esc_html($row->display_name); ?></strong><br><span class="description"><?php echo esc_html($row->user_email); ?></span></td>
                        <td><a href="<?php echo esc_url(get_edit_post_link((int) $row->premiacao_id)); ?>"><?php echo esc_html($row->post_title); ?></a></td>
                        <td><?php echo esc_html(tailpress_premiacoes_format_local_datetime((string) $row->accepted_at)); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php
        $total_pages = (int) ceil($total / $per_page);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&lsaquo;',
                'next_text' => '&rsaquo;',
            ])) . '</div></div>';
        }
        ?>
    </div>
    <?php
}
