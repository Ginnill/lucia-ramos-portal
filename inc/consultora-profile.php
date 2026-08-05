<?php
/**
 * Consultora portal profile data and actions.
 *
 * @package tailpress
 */

function tailpress_profile_address_meta_keys(): array
{
    return [
        'endereco' => 'lucia_profile_endereco',
        'cidade' => 'lucia_profile_cidade',
        'estado' => 'lucia_profile_estado',
        'cep' => 'lucia_profile_cep',
    ];
}

function tailpress_profile_pending_email_meta_key(): string
{
    return 'lucia_profile_pending_email';
}

function tailpress_profile_is_read_only(): bool
{
    return true;
}

function tailpress_profile_build_email_confirmation_url(int $user_id, string $token): string
{
    return add_query_arg([
        'lucia_profile_email_confirm' => '1',
        'user_id' => $user_id,
        'token' => $token,
    ], home_url('/'));
}

function tailpress_profile_start_email_change(int $user_id, string $email)
{
    $email = sanitize_email($email);

    if ($user_id <= 0 || $email === '' || !is_email($email)) {
        return new WP_Error(
            'lucia_profile_invalid_email',
            __('Informe um e-mail válido.', 'tailpress'),
            ['status' => 400]
        );
    }

    $pending = tailpress_profile_get_pending_email($user_id);

    if (!empty($pending) && isset($pending['email']) && strtolower((string) $pending['email']) === strtolower($email)) {
        return [
            'success' => true,
            'already_pending' => true,
            'pendingEmail' => [
                'email' => (string) $pending['email'],
                'createdAt' => isset($pending['created_at']) ? (int) $pending['created_at'] : 0,
            ],
            'message' => __('Já existe uma confirmação pendente para este e-mail.', 'tailpress'),
        ];
    }

    $token = wp_generate_password(32, false, false);
    $pending = [
        'email' => $email,
        'token_hash' => wp_hash_password($token),
        'created_at' => time(),
    ];
    update_user_meta($user_id, tailpress_profile_pending_email_meta_key(), $pending);

    $sent = wp_mail(
        $email,
        __('Confirme seu novo e-mail no Portal Lúcia Ramos', 'tailpress'),
        sprintf(
            /* translators: %s: email confirmation URL. */
            __("Para confirmar seu novo e-mail no Portal Lúcia Ramos, acesse:\n\n%s\n\nEste link expira em 72 horas.", 'tailpress'),
            tailpress_profile_build_email_confirmation_url($user_id, $token)
        )
    );

    if (!$sent) {
        delete_user_meta($user_id, tailpress_profile_pending_email_meta_key());

        return new WP_Error(
            'lucia_profile_email_not_sent',
            __('Não foi possível enviar o e-mail de confirmação. Tente novamente mais tarde.', 'tailpress'),
            ['status' => 500]
        );
    }

    return [
        'success' => true,
        'already_pending' => false,
        'pendingEmail' => [
            'email' => $email,
            'createdAt' => $pending['created_at'],
        ],
        'message' => __('Enviamos um link de confirmação para o novo e-mail.', 'tailpress'),
    ];
}

function tailpress_profile_rest_permission(): bool
{
    if (!is_user_logged_in() || !tailpress_user_is_consultora()) {
        return false;
    }

    $nonce = isset($_SERVER['HTTP_X_WP_NONCE'])
        ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE']))
        : '';

    return wp_verify_nonce($nonce, 'wp_rest') !== false;
}

function tailpress_profile_format_phone(string $phone): string
{
    $digits = tailpress_normalize_phone($phone);

    if (strlen($digits) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $digits) ?: $digits;
    }

    if (strlen($digits) === 10) {
        return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $digits) ?: $digits;
    }

    return $digits;
}

function tailpress_profile_format_cpf(string $cpf): string
{
    $digits = tailpress_normalize_cpf($cpf);

    if (strlen($digits) !== 11) {
        return $digits;
    }

    return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits) ?: $digits;
}

function tailpress_profile_get_pending_email(int $user_id): array
{
    $pending = get_user_meta($user_id, tailpress_profile_pending_email_meta_key(), true);

    if (!is_array($pending)) {
        return [];
    }

    $created_at = isset($pending['created_at']) ? (int) $pending['created_at'] : 0;

    if ($created_at <= 0 || (time() - $created_at) > 72 * HOUR_IN_SECONDS) {
        delete_user_meta($user_id, tailpress_profile_pending_email_meta_key());

        return [];
    }

    $email = isset($pending['email']) ? sanitize_email((string) $pending['email']) : '';

    if ($email === '' || !is_email($email)) {
        delete_user_meta($user_id, tailpress_profile_pending_email_meta_key());

        return [];
    }

    return $pending;
}

