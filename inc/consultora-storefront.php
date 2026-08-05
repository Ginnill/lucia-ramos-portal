<?php
/**
 * Public storefronts for consultora consignacoes.
 *
 * @package tailpress
 */

function tailpress_storefront_post_type(): string
{
    return 'lucia_vitrine';
}

function tailpress_storefront_register_post_type(): void
{
    register_post_type(tailpress_storefront_post_type(), [
        'labels' => [
            'name' => __('Vitrines de consultoras', 'tailpress'),
            'singular_name' => __('Vitrine de consultora', 'tailpress'),
        ],
        'public' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_rest' => false,
        'supports' => ['title'],
        'rewrite' => false,
        'capability_type' => 'post',
    ]);
}
add_action('init', 'tailpress_storefront_register_post_type');

function tailpress_storefront_add_rewrite_rule(): void
{
    add_rewrite_rule('^consultora/([A-Za-z0-9_-]+)/?$', 'index.php?lucia_storefront_token=$matches[1]', 'top');
}
add_action('init', 'tailpress_storefront_add_rewrite_rule');

function tailpress_storefront_query_vars(array $vars): array
{
    $vars[] = 'lucia_storefront_token';

    return $vars;
}
add_filter('query_vars', 'tailpress_storefront_query_vars');

function tailpress_storefront_meta_key(string $key): string
{
    return '_lucia_storefront_' . $key;
}

function tailpress_storefront_normalize_token($token): string
{
    return preg_replace('/[^A-Za-z0-9_-]+/', '', (string) $token) ?: '';
}

function tailpress_storefront_token_from_request(): string
{
    $query_token = get_query_var('lucia_storefront_token');

    if ($query_token !== '') {
        return tailpress_storefront_normalize_token($query_token);
    }

    $path = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($path, PHP_URL_PATH), '/');
    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
        $path = substr($path, strlen($home_path) + 1);
    }

    if (!preg_match('#^consultora/([A-Za-z0-9_-]+)/?$#', $path, $matches)) {
        return '';
    }

    return tailpress_storefront_normalize_token($matches[1]);
}

function tailpress_is_consultora_storefront_request(): bool
{
    return tailpress_storefront_token_from_request() !== '';
}

function tailpress_storefront_public_url(string $token): string
{
    return home_url('/consultora/' . rawurlencode($token) . '/');
}

function tailpress_storefront_find_by_token(string $token): ?WP_Post
{
    $token = tailpress_storefront_normalize_token($token);

    if ($token === '') {
        return null;
    }

    $post = get_page_by_path($token, OBJECT, tailpress_storefront_post_type());

    return $post instanceof WP_Post ? $post : null;
}

function tailpress_storefront_find_for_consignacao(int $user_id, string $consignacao_id): ?WP_Post
{
    $posts = get_posts([
        'post_type' => tailpress_storefront_post_type(),
        'post_status' => 'publish',
        'numberposts' => 1,
        'fields' => 'all',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => tailpress_storefront_meta_key('consultora_user_id'),
                'value' => (string) $user_id,
            ],
            [
                'key' => tailpress_storefront_meta_key('consignacao_id'),
                'value' => $consignacao_id,
            ],
        ],
    ]);

    return isset($posts[0]) && $posts[0] instanceof WP_Post ? $posts[0] : null;
}

function tailpress_storefront_generate_token(): string
{
    do {
        $token = strtolower(wp_generate_password(16, false, false));
    } while (tailpress_storefront_find_by_token($token));

    return $token;
}

function tailpress_storefront_consultora_name(int $user_id, array $data): string
{
    $api_name = trim((string) ($data['revendedora']['nome'] ?? ''));

    if ($api_name !== '') {
        return $api_name;
    }

    $user = get_user_by('ID', $user_id);

    if ($user instanceof WP_User && $user->exists()) {
        return (string) $user->display_name;
    }

    return __('Consultora Lúcia Ramos', 'tailpress');
}

function tailpress_storefront_consultora_whatsapp(int $user_id): string
{
    if (!function_exists('tailpress_consultora_whatsapp_meta_key')) {
        return '';
    }

    $whatsapp = get_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), true);

    return function_exists('tailpress_normalize_phone') ? tailpress_normalize_phone($whatsapp) : preg_replace('/\D+/', '', (string) $whatsapp);
}

function tailpress_storefront_build_products(array $items): array
{
    $available_items = array_values(array_filter($items, static function ($item) {
        return empty($item['data_devolvido']) && ($item['pre_venda'] ?? 'N') !== 'S';
    }));

    $groups = function_exists('tailpress_consignacao_group_stock_items')
        ? tailpress_consignacao_group_stock_items($available_items)
        : [];

    return array_values(array_map(static function ($product) {
        $raw_name = (string) ($product['nome'] ?? '');
        $product_code = (string) ($product['codigo'] ?? '');

        if ($product_code === '' && function_exists('tailpress_consignacao_extract_description_code')) {
            $product_code = tailpress_consignacao_extract_description_code($raw_name);
        }

        return [
            'id' => (string) ($product['id'] ?? ''),
            'nome' => function_exists('tailpress_consignacao_normalize_description')
                ? tailpress_consignacao_normalize_description($raw_name)
                : $raw_name,
            'codigo' => $product_code,
            'unidade' => (string) ($product['unidade'] ?? ''),
            'tamanho' => (string) ($product['tamanho'] ?? ''),
            'valor_unitario' => (float) ($product['valor_unitario'] ?? 0),
            'imagem_url' => (string) ($product['imagem_url'] ?? ''),
            'quantidade' => (int) ($product['disponivel_quantidade'] ?? $product['quantidade'] ?? 0),
        ];
    }, $groups));
}

