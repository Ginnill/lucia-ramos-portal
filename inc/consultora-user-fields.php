<?php
/**
 * Consultora user admin fields.
 *
 * @package tailpress
 */

function tailpress_consultora_cpf_meta_key(): string
{
    return 'consultora_cpf';
}

function tailpress_consultora_whatsapp_meta_key(): string
{
    return 'consultora_whatsapp';
}

function tailpress_normalize_cpf($cpf): string
{
    return preg_replace('/\D+/', '', (string) $cpf) ?: '';
}

function tailpress_normalize_phone($phone): string
{
    return preg_replace('/\D+/', '', (string) $phone) ?: '';
}

function tailpress_get_posted_consultora_cpf(): string
{
    if (!isset($_POST['consultora_cpf'])) {
        return '';
    }

    return tailpress_normalize_cpf(wp_unslash($_POST['consultora_cpf']));
}

function tailpress_get_posted_consultora_whatsapp(): string
{
    if (!isset($_POST['consultora_whatsapp'])) {
        return '';
    }

    return tailpress_normalize_phone(wp_unslash($_POST['consultora_whatsapp']));
}

function tailpress_posted_user_is_consultora(): bool
{
    if (isset($_POST['members_user_roles']) && is_array($_POST['members_user_roles'])) {
        $roles = array_map('sanitize_key', wp_unslash($_POST['members_user_roles']));

        return in_array('consultora', $roles, true);
    }

    if (isset($_POST['role'])) {
        return sanitize_key(wp_unslash($_POST['role'])) === 'consultora';
    }

    return false;
}

function tailpress_consultora_cpf_exists(string $cpf, int $exclude_user_id = 0): bool
{
    if ($cpf === '') {
        return false;
    }

    $user_ids = get_users([
        'fields' => 'ID',
        'meta_key' => tailpress_consultora_cpf_meta_key(),
        'meta_value' => $cpf,
        'number' => 1,
        'exclude' => $exclude_user_id > 0 ? [$exclude_user_id] : [],
    ]);

    return !empty($user_ids);
}

function tailpress_render_consultora_cpf_field($user = null): void
{
    $user_id = $user instanceof WP_User ? (int) $user->ID : 0;

    if ($user_id > 0 && !current_user_can('edit_user', $user_id)) {
        return;
    }

    if ($user_id === 0 && !current_user_can('create_users')) {
        return;
    }

    $cpf = $user_id > 0
        ? (string) get_user_meta($user_id, tailpress_consultora_cpf_meta_key(), true)
        : tailpress_get_posted_consultora_cpf();
    $whatsapp = $user_id > 0
        ? (string) get_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), true)
        : tailpress_get_posted_consultora_whatsapp();
    ?>
    <h2><?php esc_html_e('Dados da consultora', 'tailpress'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th>
                <label for="consultora_cpf"><?php esc_html_e('CPF da consultora', 'tailpress'); ?></label>
            </th>
            <td>
                <input
                    type="text"
                    name="consultora_cpf"
                    id="consultora_cpf"
                    value="<?php echo esc_attr($cpf); ?>"
                    class="regular-text"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="000.000.000-00"
                    maxlength="14"
                />
                <p class="description">
                    <?php esc_html_e('Obrigatório para usuários com a função Consultora. Salvo apenas com números.', 'tailpress'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="consultora_whatsapp"><?php esc_html_e('WhatsApp da consultora', 'tailpress'); ?></label>
            </th>
            <td>
                <input
                    type="text"
                    name="consultora_whatsapp"
                    id="consultora_whatsapp"
                    value="<?php echo esc_attr($whatsapp); ?>"
                    class="regular-text"
                    inputmode="tel"
                    autocomplete="off"
                    placeholder="(00) 00000-0000"
                    maxlength="15"
                />
                <p class="description">
                    <?php esc_html_e('Usado no botão de contato da vitrine pública. Salvo apenas com números.', 'tailpress'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('user_new_form', 'tailpress_render_consultora_cpf_field');
add_action('show_user_profile', 'tailpress_render_consultora_cpf_field');
add_action('edit_user_profile', 'tailpress_render_consultora_cpf_field');

function tailpress_print_consultora_cpf_admin_mask(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || !in_array($screen->id, ['user', 'user-edit', 'profile'], true)) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('consultora_cpf');
            var whatsappInput = document.getElementById('consultora_whatsapp');

            var formatCpf = function (value) {
                var digits = String(value || '').replace(/\D/g, '').slice(0, 11);

                if (digits.length > 9) {
                    return digits.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2}).*/, '$1.$2.$3-$4');
                }

                if (digits.length > 6) {
                    return digits.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
                }

                if (digits.length > 3) {
                    return digits.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
                }

                return digits;
            };

            var formatPhone = function (value) {
                var digits = String(value || '').replace(/\D/g, '').slice(0, 11);

                if (digits.length > 10) {
                    return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
                }

                if (digits.length > 6) {
                    return digits.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                }

                if (digits.length > 2) {
                    return digits.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
                }

                return digits;
            };

            if (input) {
                input.value = formatCpf(input.value);
                input.addEventListener('input', function () {
                    input.value = formatCpf(input.value);
                });
            }

            if (whatsappInput) {
                whatsappInput.value = formatPhone(whatsappInput.value);
                whatsappInput.addEventListener('input', function () {
                    whatsappInput.value = formatPhone(whatsappInput.value);
                });
            }
        });
    </script>
    <?php
}
add_action('admin_footer-user-new.php', 'tailpress_print_consultora_cpf_admin_mask');
add_action('admin_footer-user-edit.php', 'tailpress_print_consultora_cpf_admin_mask');
add_action('admin_footer-profile.php', 'tailpress_print_consultora_cpf_admin_mask');

