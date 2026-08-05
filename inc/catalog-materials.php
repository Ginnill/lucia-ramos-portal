<?php
/**
 * Catalog materials managed in WordPress admin.
 *
 * @package tailpress
 */

function tailpress_catalog_material_post_type(): string
{
    return 'lucia_material';
}

function tailpress_catalog_material_meta_key(string $key): string
{
    return '_lucia_catalog_material_' . $key;
}

function tailpress_catalog_material_sections(): array
{
    return [
        'videos' => __('Vídeos de treinamento', 'tailpress'),
        'documentos' => __('Documentos e guias', 'tailpress'),
    ];
}

function tailpress_catalog_material_normalize_section($section): string
{
    $section = sanitize_key((string) $section);

    return array_key_exists($section, tailpress_catalog_material_sections()) ? $section : '';
}

function tailpress_catalog_material_flash_key(): string
{
    return 'tailpress_catalog_material_flash_' . get_current_user_id();
}

function tailpress_catalog_material_flash(string $message, string $type = 'error'): void
{
    if (get_current_user_id() <= 0 || $message === '') {
        return;
    }

    set_transient(tailpress_catalog_material_flash_key(), [
        'message' => $message,
        'type' => $type,
    ], MINUTE_IN_SECONDS);
}

function tailpress_catalog_material_render_flash(): void
{
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== tailpress_catalog_material_post_type()) {
        return;
    }

    $notice = get_transient(tailpress_catalog_material_flash_key());

    if (!is_array($notice) || empty($notice['message'])) {
        return;
    }

    delete_transient(tailpress_catalog_material_flash_key());

    $type = in_array($notice['type'] ?? '', ['success', 'info', 'warning', 'error'], true)
        ? (string) $notice['type']
        : 'error';

    printf(
        '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr($type),
        esc_html((string) $notice['message'])
    );
}
add_action('admin_notices', 'tailpress_catalog_material_render_flash');

function tailpress_catalog_material_register_post_type(): void
{
    $result = register_post_type(tailpress_catalog_material_post_type(), [
        'labels' => [
            'name' => __('Materiais do Catálogo', 'tailpress'),
            'singular_name' => __('Material do Catálogo', 'tailpress'),
            'menu_name' => __('Materiais do Catálogo', 'tailpress'),
            'all_items' => __('Todos os Materiais do Catálogo', 'tailpress'),
            'add_new_item' => __('Adicionar material', 'tailpress'),
            'edit_item' => __('Editar material', 'tailpress'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-portfolio',
        'menu_position' => 32,
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false,
    ]);

    if (is_wp_error($result)) {
        error_log(
            sprintf(
                'Falha ao registrar o CPT %s: %s',
                tailpress_catalog_material_post_type(),
                $result->get_error_message()
            )
        );
    }
}
add_action('init', 'tailpress_catalog_material_register_post_type');

function tailpress_catalog_material_is_valid_video_url(string $url): bool
{
    if ($url === '' || !wp_http_validate_url($url)) {
        return false;
    }

    $parts = wp_parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));

    return in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], true);
}

function tailpress_catalog_material_is_valid_pdf_attachment(int $attachment_id): bool
{
    if ($attachment_id <= 0) {
        return false;
    }

    return get_post_mime_type($attachment_id) === 'application/pdf';
}

function tailpress_catalog_material_attachment_size_label(int $attachment_id): string
{
    $path = get_attached_file($attachment_id);

    if (!is_string($path) || $path === '' || !is_readable($path)) {
        return '';
    }

    $size = filesize($path);

    return $size !== false ? size_format((int) $size) : '';
}

function tailpress_catalog_material_document_summary(int $attachment_id): string
{
    if (!tailpress_catalog_material_is_valid_pdf_attachment($attachment_id)) {
        return __('Nenhum PDF selecionado.', 'tailpress');
    }

    $attachment = get_post($attachment_id);
    $size_label = tailpress_catalog_material_attachment_size_label($attachment_id);
    $title = $attachment instanceof WP_Post ? $attachment->post_title : '';

    if ($title === '') {
        $title = basename((string) get_attached_file($attachment_id));
    }

    return $size_label !== ''
        ? sprintf(__('%1$s (%2$s)', 'tailpress'), $title, $size_label)
        : $title;
}