function tailpress_profile_frontend_payload(?WP_User $user = null): array
{
    $user = $user instanceof WP_User ? $user : wp_get_current_user();
    $user_id = $user instanceof WP_User ? (int) $user->ID : 0;
    $address_keys = tailpress_profile_address_meta_keys();
    $pending_email = $user_id > 0 ? tailpress_profile_get_pending_email($user_id) : [];

    $address = [];
    foreach ($address_keys as $field => $meta_key) {
        $address[$field] = $user_id > 0 ? (string) get_user_meta($user_id, $meta_key, true) : '';
    }

    return [
        'emailStatus' => isset($_GET['email_status']) ? sanitize_key(wp_unslash($_GET['email_status'])) : '',
        'readOnly' => tailpress_profile_is_read_only(),
        'dadosPerfil' => [
            'nome' => $user instanceof WP_User ? $user->display_name : '',
            'email' => $user instanceof WP_User ? $user->user_email : '',
            'cpf' => $user_id > 0 ? tailpress_profile_format_cpf((string) get_user_meta($user_id, tailpress_consultora_cpf_meta_key(), true)) : '',
            'whatsapp' => $user_id > 0 ? tailpress_profile_format_phone((string) get_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), true)) : '',
            'membroDesde' => $user instanceof WP_User
                ? sprintf(
                    /* translators: %s: formatted user registration month and year. */
                    __('Membro desde %s', 'tailpress'),
                    date_i18n('F Y', strtotime($user->user_registered))
                )
                : '',
            'endereco' => $address['endereco'],
            'cidade' => $address['cidade'],
            'estado' => $address['estado'],
            'cep' => $address['cep'],
        ],
        'emailPendente' => [
            'email' => isset($pending_email['email']) ? (string) $pending_email['email'] : '',
            'createdAt' => isset($pending_email['created_at']) ? (int) $pending_email['created_at'] : 0,
        ],
    ];
}

function tailpress_profile_update_contact(WP_REST_Request $request)
{
    if (tailpress_profile_is_read_only()) {
        return new WP_Error(
            'lucia_profile_read_only',
            __('O perfil é sincronizado externamente e não pode ser alterado por aqui.', 'tailpress'),
            ['status' => 403]
        );
    }

    $user_id = get_current_user_id();
    $whatsapp = tailpress_normalize_phone($request->get_param('whatsapp'));

    if ($whatsapp !== '' && (strlen($whatsapp) < 10 || strlen($whatsapp) > 11)) {
        return new WP_Error(
            'lucia_profile_invalid_whatsapp',
            __('O WhatsApp deve conter DDD e 8 ou 9 dígitos.', 'tailpress'),
            ['status' => 400]
        );
    }

    if ($whatsapp === '') {
        delete_user_meta($user_id, tailpress_consultora_whatsapp_meta_key());
    } else {
        update_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), $whatsapp);
    }

    foreach (tailpress_profile_address_meta_keys() as $field => $meta_key) {
        $value = sanitize_text_field((string) $request->get_param($field));

        if ($field === 'cep') {
            $value = substr(preg_replace('/[^\d-]/', '', $value) ?: '', 0, 9);
        }

        if ($field === 'estado') {
            $value = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $value) ?: '', 0, 2));
        }

        if ($value === '') {
            delete_user_meta($user_id, $meta_key);
        } else {
            update_user_meta($user_id, $meta_key, $value);
        }
    }

    return rest_ensure_response([
        'success' => true,
        'profile' => tailpress_profile_frontend_payload(wp_get_current_user())['dadosPerfil'],
    ]);
}

