<?php
/**
 * Lucia Ramos consignacao API integration.
 *
 * @package tailpress
 */

function tailpress_consignacao_api_base_url(): string
{
    return defined('LUCIA_CONSIGNACAO_API_BASE_URL')
        ? untrailingslashit((string) LUCIA_CONSIGNACAO_API_BASE_URL)
        : '';
}

function tailpress_consignacao_api_key(): string
{
    return defined('LUCIA_CONSIGNACAO_API_KEY') ? trim((string) LUCIA_CONSIGNACAO_API_KEY) : '';
}

function tailpress_consignacao_api_is_configured(): bool
{
    return tailpress_consignacao_api_base_url() !== '' && tailpress_consignacao_api_key() !== '';
}

function tailpress_consignacao_error(string $code, string $message, int $status = 400): WP_Error
{
    return new WP_Error($code, $message, ['status' => $status]);
}

function tailpress_consignacao_default_totals(): array
{
    return [
        'total_itens' => 0,
        'total_prevenda' => 0,
        'valor_estoque' => 0.0,
        'valor_prevenda' => 0.0,
        'valor_sugerido' => 0.0,
    ];
}

function tailpress_consignacao_error_payload(WP_Error $error): array
{
    return [
        'ok' => false,
        'error_code' => $error->get_error_code(),
        'message' => $error->get_error_message(),
        'revendedora' => ['cpf' => '', 'nome' => ''],
        'consignacoes' => [],
        'itens' => [],
        'itens_ativos' => [],
        'totais' => tailpress_consignacao_default_totals(),
    ];
}

function tailpress_consignacao_current_user_cpf(): string
{
    $cpf = get_user_meta(get_current_user_id(), tailpress_consultora_cpf_meta_key(), true);

    return tailpress_normalize_cpf($cpf);
}

function tailpress_consignacao_request(string $method, string $path, ?array $body = null)
{
    $method = strtoupper($method);

    if (!tailpress_consignacao_api_is_configured()) {
        return tailpress_consignacao_error(
            'lucia_api_not_configured',
            __('API de consignação não configurada. Defina LUCIA_CONSIGNACAO_API_BASE_URL e LUCIA_CONSIGNACAO_API_KEY no wp-config.php.', 'tailpress'),
            500
        );
    }

    $args = [
        'method' => $method,
        'timeout' => 12,
        'headers' => [
            'Accept' => 'application/json',
            'x-api-key' => tailpress_consignacao_api_key(),
        ],
    ];

    if ($body !== null) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request(tailpress_consignacao_api_base_url() . '/' . ltrim($path, '/'), $args);

    if (is_wp_error($response)) {
        return tailpress_consignacao_error(
            'lucia_api_request_failed',
            sprintf(__('Não foi possível conectar à API de consignação: %s', 'tailpress'), $response->get_error_message()),
            502
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $response_body = (string) wp_remote_retrieve_body($response);

    if ($status_code < 200 || $status_code >= 300) {
        return tailpress_consignacao_error(
            'lucia_api_http_error',
            sprintf(__('A API de consignação retornou erro HTTP %d.', 'tailpress'), $status_code),
            $status_code >= 400 ? $status_code : 502
        );
    }

    if ($method === 'PUT') {
        $decoded = $response_body !== '' ? json_decode($response_body, true) : null;

        return [
            'success' => true,
            'response_type' => is_array($decoded) && json_last_error() === JSON_ERROR_NONE ? 'json' : ($response_body === '' ? 'empty' : 'text'),
        ];
    }

    $decoded = $response_body !== '' ? json_decode($response_body, true) : null;

    if ($response_body === '' || json_last_error() !== JSON_ERROR_NONE) {
        return tailpress_consignacao_error(
            'lucia_api_invalid_json',
            __('A API de consignação retornou uma resposta inválida.', 'tailpress'),
            502
        );
    }

    return is_array($decoded) ? $decoded : ['success' => true];
}

function tailpress_consignacao_normalize_date($date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }

    return (string) $date;
}

function tailpress_consignacao_normalize_money($value): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }

    $normalized = preg_replace('/[^\d,.-]/', '', (string) $value);

    if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
    } elseif (str_contains($normalized, ',')) {
        $normalized = str_replace(',', '.', $normalized);
    }

    return (float) $normalized;
}

