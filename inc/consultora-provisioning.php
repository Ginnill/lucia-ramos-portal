<?php
/**
 * ERP consultora provisioning REST integration.
 *
 * @package tailpress
 */

function tailpress_erp_integration_api_key(): string
{
    return defined('LUCIA_ERP_INTEGRATION_API_KEY') ? trim((string) LUCIA_ERP_INTEGRATION_API_KEY) : '';
}

function tailpress_consultora_provisioning_error(string $code, string $message, int $status): WP_Error
{
    return new WP_Error($code, $message, ['status' => $status]);
}

function tailpress_consultora_provisioning_response(array $payload, int $status = 200): WP_REST_Response
{
    $response = rest_ensure_response($payload);
    $response->set_status($status);

    return $response;
}

function tailpress_consultora_provisioning_permission()
{
    $expected_key = tailpress_erp_integration_api_key();

    if ($expected_key === '') {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_not_configured',
            __('Integração ERP não configurada. Defina LUCIA_ERP_INTEGRATION_API_KEY no wp-config.php.', 'tailpress'),
            500
        );
    }

    $provided_key = isset($_SERVER['HTTP_X_LUCIA_INTEGRATION_KEY'])
        ? trim((string) wp_unslash($_SERVER['HTTP_X_LUCIA_INTEGRATION_KEY']))
        : '';

    if ($provided_key === '') {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_missing_key',
            __('Cabeçalho X-Lucia-Integration-Key ausente.', 'tailpress'),
            401
        );
    }

    if (!hash_equals($expected_key, $provided_key)) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_key',
            __('Chave de integração inválida.', 'tailpress'),
            403
        );
    }

    return true;
}

function tailpress_consultora_provisioning_find_user_by_cpf(string $cpf)
{
    if ($cpf === '') {
        return null;
    }

    $user_ids = get_users([
        'fields' => 'ids',
        'number' => 2,
        'meta_key' => tailpress_consultora_cpf_meta_key(),
        'meta_value' => $cpf,
    ]);

    if (empty($user_ids)) {
        return null;
    }

    if (count($user_ids) > 1) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_duplicate_cpf',
            __('O CPF informado está duplicado em mais de um usuário.', 'tailpress'),
            409
        );
    }

    $user = get_user_by('id', (int) $user_ids[0]);

    return $user instanceof WP_User ? $user : null;
}

function tailpress_consultora_provisioning_normalize_state($estado): string
{
    return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $estado) ?: '', 0, 2));
}

function tailpress_consultora_provisioning_normalize_cep($cep): string
{
    $digits = preg_replace('/\D+/', '', (string) $cep) ?: '';

    if (strlen($digits) !== 8) {
        return '';
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5, 3);
}

function tailpress_consultora_provisioning_normalize_name($nome): string
{
    return trim(wp_strip_all_tags((string) $nome));
}

function tailpress_consultora_provisioning_parse_payload(WP_REST_Request $request)
{
    $nome = tailpress_consultora_provisioning_normalize_name($request->get_param('nome'));
    $email = sanitize_email((string) $request->get_param('email'));
    $cpf = tailpress_normalize_cpf($request->get_param('cpf'));
    $whatsapp = tailpress_normalize_phone($request->get_param('whatsapp'));
    $endereco = sanitize_text_field((string) $request->get_param('endereco'));
    $cidade = sanitize_text_field((string) $request->get_param('cidade'));
    $estado = tailpress_consultora_provisioning_normalize_state($request->get_param('estado'));
    $cep_input = (string) $request->get_param('cep');
    $cep = $cep_input === '' ? '' : tailpress_consultora_provisioning_normalize_cep($cep_input);

    if ($nome === '') {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_nome',
            __('Informe o nome da consultora.', 'tailpress'),
            422
        );
    }

    if ($email === '' || !is_email($email)) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_email',
            __('Informe um e-mail válido.', 'tailpress'),
            422
        );
    }

    if (strlen($cpf) !== 11) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_cpf',
            __('O CPF da consultora deve conter 11 dígitos.', 'tailpress'),
            422
        );
    }

    if ($whatsapp !== '' && (strlen($whatsapp) < 10 || strlen($whatsapp) > 11)) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_whatsapp',
            __('O WhatsApp da consultora deve conter DDD e 8 ou 9 dígitos.', 'tailpress'),
            422
        );
    }

    if ((string) $request->get_param('estado') !== '' && strlen($estado) !== 2) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_estado',
            __('O estado da consultora deve conter 2 letras.', 'tailpress'),
            422
        );
    }

    if ($cep_input !== '' && $cep === '') {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_invalid_cep',
            __('O CEP da consultora deve conter 8 dígitos.', 'tailpress'),
            422
        );
    }

    return [
        'nome' => $nome,
        'email' => $email,
        'cpf' => $cpf,
        'whatsapp' => $whatsapp,
        'endereco' => $endereco,
        'cidade' => $cidade,
        'estado' => $estado,
        'cep' => $cep,
    ];
}

