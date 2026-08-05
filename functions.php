<?php

if (is_file(__DIR__.'/vendor/autoload_packages.php')) {
    require_once __DIR__.'/vendor/autoload_packages.php';
}

function tailpress_vite_server_url(): string
{
    return 'http://portal-lucia-ramos.test:3000';
}

function tailpress_should_use_vite_dev_server(): bool
{
    if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
        return true;
    }

    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';

    return $host === 'localhost'
        || $host === '127.0.0.1'
        || str_ends_with($host, '.test')
        || str_ends_with($host, '.local');
}

function tailpress(): TailPress\Framework\Theme
{
    $viteCompiler = new TailPress\Framework\Assets\ViteCompiler(
        tailpress_vite_server_url()
    );

    if (!tailpress_should_use_vite_dev_server()) {
        $viteCompiler = new class (tailpress_vite_server_url()) extends TailPress\Framework\Assets\ViteCompiler {
            public function isDevServerRunning(): bool
            {
                return false;
            }
        };
    }

    return TailPress\Framework\Theme::instance()
        ->assets(fn($manager) => $manager
            ->withCompiler($viteCompiler, fn($compiler) => $compiler
                ->registerAsset('resources/css/app.css')
                ->registerAsset('resources/js/app.js')
                ->editorStyleFile('resources/css/editor-style.css')
            )
            ->enqueueAssets()
        )
        ->features(fn($manager) => $manager->add(TailPress\Framework\Features\MenuOptions::class))
        ->menus(fn($manager) => $manager->add('primary', __( 'Primary Menu', 'tailpress')))
        ->themeSupport(fn($manager) => $manager->add([
            'title-tag',
            'custom-logo',
            'post-thumbnails',
            'align-wide',
            'wp-block-styles',
            'responsive-embeds',
            'html5' => [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ]
        ]));
}

tailpress();

/**
 * Ensure Vite frontend scripts are always loaded as ES modules.
 *
 * TailPress ViteCompiler can miss app handle conversion in dev mode, which
 * prevents imports (like Alpine) from executing and leaves window.Alpine undefined.
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    $moduleHandles = ['vite-client', 'tailpress-app'];
    $isThemeAppSrc = str_contains((string) $src, '/resources/js/app.js')
        || preg_match('#/dist/assets/app-[^/]+\.js$#', (string) $src);

    if (!in_array($handle, $moduleHandles, true) && !$isThemeAppSrc) {
        return $tag;
    }

    if (preg_match('/\stype=(["\']).*?\1/i', $tag)) {
        return preg_replace('/\stype=(["\']).*?\1/i', ' type="module"', $tag, 1);
    }

    return str_replace('<script ', '<script type="module" ', $tag);
}, 999, 3);

/**
 * Checks if the current user has the consultora role.
 */
function tailpress_user_is_consultora($user = null): bool
{
    $user = $user instanceof WP_User ? $user : wp_get_current_user();

    return $user instanceof WP_User && in_array('consultora', (array) $user->roles, true);
    // return $user instanceof WP_User && (in_array('consultora', (array) $user->roles, true) || in_array('administrator', (array) $user->roles, true));
}

/**
 * Checks if the current login attempt came from the public portal login form.
 */
function tailpress_is_portal_login_request(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    $portal_login = isset($_POST['lucia_portal_login'])
        ? sanitize_text_field(wp_unslash($_POST['lucia_portal_login']))
        : '';

    return $portal_login === '1';
}

/**
 * Slugs available inside the consultora portal.
 */
function tailpress_portal_page_slugs(): array
{
    return ['catalogo', 'perfil', 'vendas', 'estoque', 'premiacoes'];
}

/**
 * Checks whether the current request is a consultora portal page.
 */
function tailpress_is_portal_page(): bool
{
    return is_front_page() || is_page(tailpress_portal_page_slugs());
}

/**
 * Restrict access to protected portal routes and keep consultora users inside portal pages.
 */