function tailpress_catalog_material_render_meta_box(WP_Post $post): void
{
    $section = tailpress_catalog_material_normalize_section(get_post_meta($post->ID, tailpress_catalog_material_meta_key('section'), true));
    $video_duration = (string) get_post_meta($post->ID, tailpress_catalog_material_meta_key('video_duration'), true);
    $video_url = (string) get_post_meta($post->ID, tailpress_catalog_material_meta_key('video_url'), true);
    $document_id = (int) get_post_meta($post->ID, tailpress_catalog_material_meta_key('document_id'), true);

    wp_nonce_field('tailpress_catalog_material_save', 'tailpress_catalog_material_nonce');
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="lucia_catalog_material_section"><?php esc_html_e('Seção', 'tailpress'); ?></label>
            </th>
            <td>
                <select id="lucia_catalog_material_section" name="lucia_catalog_material_section" required>
                    <option value=""><?php esc_html_e('Selecione uma seção', 'tailpress'); ?></option>
                    <?php foreach (tailpress_catalog_material_sections() as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($section, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Cada seção aceita no máximo 4 materiais publicados.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr data-lucia-catalog-section="videos">
            <th scope="row">
                <label for="lucia_catalog_material_video_duration"><?php esc_html_e('Duração', 'tailpress'); ?></label>
            </th>
            <td>
                <input
                    type="text"
                    id="lucia_catalog_material_video_duration"
                    name="lucia_catalog_material_video_duration"
                    value="<?php echo esc_attr($video_duration); ?>"
                    class="regular-text"
                    placeholder="5:30"
                >
                <p class="description"><?php esc_html_e('Informe a duração manual exibida no card.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr data-lucia-catalog-section="videos">
            <th scope="row">
                <label for="lucia_catalog_material_video_url"><?php esc_html_e('Link do vídeo', 'tailpress'); ?></label>
            </th>
            <td>
                <input
                    type="url"
                    id="lucia_catalog_material_video_url"
                    name="lucia_catalog_material_video_url"
                    value="<?php echo esc_attr($video_url); ?>"
                    class="large-text"
                    placeholder="https://www.youtube.com/watch?v=..."
                >
                <p class="description"><?php esc_html_e('Use um link válido do YouTube. O portal abrirá o vídeo em nova aba.', 'tailpress'); ?></p>
            </td>
        </tr>
        <tr data-lucia-catalog-section="documentos">
            <th scope="row"><?php esc_html_e('Arquivo PDF', 'tailpress'); ?></th>
            <td>
                <input type="hidden" id="lucia_catalog_material_document_id" name="lucia_catalog_material_document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                <button type="button" class="button" id="lucia_catalog_material_select_pdf">
                    <?php esc_html_e('Selecionar PDF', 'tailpress'); ?>
                </button>
                <button
                    type="button"
                    class="button-link-delete"
                    id="lucia_catalog_material_clear_pdf"
                    <?php echo $document_id > 0 ? '' : 'hidden'; ?>
                >
                    <?php esc_html_e('Remover arquivo', 'tailpress'); ?>
                </button>
                <p class="description" id="lucia_catalog_material_document_summary">
                    <?php echo esc_html(tailpress_catalog_material_document_summary($document_id)); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

function tailpress_catalog_material_add_meta_boxes(): void
{
    add_meta_box(
        'tailpress_catalog_material_settings',
        __('Configuração do material', 'tailpress'),
        'tailpress_catalog_material_render_meta_box',
        tailpress_catalog_material_post_type(),
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'tailpress_catalog_material_add_meta_boxes');

function tailpress_catalog_material_enqueue_admin_assets(string $hook): void
{
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $post_type = '';

    if (isset($_GET['post_type'])) {
        $post_type = sanitize_key(wp_unslash($_GET['post_type']));
    } elseif (isset($_GET['post'])) {
        $post_type = (string) get_post_type((int) $_GET['post']);
    }

    if ($post_type !== tailpress_catalog_material_post_type()) {
        return;
    }

    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'tailpress_catalog_material_enqueue_admin_assets');

function tailpress_catalog_material_render_admin_script(): void
{
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== tailpress_catalog_material_post_type() || $screen->base !== 'post') {
        return;
    }

    $script_labels = [
        'emptyDocument' => __('Nenhum PDF selecionado.', 'tailpress'),
        'selectedDocument' => __('PDF selecionado', 'tailpress'),
        'selectTitle' => __('Selecionar PDF', 'tailpress'),
        'selectButton' => __('Usar PDF', 'tailpress'),
    ];
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const labels = <?php echo wp_json_encode($script_labels); ?>;
      const sectionField = document.getElementById('lucia_catalog_material_section');
      const videoRows = document.querySelectorAll('[data-lucia-catalog-section="videos"]');
      const documentRows = document.querySelectorAll('[data-lucia-catalog-section="documentos"]');
      const documentIdField = document.getElementById('lucia_catalog_material_document_id');
      const summaryField = document.getElementById('lucia_catalog_material_document_summary');
      const selectButton = document.getElementById('lucia_catalog_material_select_pdf');
      const clearButton = document.getElementById('lucia_catalog_material_clear_pdf');

      const toggleRows = function () {
        const section = sectionField ? sectionField.value : '';

        videoRows.forEach(function (row) {
          row.hidden = section !== 'videos';
        });

        documentRows.forEach(function (row) {
          row.hidden = section !== 'documentos';
        });
      };

      const setDocumentSummary = function (label) {
        if (!summaryField) {
          return;
        }

        summaryField.textContent = label || labels.emptyDocument;
      };

      const toggleClearButton = function () {
        if (!clearButton || !documentIdField) {
          return;
        }

        clearButton.hidden = documentIdField.value === '';
      };

      if (sectionField) {
        sectionField.addEventListener('change', toggleRows);
        toggleRows();
      }

      toggleClearButton();

      if (selectButton && documentIdField && typeof wp !== 'undefined' && wp.media) {
        selectButton.addEventListener('click', function (event) {
          event.preventDefault();

          const frame = wp.media({
            title: labels.selectTitle,
            button: { text: labels.selectButton },
            library: { type: 'application/pdf' },
            multiple: false
          });

          frame.on('select', function () {
            const selection = frame.state().get('selection').first();

            if (!selection) {
              return;
            }

            const attachment = selection.toJSON();
            const sizeLabel = attachment.filesizeHumanReadable ? ' (' + attachment.filesizeHumanReadable + ')' : '';

            documentIdField.value = attachment.id ? String(attachment.id) : '';
            setDocumentSummary((attachment.filename || attachment.title || labels.selectedDocument) + sizeLabel);
            toggleClearButton();
          });

          frame.open();
        });
      }

      if (clearButton && documentIdField) {
        clearButton.addEventListener('click', function (event) {
          event.preventDefault();
          documentIdField.value = '';
          setDocumentSummary(labels.emptyDocument);
          toggleClearButton();
        });
      }
    });
    </script>
    <?php
}
add_action('admin_footer', 'tailpress_catalog_material_render_admin_script');

function tailpress_catalog_material_save(int $post_id, WP_Post $post): void
{
    if (
        wp_is_post_revision($post_id)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || get_post_type($post_id) !== tailpress_catalog_material_post_type()
        || !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $can_save_meta = isset($_POST['tailpress_catalog_material_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tailpress_catalog_material_nonce'])), 'tailpress_catalog_material_save');

    if ($can_save_meta) {
        $section = isset($_POST['lucia_catalog_material_section'])
            ? tailpress_catalog_material_normalize_section(wp_unslash($_POST['lucia_catalog_material_section']))
            : '';
        $video_duration = isset($_POST['lucia_catalog_material_video_duration'])
            ? sanitize_text_field(wp_unslash($_POST['lucia_catalog_material_video_duration']))
            : '';
        $video_url = isset($_POST['lucia_catalog_material_video_url'])
            ? esc_url_raw(wp_unslash($_POST['lucia_catalog_material_video_url']))
            : '';
        $document_id = isset($_POST['lucia_catalog_material_document_id'])
            ? absint(wp_unslash($_POST['lucia_catalog_material_document_id']))
            : 0;

        update_post_meta($post_id, tailpress_catalog_material_meta_key('section'), $section);
        update_post_meta($post_id, tailpress_catalog_material_meta_key('video_duration'), $section === 'videos' ? $video_duration : '');
        update_post_meta($post_id, tailpress_catalog_material_meta_key('video_url'), $section === 'videos' ? $video_url : '');
        update_post_meta($post_id, tailpress_catalog_material_meta_key('document_id'), $section === 'documentos' ? $document_id : 0);
    }

    $section = tailpress_catalog_material_normalize_section(get_post_meta($post_id, tailpress_catalog_material_meta_key('section'), true));
    $video_duration = (string) get_post_meta($post_id, tailpress_catalog_material_meta_key('video_duration'), true);
    $video_url = (string) get_post_meta($post_id, tailpress_catalog_material_meta_key('video_url'), true);
    $document_id = (int) get_post_meta($post_id, tailpress_catalog_material_meta_key('document_id'), true);
    $errors = [];

    if ($section === '') {
        $errors[] = __('Selecione a seção do material antes de salvar.', 'tailpress');
    } elseif ($section === 'videos') {
        if ($video_duration === '') {
            $errors[] = __('Informe a duração do vídeo.', 'tailpress');
        }

        if (!tailpress_catalog_material_is_valid_video_url($video_url)) {
            $errors[] = __('Use um link válido do YouTube para vídeos de treinamento.', 'tailpress');
        }
    } elseif ($section === 'documentos' && !tailpress_catalog_material_is_valid_pdf_attachment($document_id)) {
        $errors[] = __('Selecione um arquivo PDF válido para documentos e guias.', 'tailpress');
    }

    if ($section !== '' && $post->post_status === 'publish') {
        $published_ids = get_posts([
            'post_type' => tailpress_catalog_material_post_type(),
            'post_status' => 'publish',
            'numberposts' => 4,
            'fields' => 'ids',
            'post__not_in' => [$post_id],
            'meta_query' => [
                [
                    'key' => tailpress_catalog_material_meta_key('section'),
                    'value' => $section,
                ],
            ],
        ]);

        if (count($published_ids) >= 4) {
            $errors[] = sprintf(
                __('A seção %s já possui 4 materiais publicados. O material salvo foi movido para rascunho.', 'tailpress'),
                '"' . (tailpress_catalog_material_sections()[$section] ?? $section) . '"'
            );
        }
    }

    if (empty($errors)) {
        return;
    }

    if ($post->post_status === 'publish') {
        remove_action('save_post_' . tailpress_catalog_material_post_type(), 'tailpress_catalog_material_save', 10);

        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'draft',
        ]);

        add_action('save_post_' . tailpress_catalog_material_post_type(), 'tailpress_catalog_material_save', 10, 2);
    }

    tailpress_catalog_material_flash(implode(' ', $errors));
}
add_action('save_post_' . tailpress_catalog_material_post_type(), 'tailpress_catalog_material_save', 10, 2);