function tailpress_consultora_provisioning_generate_login(string $email): string
{
    $email_parts = explode('@', $email);
    $base = sanitize_user($email_parts[0] ?? '', true);
    $base = $base !== '' ? $base : 'consultora';
    $candidate = $base;
    $suffix = 1;

    while (username_exists($candidate)) {
        $suffix++;
        $candidate = $base . $suffix;
    }

    return $candidate;
}

function tailpress_consultora_provisioning_assign_profile_data(int $user_id, array $payload): void
{
    $result = wp_update_user([
        'ID' => $user_id,
        'display_name' => $payload['nome'],
    ]);

    if (is_wp_error($result)) {
        return;
    }

    update_user_meta($user_id, 'nickname', $payload['nome']);

    update_user_meta($user_id, tailpress_consultora_cpf_meta_key(), $payload['cpf']);

    if ($payload['whatsapp'] === '') {
        delete_user_meta($user_id, tailpress_consultora_whatsapp_meta_key());
    } else {
        update_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), $payload['whatsapp']);
    }

    $address_meta_keys = function_exists('tailpress_profile_address_meta_keys')
        ? tailpress_profile_address_meta_keys()
        : [
            'endereco' => 'lucia_profile_endereco',
            'cidade' => 'lucia_profile_cidade',
            'estado' => 'lucia_profile_estado',
            'cep' => 'lucia_profile_cep',
        ];
    $address_updates = [
        $address_meta_keys['endereco'] => $payload['endereco'],
        $address_meta_keys['cidade'] => $payload['cidade'],
        $address_meta_keys['estado'] => $payload['estado'],
        $address_meta_keys['cep'] => $payload['cep'],
    ];

    foreach ($address_updates as $meta_key => $value) {
        if ($value === '') {
            delete_user_meta($user_id, $meta_key);
        } else {
            update_user_meta($user_id, $meta_key, $value);
        }
    }

    $user = get_user_by('id', $user_id);
    if ($user instanceof WP_User && !in_array('consultora', (array) $user->roles, true)) {
        $user->add_role('consultora');
    }
}

function tailpress_consultora_provisioning_conflict_payload(array $payload, string $message): array
{
    return [
        'success' => false,
        'action' => 'conflict',
        'user_id' => null,
        'user_login' => '',
        'email' => $payload['email'],
        'cpf' => $payload['cpf'],
        'message' => $message,
    ];
}

function tailpress_consultora_provisioning_notify_sales_conflict(array $payload, string $reason): void
{
    wp_mail(
        'vendas@luciaramos.com.br',
        __('Falha na criação de consultora via ERP', 'tailpress'),
        sprintf(
            "Nao foi possivel provisionar a consultora no portal.\n\nMotivo: %s\nNome: %s\nE-mail: %s\nCPF: %s\nWhatsApp: %s\nEndereco: %s\nCidade: %s\nEstado: %s\nCEP: %s\n",
            $reason,
            $payload['nome'],
            $payload['email'],
            $payload['cpf'],
            $payload['whatsapp'],
            $payload['endereco'],
            $payload['cidade'],
            $payload['estado'],
            $payload['cep']
        )
    );
}

function tailpress_consultora_provisioning_send_access_email(WP_User $user)
{
    $reset_key = get_password_reset_key($user);

    if (is_wp_error($reset_key)) {
        return $reset_key;
    }

    $reset_url = network_site_url(
        'wp-login.php?action=rp&key=' . rawurlencode($reset_key) . '&login=' . rawurlencode($user->user_login),
        'login'
    );

    $sent = wp_mail(
        $user->user_email,
        __('Seu acesso ao Portal Lúcia Ramos', 'tailpress'),
        sprintf(
            "Ola, %s!\n\nSua conta no Portal Lucia Ramos foi criada com sucesso.\n\nE-mail de acesso: %s\nUsuario: %s\n\nAntes do primeiro login, defina sua senha neste link:\n%s\n\nDepois disso, acesse o portal em:\n%s\n",
            $user->display_name ?: $user->user_login,
            $user->user_email,
            $user->user_login,
            $reset_url,
            home_url('/entrar/')
        )
    );

    if (!$sent) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_email_not_sent',
            __('Não foi possível enviar o e-mail de acesso da consultora.', 'tailpress'),
            500
        );
    }

    return true;
}

function tailpress_consultora_provisioning_handle_conflict(array $payload, string $message): WP_REST_Response
{
    tailpress_consultora_provisioning_notify_sales_conflict($payload, $message);

    return tailpress_consultora_provisioning_response(
        tailpress_consultora_provisioning_conflict_payload($payload, $message),
        409
    );
}