function tailpress_portal_route_guard(): void
{
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $login_url = home_url('/entrar/');
    $portal_slugs = tailpress_portal_page_slugs();
    $is_protected_portal_page = is_front_page() || is_page($portal_slugs);

    if ($is_protected_portal_page && (!is_user_logged_in() || !tailpress_user_is_consultora())) {
        wp_safe_redirect($login_url);
        exit;
    }

    if (!is_user_logged_in() || !tailpress_user_is_consultora()) {
        return;
    }

    if (is_page('entrar')) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (!is_front_page() && !is_page($portal_slugs)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}
add_action('template_redirect', 'tailpress_portal_route_guard');

/**
 * Prevent consultora users from accessing the wp-admin area.
 */
function tailpress_block_consultora_admin_access(): void
{
    if (!is_user_logged_in() || !tailpress_user_is_consultora() || wp_doing_ajax()) {
        return;
    }

    wp_safe_redirect(home_url('/'));
    exit;
}
add_action('admin_init', 'tailpress_block_consultora_admin_access');

/**
 * Keep logged-in consultora users away from default wp-login.php screens.
 */
function tailpress_consultora_login_screen_guard(): void
{
    if (!is_user_logged_in() || !tailpress_user_is_consultora()) {
        return;
    }

    global $pagenow;

    if ($pagenow !== 'wp-login.php') {
        return;
    }

    $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : 'login';

    if ($action === 'logout') {
        return;
    }

    wp_safe_redirect(home_url('/'));
    exit;
}
add_action('init', 'tailpress_consultora_login_screen_guard');

/**
 * Keep the public portal login exclusive to consultora users.
 */
function tailpress_require_consultora_for_portal_login($user, string $password)
{
    if (!tailpress_is_portal_login_request() || !($user instanceof WP_User)) {
        return $user;
    }

    if (tailpress_user_is_consultora($user)) {
        return $user;
    }

    return new WP_Error(
        'consultora_forbidden',
        __('Acesso permitido apenas para consultoras.', 'tailpress')
    );
}
add_filter('wp_authenticate_user', 'tailpress_require_consultora_for_portal_login', 10, 2);

/**
 * Redirect failed portal logins back to /entrar with an error flag.
 */
function tailpress_failed_login_redirect_to_entrar(string $username, WP_Error $error): void
{
    if (wp_doing_ajax() || !tailpress_is_portal_login_request()) {
        return;
    }

    $status = $error->get_error_code() === 'consultora_forbidden' ? 'forbidden' : 'failed';

    wp_safe_redirect(home_url('/entrar/?login=' . $status));
    exit;
}
add_action('wp_login_failed', 'tailpress_failed_login_redirect_to_entrar', 10, 2);

/**
 * Force consultora users to land on the portal dashboard after login.
 */
function tailpress_consultora_login_redirect(string $redirect_to, string $requested_redirect_to, $user): string
{
    if (!($user instanceof WP_User)) {
        return $redirect_to;
    }

    if (tailpress_user_is_consultora($user)) {
        return home_url('/');
    }

    return $redirect_to;
}
add_filter('login_redirect', 'tailpress_consultora_login_redirect', 10, 3);

/**
 * Always redirect to /entrar after logout.
 */
function tailpress_logout_redirect_to_entrar(string $redirect_to, string $requested_redirect_to, $user): string
{
    if (!($user instanceof WP_User) || !in_array('consultora', (array) $user->roles, true)) {
        return $redirect_to;
    }

    return home_url('/entrar/');
}
add_filter('logout_redirect', 'tailpress_logout_redirect_to_entrar', 10, 3);

if (is_file(__DIR__ . '/inc/consultora-user-fields.php')) {
    require_once __DIR__ . '/inc/consultora-user-fields.php';
}

if (is_file(__DIR__ . '/inc/consignacao-api.php')) {
    require_once __DIR__ . '/inc/consignacao-api.php';
}

if (is_file(__DIR__ . '/inc/consultora-profile.php')) {
    require_once __DIR__ . '/inc/consultora-profile.php';
}

if (is_file(__DIR__ . '/inc/consultora-provisioning.php')) {
    require_once __DIR__ . '/inc/consultora-provisioning.php';
}

if (is_file(__DIR__ . '/inc/consultora-storefront.php')) {
    require_once __DIR__ . '/inc/consultora-storefront.php';
}

if (is_file(__DIR__ . '/inc/dashboard-goals.php')) {
    require_once __DIR__ . '/inc/dashboard-goals.php';
}

if (is_file(__DIR__ . '/inc/dashboard-notices.php')) {
    require_once __DIR__ . '/inc/dashboard-notices.php';
}

if (is_file(__DIR__ . '/inc/premiacoes.php')) {
    require_once __DIR__ . '/inc/premiacoes.php';
}

if (is_file(__DIR__ . '/inc/catalog-materials.php')) {
    require_once __DIR__ . '/inc/catalog-materials.php';
}

add_filter('show_admin_bar', '__return_false');