function tailpress_consignacao_normalize_cons_item_id($cons_item_id): string
{
    return preg_replace('/\D+/', '', (string) $cons_item_id) ?: '';
}

function tailpress_consignacao_normalize_pre_venda($pre_venda): string
{
    return strtoupper((string) $pre_venda) === 'S' ? 'S' : 'N';
}

function tailpress_consignacao_normalize_codigo($codigo): string
{
    return trim(sanitize_text_field((string) $codigo));
}

function tailpress_consignacao_extract_description_code($description): string
{
    $description = (string) $description;

    return preg_match('/(?<!\d)(\d{4}-\d{3,4})(?!\d)/u', $description, $matches)
        ? (string) $matches[1]
        : '';
}

function tailpress_consignacao_normalize_description($description): string
{
    $description = sanitize_text_field((string) $description);
    $description = preg_replace('/(?<!\d)\d{4}-\d{3,4}(?!\d)/u', ' ', $description);
    $description = preg_replace('/[\(\[]\s*[\)\]]/u', ' ', (string) $description);
    $description = preg_replace('/\s{2,}/u', ' ', (string) $description);
    $description = preg_replace('/^[\s–—|·:\-]+|[\s–—|·:\-]+$/u', '', (string) $description);

    return trim((string) $description);
}

function tailpress_consignacao_normalize_media_url($url): string
{
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    $normalized_url = esc_url_raw($url, ['http', 'https']);

    return is_string($normalized_url) ? $normalized_url : '';
}

function tailpress_consignacao_item_image_url(array $item): string
{
    foreach (['imagem_url', 'image_url', 'foto_url', 'url_imagem', 'imagem', 'foto'] as $image_key) {
        if (!empty($item[$image_key])) {
            return tailpress_consignacao_normalize_media_url($item[$image_key]);
        }
    }

    return '';
}

function tailpress_consignacao_suggested_goal_value(array $consignacoes): float
{
    $suggested_goals = array_values(array_filter($consignacoes, static fn($consignacao) => (float) ($consignacao['valor_sugerido'] ?? 0) > 0));

    usort($suggested_goals, static function ($a, $b) {
        $a_is_open = empty($a['data_acerto']) ? 1 : 0;
        $b_is_open = empty($b['data_acerto']) ? 1 : 0;

        if ($a_is_open !== $b_is_open) {
            return $b_is_open <=> $a_is_open;
        }

        $a_date = strtotime((string) ($a['data_abertura'] ?? '')) ?: 0;
        $b_date = strtotime((string) ($b['data_abertura'] ?? '')) ?: 0;

        if ($a_date !== $b_date) {
            return $b_date <=> $a_date;
        }

        return (float) ($b['valor_sugerido'] ?? 0) <=> (float) ($a['valor_sugerido'] ?? 0);
    });

    return !empty($suggested_goals) ? (float) ($suggested_goals[0]['valor_sugerido'] ?? 0) : 0.0;
}

function tailpress_consignacao_calculate_totals(array $active_items, array $consignacoes = []): array
{
    $pre_venda_items = array_values(array_filter($active_items, static fn($item) => ($item['pre_venda'] ?? 'N') === 'S'));

    return [
        'total_itens' => count($active_items),
        'total_prevenda' => count($pre_venda_items),
        'valor_estoque' => array_reduce($active_items, static fn($sum, $item) => $sum + (float) ($item['valor_unitario'] ?? 0), 0.0),
        'valor_prevenda' => array_reduce($pre_venda_items, static fn($sum, $item) => $sum + (float) ($item['valor_unitario'] ?? 0), 0.0),
        'valor_sugerido' => tailpress_consignacao_suggested_goal_value($consignacoes),
    ];
}

function tailpress_consignacao_find_active_item(array $data, string $cons_item_id): ?array
{
    foreach ((array) ($data['itens_ativos'] ?? []) as $item) {
        if (is_array($item) && ($item['cons_item_id'] ?? '') === $cons_item_id) {
            return $item;
        }
    }

    return null;
}