function tailpress_validate_consultora_cpf(WP_Error $errors, bool $update, $user): void
{
    $user_id = $update && isset($user->ID) ? (int) $user->ID : 0;
    $cpf = tailpress_get_posted_consultora_cpf();
    $is_consultora = tailpress_posted_user_is_consultora();

    if ($is_consultora && $cpf === '') {
        $errors->add(
            'consultora_cpf_required',
            __('Informe o CPF da consultora.', 'tailpress')
        );

        return;
    }

    if ($cpf !== '' && strlen($cpf) !== 11) {
        $errors->add(
            'consultora_cpf_invalid',
            __('O CPF da consultora deve conter 11 dígitos.', 'tailpress')
        );

        return;
    }

    if ($cpf !== '' && tailpress_consultora_cpf_exists($cpf, $user_id)) {
        $errors->add(
            'consultora_cpf_duplicate',
            __('Este CPF já está cadastrado em outro usuário.', 'tailpress')
        );
    }

    $whatsapp = tailpress_get_posted_consultora_whatsapp();

    if ($whatsapp !== '' && (strlen($whatsapp) < 10 || strlen($whatsapp) > 11)) {
        $errors->add(
            'consultora_whatsapp_invalid',
            __('O WhatsApp da consultora deve conter DDD e 8 ou 9 dígitos.', 'tailpress')
        );
    }
}
add_action('user_profile_update_errors', 'tailpress_validate_consultora_cpf', 10, 3);

function tailpress_save_consultora_fields(int $user_id): void
{
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['consultora_cpf'])) {
        $cpf = tailpress_get_posted_consultora_cpf();

        if ($cpf === '') {
            delete_user_meta($user_id, tailpress_consultora_cpf_meta_key());
        } else {
            update_user_meta($user_id, tailpress_consultora_cpf_meta_key(), $cpf);
        }
    }

    if (isset($_POST['consultora_whatsapp'])) {
        $whatsapp = tailpress_get_posted_consultora_whatsapp();

        if ($whatsapp === '') {
            delete_user_meta($user_id, tailpress_consultora_whatsapp_meta_key());
        } else {
            update_user_meta($user_id, tailpress_consultora_whatsapp_meta_key(), $whatsapp);
        }
    }
}
add_action('user_register', 'tailpress_save_consultora_fields', 20);
add_action('personal_options_update', 'tailpress_save_consultora_fields');
add_action('edit_user_profile_update', 'tailpress_save_consultora_fields');