function tailpress_consultora_provisioning_create_consultora(array $payload)
{
    $user_login = tailpress_consultora_provisioning_generate_login($payload['email']);
    $user_id = wp_insert_user([
        'user_login' => $user_login,
        'user_pass' => wp_generate_password(24, true, true),
        'user_email' => $payload['email'],
        'display_name' => $payload['nome'],
        'role' => 'consultora',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    tailpress_consultora_provisioning_assign_profile_data((int) $user_id, $payload);

    $user = get_user_by('id', (int) $user_id);
    if (!($user instanceof WP_User)) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_user_not_found',
            __('Não foi possível carregar a consultora criada.', 'tailpress'),
            500
        );
    }

    $email_result = tailpress_consultora_provisioning_send_access_email($user);
    if (is_wp_error($email_result)) {
        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        wp_delete_user((int) $user_id);

        return $email_result;
    }

    return tailpress_consultora_provisioning_response([
        'success' => true,
        'action' => 'created',
        'user_id' => (int) $user->ID,
        'user_login' => $user->user_login,
        'email' => $user->user_email,
        'cpf' => $payload['cpf'],
        'message' => __('Consultora criada com sucesso.', 'tailpress'),
    ], 201);
}

function tailpress_consultora_provisioning_sync_consultora(WP_User $user_by_cpf, array $payload)
{
    $existing_email_user = get_user_by('email', $payload['email']);

    if ($existing_email_user instanceof WP_User && (int) $existing_email_user->ID !== (int) $user_by_cpf->ID) {
        return tailpress_consultora_provisioning_handle_conflict(
            $payload,
            __('O e-mail informado já pertence a outro usuário do sistema.', 'tailpress')
        );
    }

    $response = [
        'success' => true,
        'action' => 'updated',
        'user_id' => (int) $user_by_cpf->ID,
        'user_login' => $user_by_cpf->user_login,
        'email' => $user_by_cpf->user_email,
        'cpf' => $payload['cpf'],
        'message' => __('Consultora sincronizada com sucesso.', 'tailpress'),
    ];

    if (strtolower($payload['email']) !== strtolower($user_by_cpf->user_email)) {
        $email_change = tailpress_profile_start_email_change((int) $user_by_cpf->ID, $payload['email']);

        if (is_wp_error($email_change)) {
            return $email_change;
        }

        $response['action'] = 'email_change_pending';
        $response['pending_email'] = $email_change['pendingEmail'];
        $response['message'] = $email_change['already_pending']
            ? __('Dados sincronizados e a confirmação do novo e-mail já está pendente.', 'tailpress')
            : __('Dados sincronizados e enviamos a confirmação do novo e-mail.', 'tailpress');
    }

    tailpress_consultora_provisioning_assign_profile_data((int) $user_by_cpf->ID, $payload);

    return tailpress_consultora_provisioning_response($response);
}

function tailpress_consultora_provisioning_rest_create_or_sync(WP_REST_Request $request)
{
    if (!get_role('consultora')) {
        return tailpress_consultora_provisioning_error(
            'lucia_integration_missing_role',
            __('A role consultora não está cadastrada no WordPress.', 'tailpress'),
            500
        );
    }

    $payload = tailpress_consultora_provisioning_parse_payload($request);
    if (is_wp_error($payload)) {
        return $payload;
    }

    $user_by_cpf = tailpress_consultora_provisioning_find_user_by_cpf($payload['cpf']);
    if (is_wp_error($user_by_cpf)) {
        return tailpress_consultora_provisioning_handle_conflict($payload, $user_by_cpf->get_error_message());
    }

    $user_by_email = get_user_by('email', $payload['email']);

    if ($user_by_cpf instanceof WP_User && !tailpress_user_is_consultora($user_by_cpf)) {
        return tailpress_consultora_provisioning_handle_conflict(
            $payload,
            __('O CPF informado já pertence a um usuário que não é consultora.', 'tailpress')
        );
    }

    if ($user_by_email instanceof WP_User && !tailpress_user_is_consultora($user_by_email)) {
        return tailpress_consultora_provisioning_handle_conflict(
            $payload,
            __('O e-mail informado já pertence a um usuário que não é consultora.', 'tailpress')
        );
    }

    if ($user_by_cpf instanceof WP_User) {
        return tailpress_consultora_provisioning_sync_consultora($user_by_cpf, $payload);
    }

    if ($user_by_email instanceof WP_User) {
        return tailpress_consultora_provisioning_handle_conflict(
            $payload,
            __('O e-mail informado já está vinculado a outra consultora.', 'tailpress')
        );
    }

    return tailpress_consultora_provisioning_create_consultora($payload);
}

function tailpress_consultora_provisioning_register_rest_routes(): void
{
    register_rest_route('lucia-integracao/v1', '/consultoras', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'tailpress_consultora_provisioning_rest_create_or_sync',
        'permission_callback' => 'tailpress_consultora_provisioning_permission',
    ]);
}
add_action('rest_api_init', 'tailpress_consultora_provisioning_register_rest_routes');