function tailpress_consignacao_normalize_item(array $item, array $consignacao): ?array
{
    $cons_item_id = tailpress_consignacao_normalize_cons_item_id($item['cons_item_id'] ?? '');

    if ($cons_item_id === '') {
        return null;
    }

    $raw_description = (string) ($item['descricao'] ?? '');
    $codigo = tailpress_consignacao_normalize_codigo($item['codigo'] ?? '');

    if ($codigo === '') {
        $codigo = tailpress_consignacao_extract_description_code($raw_description);
    }

    return [
        'consignacao_id' => (string) ($consignacao['consignacao_id'] ?? ''),
        'data_abertura' => tailpress_consignacao_normalize_date($consignacao['data_abertura'] ?? null),
        'data_acerto' => tailpress_consignacao_normalize_date($consignacao['data_acerto'] ?? null),
        'cons_item_id' => $cons_item_id,
        'produto_id' => (string) ($item['produto_id'] ?? ''),
        'descricao' => tailpress_consignacao_normalize_description($raw_description),
        'codigo' => $codigo,
        'unidade' => (string) ($item['unidade'] ?? ''),
        'tamanho' => (string) ($item['tamanho'] ?? ''),
        'valor_unitario' => tailpress_consignacao_normalize_money($item['valor_unitario'] ?? 0),
        'imagem_url' => tailpress_consignacao_item_image_url($item),
        'data_devolvido' => tailpress_consignacao_normalize_date($item['data_Devolvido'] ?? $item['data_devolvido'] ?? null),
        'pre_venda' => tailpress_consignacao_normalize_pre_venda($item['pre_venda'] ?? 'N'),
        'data_prevenda' => tailpress_consignacao_normalize_date($item['data_prevenda'] ?? null),
    ];
}

function tailpress_consignacao_normalize_response(array $response): array
{
    $revendedora = is_array($response['revendedora'] ?? null) ? $response['revendedora'] : [];
    $consignacoes = [];
    $all_items = [];

    foreach ((array) ($response['consignacoes'] ?? []) as $consignacao) {
        if (!is_array($consignacao)) {
            continue;
        }

        $items = [];

        foreach ((array) ($consignacao['itens'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized_item = tailpress_consignacao_normalize_item($item, $consignacao);

            if (!$normalized_item) {
                continue;
            }

            $items[] = $normalized_item;
            $all_items[] = $normalized_item;
        }

        $consignacoes[] = [
            'consignacao_id' => (string) ($consignacao['consignacao_id'] ?? ''),
            'data_abertura' => tailpress_consignacao_normalize_date($consignacao['data_abertura'] ?? null),
            'data_acerto' => tailpress_consignacao_normalize_date($consignacao['data_acerto'] ?? null),
            'valor_sugerido' => tailpress_consignacao_normalize_money($consignacao['valor_sugerido'] ?? 0),
            'valor_venda' => tailpress_consignacao_normalize_money($consignacao['valor_venda'] ?? 0),
            'valor_prevenda' => tailpress_consignacao_normalize_money($consignacao['valor_prevenda'] ?? 0),
            'itens' => $items,
        ];
    }

    $active_items = array_values(array_filter($all_items, static fn($item) => empty($item['data_devolvido'])));

    return [
        'ok' => true,
        'revendedora' => [
            'cpf' => (string) ($revendedora['cpf'] ?? ''),
            'nome' => (string) ($revendedora['nome'] ?? ''),
        ],
        'consignacoes' => $consignacoes,
        'itens' => $all_items,
        'itens_ativos' => $active_items,
        'totais' => tailpress_consignacao_calculate_totals($active_items, $consignacoes),
    ];
}

function tailpress_consignacao_fetch_by_cpf(string $cpf)
{
    $cpf = tailpress_normalize_cpf($cpf);

    if ($cpf === '') {
        return tailpress_consignacao_error(
            'lucia_missing_cpf',
            __('Esta consultora não possui CPF cadastrado. Peça para o administrador preencher o CPF no usuário.', 'tailpress'),
            400
        );
    }

    $response = tailpress_consignacao_request('GET', rawurlencode($cpf));

    if (is_wp_error($response)) {
        return $response;
    }

    if (!isset($response['consignacoes']) || !is_array($response['consignacoes'])) {
        return tailpress_consignacao_error(
            'lucia_invalid_consignacao_payload',
            __('A API de consignação não retornou uma lista válida de consignações.', 'tailpress'),
            502
        );
    }

    return tailpress_consignacao_normalize_response($response);
}

function tailpress_consignacao_get_current_user_data(): array
{
    $cpf = tailpress_consignacao_current_user_cpf();
    $response = tailpress_consignacao_fetch_by_cpf($cpf);

    if (is_wp_error($response)) {
        return tailpress_consignacao_error_payload($response);
    }

    return $response;
}

function tailpress_consignacao_group_stock_items(array $items): array
{
    $groups = [];

    foreach ($items as $item) {
        if (!empty($item['data_devolvido'])) {
            continue;
        }

        $key = implode('|', [
            $item['descricao'] ?? '',
            $item['codigo'] ?? '',
            $item['tamanho'] ?? '',
            (string) ($item['valor_unitario'] ?? 0),
            $item['unidade'] ?? '',
        ]);

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'id' => md5($key),
                'nome' => (string) ($item['descricao'] ?? ''),
                'codigo' => (string) ($item['codigo'] ?? ''),
                'unidade' => (string) ($item['unidade'] ?? ''),
                'tamanho' => (string) ($item['tamanho'] ?? ''),
                'valor_unitario' => (float) ($item['valor_unitario'] ?? 0),
                'imagem_url' => (string) ($item['imagem_url'] ?? ''),
                'quantidade' => 0,
                'prevenda_quantidade' => 0,
                'disponivel_quantidade' => 0,
                'consignacoes' => [],
            ];
        }

        if ($groups[$key]['imagem_url'] === '' && !empty($item['imagem_url'])) {
            $groups[$key]['imagem_url'] = (string) $item['imagem_url'];
        }

        if ($groups[$key]['codigo'] === '' && !empty($item['codigo'])) {
            $groups[$key]['codigo'] = (string) $item['codigo'];
        }

        $groups[$key]['quantidade']++;
        $groups[$key]['consignacoes'][(string) ($item['consignacao_id'] ?? '')] = true;

        if (($item['pre_venda'] ?? 'N') === 'S') {
            $groups[$key]['prevenda_quantidade']++;
        }
    }

    return array_values(array_map(static function ($group) {
        $group['disponivel_quantidade'] = max(0, $group['quantidade'] - $group['prevenda_quantidade']);
        $group['consignacoes'] = array_values(array_filter(array_keys($group['consignacoes'])));

        return $group;
    }, $groups));
}