function tailpress_profile_update_password(WP_REST_Request $request)
{
    if (tailpress_profile_is_read_only()) {
        return new WP_Error(
            'lucia_profile_read_only',
            __('A senha não pode ser alterada nesta tela.', 'tailpress'),
            ['status' => 403]
        );
    }

    $user = wp_get_current_user();
    $current_password = (string) $request->get_param('senhaAtual');
    $new_password = (string) $request->get_param('senhaNova');
    $confirm_password = (string) $request->get_param('confirmarSenha');

    if (!wp_check_password($current_password, $user->user_pass, (int) $user->ID)) {
        return new WP_Error(
            'lucia_profile_wrong_password',
            __('A senha atual não confere.', 'tailpress'),
            ['status' => 400]
        );
    }

    if ($new_password !== $confirm_password) {
        return new WP_Error(
            'lucia_profile_password_mismatch',
            __('A nova senha e a confirmação não coincidem.', 'tailpress'),
            ['status' => 400]
        );
    }

    if (strlen($new_password) < 8) {
        return new WP_Error(
            'lucia_profile_password_short',
            __('A nova senha deve ter pelo menos 8 caracteres.', 'tailpress'),
            ['status' => 400]
        );
    }

    wp_set_password($new_password, (int) $user->ID);

    return rest_ensure_response([
        'success' => true,
        'reauthRequired' => true,
        'message' => __('Senha alterada. Entre novamente com a nova senha.', 'tailpress'),
        'loginUrl' => home_url('/entrar/'),
    ]);
}

function tailpress_profile_request_email_change(WP_REST_Request $request)
{
    if (tailpress_profile_is_read_only()) {
        return new WP_Error(
            'lucia_profile_read_only',
            __('O e-mail é sincronizado externamente e não pode ser alterado nesta tela.', 'tailpress'),
            ['status' => 403]
        );
    }

    $user = wp_get_current_user();
    $user_id = (int) $user->ID;
    $email = sanitize_email((string) $request->get_param('email'));

    if ($email === '' || !is_email($email)) {
        return new WP_Error(
            'lucia_profile_invalid_email',
            __('Informe um e-mail válido.', 'tailpress'),
            ['status' => 400]
        );
    }

    if (strtolower($email) === strtolower($user->user_email)) {
        return new WP_Error(
            'lucia_profile_same_email',
            __('Este e-mail já está cadastrado na sua conta.', 'tailpress'),
            ['status' => 400]
        );
    }

    $existing_user_id = email_exists($email);
    if ($existing_user_id && (int) $existing_user_id !== $user_id) {
        return new WP_Error(
            'lucia_profile_email_exists',
            __('Este e-mail já está em uso por outro usuário.', 'tailpress'),
            ['status' => 400]
        );
    }

    $result = tailpress_profile_start_email_change($user_id, $email);

    if (is_wp_error($result)) {
        return $result;
    }

    return rest_ensure_response($result);
}

function tailpress_profile_confirm_email_change(): void
{
    if (!isset($_GET['lucia_profile_email_confirm'])) {
        return;
    }

    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

    if ($user_id <= 0 || $token === '') {
        wp_die(esc_html__('Link de confirmação inválido.', 'tailpress'));
    }

    $pending = tailpress_profile_get_pending_email($user_id);

    if (empty($pending) || empty($pending['token_hash']) || !wp_check_password($token, (string) $pending['token_hash'])) {
        wp_die(esc_html__('Link de confirmação inválido ou expirado.', 'tailpress'));
    }

    $result = wp_update_user([
        'ID' => $user_id,
        'user_email' => (string) $pending['email'],
    ]);

    if (is_wp_error($result)) {
        delete_user_meta($user_id, tailpress_profile_pending_email_meta_key());
        wp_die(esc_html($result->get_error_message()));
    }

    delete_user_meta($user_id, tailpress_profile_pending_email_meta_key());

    if (is_user_logged_in() && get_current_user_id() === $user_id) {
        wp_safe_redirect(add_query_arg('email_status', 'confirmed', home_url('/perfil/')));
        exit;
    }

    wp_die(
        sprintf(
            '<p>%s</p><p><a href="%s">%s</a></p>',
            esc_html__('E-mail confirmado com sucesso.', 'tailpress'),
            esc_url(home_url('/entrar/')),
            esc_html__('Entrar no portal', 'tailpress')
        )
    );
}
add_action('template_redirect', 'tailpress_profile_confirm_email_change', 0);

function tailpress_profile_register_rest_routes(): void
{
    if (!tailpress_profile_is_read_only()) {
        register_rest_route('lucia-portal/v1', '/perfil', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'tailpress_profile_update_contact',
            'permission_callback' => 'tailpress_profile_rest_permission',
        ]);

        register_rest_route('lucia-portal/v1', '/perfil/senha', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'tailpress_profile_update_password',
            'permission_callback' => 'tailpress_profile_rest_permission',
        ]);

        register_rest_route('lucia-portal/v1', '/perfil/email', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'tailpress_profile_request_email_change',
            'permission_callback' => 'tailpress_profile_rest_permission',
        ]);
    }
}
add_action('rest_api_init', 'tailpress_profile_register_rest_routes');