function tailpress_storefront_build_snapshot(int $user_id, array $data, array $consignacao): array
{
    $products = tailpress_storefront_build_products((array) ($consignacao['itens'] ?? []));
    $total_items = array_reduce($products, static fn($sum, $product) => $sum + (int) ($product['quantidade'] ?? 0), 0);
    $total_value = array_reduce($products, static function ($sum, $product) {
        return $sum + ((int) ($product['quantidade'] ?? 0) * (float) ($product['valor_unitario'] ?? 0));
    }, 0.0);

    return [
        'version' => 3,
        'consultora_nome' => tailpress_storefront_consultora_name($user_id, $data),
        'consultora_whatsapp' => tailpress_storefront_consultora_whatsapp($user_id),
        'consignacao_id' => (string) ($consignacao['consignacao_id'] ?? ''),
        'data_abertura' => $consignacao['data_abertura'] ?? null,
        'data_acerto' => $consignacao['data_acerto'] ?? null,
        'updated_at' => current_time('mysql'),
        'totais' => [
            'produtos' => count($products),
            'pecas' => $total_items,
            'valor_disponivel' => $total_value,
        ],
        'produtos' => $products,
    ];
}

function tailpress_storefront_upsert_snapshot(int $user_id, array $data, array $consignacao): ?array
{
    $consignacao_id = (string) ($consignacao['consignacao_id'] ?? '');

    if ($user_id <= 0 || $consignacao_id === '') {
        return null;
    }

    $post = tailpress_storefront_find_for_consignacao($user_id, $consignacao_id);
    $token = $post instanceof WP_Post ? $post->post_name : tailpress_storefront_generate_token();
    $snapshot = tailpress_storefront_build_snapshot($user_id, $data, $consignacao);
    $post_data = [
        'post_type' => tailpress_storefront_post_type(),
        'post_status' => 'publish',
        'post_name' => $token,
        'post_title' => sprintf(__('Vitrine %1$s - Sacola %2$s', 'tailpress'), $snapshot['consultora_nome'], $consignacao_id),
    ];

    if ($post instanceof WP_Post) {
        $post_data['ID'] = $post->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id) || (int) $post_id <= 0) {
        return null;
    }

    update_post_meta((int) $post_id, tailpress_storefront_meta_key('consultora_user_id'), (string) $user_id);
    update_post_meta((int) $post_id, tailpress_storefront_meta_key('consignacao_id'), $consignacao_id);
    update_post_meta((int) $post_id, tailpress_storefront_meta_key('snapshot'), $snapshot);
    update_post_meta((int) $post_id, tailpress_storefront_meta_key('updated_at'), $snapshot['updated_at']);

    return [
        'consignacao_id' => $consignacao_id,
        'url' => tailpress_storefront_public_url($token),
        'updated_at' => $snapshot['updated_at'],
        'produtos' => (int) ($snapshot['totais']['produtos'] ?? 0),
        'pecas' => (int) ($snapshot['totais']['pecas'] ?? 0),
    ];
}

function tailpress_storefront_sync_user_snapshots(int $user_id, array $data): array
{
    if ($user_id <= 0 || empty($data['ok'])) {
        return [];
    }

    $storefronts = [];

    foreach ((array) ($data['consignacoes'] ?? []) as $consignacao) {
        if (!is_array($consignacao)) {
            continue;
        }

        $storefront = tailpress_storefront_upsert_snapshot($user_id, $data, $consignacao);

        if ($storefront) {
            $storefronts[] = $storefront;
        }
    }

    return $storefronts;
}

function tailpress_storefront_sync_current_user_snapshots(array $data): array
{
    return tailpress_storefront_sync_user_snapshots(get_current_user_id(), $data);
}

function tailpress_storefront_get_post_snapshot(WP_Post $post): array
{
    $snapshot = get_post_meta($post->ID, tailpress_storefront_meta_key('snapshot'), true);

    if (!is_array($snapshot)) {
        return [];
    }

    if (isset($snapshot['produtos']) && is_array($snapshot['produtos'])) {
        foreach ($snapshot['produtos'] as $index => $product) {
            if (!is_array($product)) {
                continue;
            }

            $raw_name = (string) ($product['nome'] ?? '');

            if (empty($product['codigo']) && function_exists('tailpress_consignacao_extract_description_code')) {
                $snapshot['produtos'][$index]['codigo'] = tailpress_consignacao_extract_description_code($raw_name);
            }

            if (function_exists('tailpress_consignacao_normalize_description')) {
                $snapshot['produtos'][$index]['nome'] = tailpress_consignacao_normalize_description($raw_name);
            }
        }
    }

    return $snapshot;
}

function tailpress_storefront_template_redirect(): void
{
    $token = tailpress_storefront_token_from_request();

    if ($token === '') {
        return;
    }

    $post = tailpress_storefront_find_by_token($token);

    if (!$post) {
        status_header(404);
        $GLOBALS['tailpress_storefront_snapshot'] = null;
    } else {
        status_header(200);
        $GLOBALS['tailpress_storefront_snapshot'] = tailpress_storefront_get_post_snapshot($post);
        $GLOBALS['tailpress_storefront_url'] = tailpress_storefront_public_url($token);
    }

    include get_theme_file_path('template-consultora-storefront.php');
    exit;
}
add_action('template_redirect', 'tailpress_storefront_template_redirect', 1);