function tailpress_consignacao_frontend_payload(array $data): array
{
    $products = $data['ok'] ? tailpress_consignacao_group_stock_items($data['itens_ativos']) : [];
    $units = array_values(array_unique(array_filter(array_map(static fn($product) => (string) ($product['unidade'] ?? ''), $products))));
    sort($units);
    $storefronts = $data['ok'] && function_exists('tailpress_storefront_sync_current_user_snapshots')
        ? tailpress_storefront_sync_current_user_snapshots($data)
        : [];

    return [
        'ok' => (bool) $data['ok'],
        'message' => (string) ($data['message'] ?? ''),
        'revendedora' => $data['revendedora'] ?? ['cpf' => '', 'nome' => ''],
        'consignacoes' => $data['consignacoes'] ?? [],
        'itens' => $data['ok'] ? array_values($data['itens_ativos']) : [],
        'produtos' => $products,
        'unidades' => array_merge(['todos'], $units),
        'vitrines' => $storefronts,
        'totais' => array_merge(tailpress_consignacao_default_totals(), is_array($data['totais'] ?? null) ? $data['totais'] : []),
    ];
}

function tailpress_consignacao_update_pre_venda(string $cons_item_id, string $pre_venda)
{
    $cons_item_id = tailpress_consignacao_normalize_cons_item_id($cons_item_id);
    $pre_venda = tailpress_consignacao_normalize_pre_venda($pre_venda);

    return tailpress_consignacao_request('PUT', 'item/prevendaitem', [
        'cons_item_id' => $cons_item_id,
        'pre_venda' => $pre_venda,
    ]);
}