function tailpress_catalog_material_items(string $section, int $limit = 4): array
{
    $section = tailpress_catalog_material_normalize_section($section);
    $limit = max(1, min(4, $limit));

    if ($section === '') {
        return [];
    }

    $posts = get_posts([
        'post_type' => tailpress_catalog_material_post_type(),
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => tailpress_catalog_material_meta_key('section'),
                'value' => $section,
            ],
        ],
    ]);

    $items = [];

    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }

        if ($section === 'videos') {
            $video_url = (string) get_post_meta($post->ID, tailpress_catalog_material_meta_key('video_url'), true);
            $video_duration = (string) get_post_meta($post->ID, tailpress_catalog_material_meta_key('video_duration'), true);

            if ($video_duration === '' || !tailpress_catalog_material_is_valid_video_url($video_url)) {
                continue;
            }

            $items[] = [
                'nome' => get_the_title($post),
                'duracao' => $video_duration,
                'link' => $video_url,
            ];

            continue;
        }

        $document_id = (int) get_post_meta($post->ID, tailpress_catalog_material_meta_key('document_id'), true);
        $document_url = wp_get_attachment_url($document_id);

        if (!tailpress_catalog_material_is_valid_pdf_attachment($document_id) || !$document_url) {
            continue;
        }

        $items[] = [
            'nome' => get_the_title($post),
            'tipo' => 'PDF',
            'tamanho' => tailpress_catalog_material_attachment_size_label($document_id),
            'arquivo_url' => $document_url,
        ];
    }

    return array_slice($items, 0, $limit);
}

function tailpress_catalog_material_items_for_portal(): array
{
    return [
        'videos' => tailpress_catalog_material_items('videos', 4),
        'documentos' => tailpress_catalog_material_items('documentos', 4),
    ];
}