function tailpress_consignacao_apply_pre_venda_to_data(array $data, string $cons_item_id, string $pre_venda): array
{
    $update_item = static function ($item) use ($cons_item_id, $pre_venda) {
        if (is_array($item) && ($item['cons_item_id'] ?? '') === $cons_item_id) {
            $item['pre_venda'] = $pre_venda;
            $item['data_prevenda'] = $pre_venda === 'S' ? current_time('Y-m-d') : null;
        }

        return $item;
    };

    foreach (['itens', 'itens_ativos'] as $list_key) {
        if (!isset($data[$list_key]) || !is_array($data[$list_key])) {
            continue;
        }

        $data[$list_key] = array_map($update_item, $data[$list_key]);
    }

    if (isset($data['consignacoes']) && is_array($data['consignacoes'])) {
        foreach ($data['consignacoes'] as $consignacao_index => $consignacao) {
            if (!is_array($consignacao) || !isset($consignacao['itens']) || !is_array($consignacao['itens'])) {
                continue;
            }

            $data['consignacoes'][$consignacao_index]['itens'] = array_map($update_item, $consignacao['itens']);
        }
    }

    $active_items = is_array($data['itens_ativos'] ?? null) ? $data['itens_ativos'] : [];
    $consignacoes = is_array($data['consignacoes'] ?? null) ? $data['consignacoes'] : [];
    $data['totais'] = tailpress_consignacao_calculate_totals($active_items, $consignacoes);

    return $data;
}

function tailpress_consignacao_rest_permission()
{
    if (!is_user_logged_in()) {
        return tailpress_consignacao_error('lucia_rest_not_logged_in', __('Faça login para continuar.', 'tailpress'), 401);
    }

    if (!tailpress_user_is_consultora()) {
        return tailpress_consignacao_error('lucia_rest_forbidden', __('Apenas consultoras podem alterar pré-vendas.', 'tailpress'), 403);
    }

    if (tailpress_consignacao_current_user_cpf() === '') {
        return tailpress_consignacao_error('lucia_rest_missing_cpf', __('CPF da consultora não cadastrado.', 'tailpress'), 400);
    }

    return true;
}

function tailpress_consignacao_rest_update_pre_venda(WP_REST_Request $request)
{
    $cons_item_id = tailpress_consignacao_normalize_cons_item_id($request->get_param('cons_item_id'));
    $requested_pre_venda = strtoupper((string) $request->get_param('pre_venda'));

    if ($cons_item_id === '') {
        return tailpress_consignacao_error('lucia_invalid_cons_item_id', __('Item de consignação inválido.', 'tailpress'), 400);
    }

    if (!in_array($requested_pre_venda, ['S', 'N'], true)) {
        return tailpress_consignacao_error('lucia_invalid_pre_venda', __('Valor de pré-venda inválido.', 'tailpress'), 400);
    }

    $pre_venda = tailpress_consignacao_normalize_pre_venda($requested_pre_venda);

    $data = tailpress_consignacao_get_current_user_data();

    if (!$data['ok']) {
        return tailpress_consignacao_error($data['error_code'], $data['message'], 400);
    }

    $selected_item = tailpress_consignacao_find_active_item($data, $cons_item_id);

    if (!$selected_item) {
        return tailpress_consignacao_error(
            'lucia_item_not_owned',
            __('Este item não pertence à consultora logada ou já foi devolvido.', 'tailpress'),
            403
        );
    }

    $response = tailpress_consignacao_update_pre_venda($cons_item_id, $pre_venda);

    if (is_wp_error($response)) {
        return $response;
    }

    if (function_exists('tailpress_storefront_sync_current_user_snapshots')) {
        tailpress_storefront_sync_current_user_snapshots(
            tailpress_consignacao_apply_pre_venda_to_data($data, $cons_item_id, $pre_venda)
        );
    }

    return rest_ensure_response([
        'success' => true,
        'cons_item_id' => $cons_item_id,
        'pre_venda' => $pre_venda,
    ]);
}

function tailpress_consignacao_register_rest_routes(): void
{
    register_rest_route('lucia-portal/v1', '/consignacao', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => static function () {
            return rest_ensure_response(tailpress_consignacao_frontend_payload(tailpress_consignacao_get_current_user_data()));
        },
        'permission_callback' => 'tailpress_consignacao_rest_permission',
    ]);

    register_rest_route('lucia-portal/v1', '/pre-venda', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'tailpress_consignacao_rest_update_pre_venda',
        'permission_callback' => 'tailpress_consignacao_rest_permission',
        'args' => [
            'cons_item_id' => [
                'required' => true,
                'type' => 'string',
            ],
            'pre_venda' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['S', 'N'],
            ],
        ],
    ]);
}
add_action('rest_api_init', 'tailpress_consignacao_register_rest_routes');
