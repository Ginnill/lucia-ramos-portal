<?php
/**
 * Sidebar navigation template.
 *
 * @package tailpress
 */

$content = isset($args['content']) ? (string) $args['content'] : '';

$logo_url = get_theme_file_uri('img/lucia-ramos-logo-branco.png');

$logout_url = wp_logout_url(home_url('/entrar/'));

$menu_items = [
    [
        'path' => home_url('/'),
        'label' => __('Dashboard', 'tailpress'),
        'active' => is_front_page(),
        'icon' => 'dashboard',
    ],
    [
        'path' => home_url('/estoque/'),
        'label' => __('Estoque', 'tailpress'),
        'active' => is_page('estoque'),
        'icon' => 'estoque',
    ],
    [
        'path' => home_url('/vendas/'),
        'label' => __('Vendas', 'tailpress'),
        'active' => is_page('vendas'),
        'icon' => 'vendas',
    ],
    [
        'path' => home_url('/premiacoes/'),
        'label' => __('Premiações', 'tailpress'),
        'active' => is_page('premiacoes'),
        'icon' => 'premiacoes',
    ],
    [
        'path' => home_url('/catalogo/'),
        'label' => __('Catalogo', 'tailpress'),
        'active' => is_page('catalogo'),
        'icon' => 'catalogo',
    ],
    [
        'path' => home_url('/perfil/'),
        'label' => __('Perfil', 'tailpress'),
        'active' => is_page('perfil'),
        'icon' => 'perfil',
    ],
];

$nav_item_class = static function (bool $is_active): string {
    $base = 'flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 ';

    if ($is_active) {
        return $base . 'bg-white dark:bg-[#BA007C] text-[#BA007C] dark:text-white shadow-lg font-semibold';
    }

    return $base . 'text-white/90 dark:text-gray-300 hover:bg-white/10 dark:hover:bg-[#BA007C]/20 dark:hover:text-[#BA007C]';
};

$render_logo = static function (string $class) use ($logo_url): string {
    $class_attr = esc_attr($class);

    return sprintf(
        '<img src="%1$s" alt="%2$s" class="%3$s" />',
        esc_url($logo_url),
        esc_attr__('Lúcia Ramos', 'tailpress'),
        $class_attr
    );
};

$render_icon = static function (string $name, string $class = 'w-5 h-5'): string {
    $class_attr = esc_attr($class);

    switch ($name) {
        case 'premiacoes':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5M9 4.5v1.125a3 3 0 0 0 6 0V4.5m-6 0H5.625A1.125 1.125 0 0 0 4.5 5.625v.75A4.125 4.125 0 0 0 8.625 10.5M15 4.5h3.375A1.125 1.125 0 0 1 19.5 5.625v.75a4.125 4.125 0 0 1-4.125 4.125M12 9v6m-3.75 4.5h7.5M9.75 15h4.5v4.5h-4.5z"/></svg>';
        case 'catalogo':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>';
        case 'perfil':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 20.25a7.5 7.5 0 0115 0"/></svg>';
        case 'vendas':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>';
        case 'estoque':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 7.5L12 3l8.25 4.5L12 12 3.75 7.5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 12L12 16.5 20.25 12M3.75 16.5L12 21l8.25-4.5"/></svg>';
        case 'moon':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1111.21 3c.06.66.09 1.34.09 2.02A7.5 7.5 0 0019 12.5c.69 0 1.36-.03 2-.09z"/></svg>';
        case 'sun':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2.25v2.25M12 19.5v2.25M4.5 12H2.25M21.75 12H19.5M5.64 5.64 4.05 4.05M19.95 19.95l-1.59-1.59M18.36 5.64l1.59-1.59M4.05 19.95l1.59-1.59"/></svg>';
        case 'logout':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 12H9m0 0l3-3m-3 3l3 3"/></svg>';
        case 'menu':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>';
        case 'x':
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>';
        case 'dashboard':
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class_attr . '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3.5" y="3.5" width="7" height="7" rx="1.1" stroke-width="1.7"/><rect x="13.5" y="3.5" width="7" height="4.5" rx="1.1" stroke-width="1.7"/><rect x="13.5" y="10.5" width="7" height="10" rx="1.1" stroke-width="1.7"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.1" stroke-width="1.7"/></svg>';
    }
};
?>

<div
    x-data="{ sidebarOpen: false }"
    class="flex h-screen bg-[#F8F8F8] dark:bg-[#0F0F0F] overflow-hidden transition-colors duration-300"
>
    <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-gradient-to-b from-[#BA007C] to-[#8B1538] dark:bg-[#1A1A1A] dark:from-[#1A1A1A] dark:to-[#1A1A1A] text-white shadow-xl transition-colors duration-300">
        <div class="p-6 border-b border-white/20 dark:border-gray-700">
            <?php echo $render_logo('w-full h-auto'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <p class="text-xs mt-3 text-white/80 dark:text-gray-400 text-center">Portal de Consultoras</p>
        </div>

        <nav class="flex-1 p-4 space-y-2">
            <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo esc_url($item['path']); ?>" class="<?php echo esc_attr($nav_item_class((bool) $item['active'])); ?>">
                    <?php echo $render_icon((string) $item['icon']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-white/20 dark:border-gray-700 space-y-2">
            <button
                type="button"
                x-on:click="$store.theme.toggle()"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-white/90 dark:text-gray-300 hover:bg-white/10 dark:hover:bg-[#BA007C]/20 dark:hover:text-[#BA007C] transition-all w-full"
            >
                <template x-if="$store.theme.mode === 'light'">
                    <?php echo $render_icon('moon'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
                <template x-if="$store.theme.mode !== 'light'">
                    <?php echo $render_icon('sun'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
                <span x-text="$store.theme.mode === 'light' ? 'Modo Escuro' : 'Modo Claro'"></span>
            </button>
            <button
                type="button"
                x-on:click="window.location.href = '<?php echo esc_js($logout_url); ?>'"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-white/90 dark:text-gray-300 hover:bg-white/10 dark:hover:bg-[#BA007C]/20 dark:hover:text-[#BA007C] transition-all w-full"
            >
                <?php echo $render_icon('logout'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span>Sair</span>
            </button>
        </div>
    </aside>

    <div x-cloak x-show="sidebarOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-on:click="sidebarOpen = false"></div>

    <aside
        class="fixed top-0 left-0 h-full w-64 bg-gradient-to-b from-[#BA007C] to-[#8B1538] dark:bg-[#1A1A1A] dark:from-[#1A1A1A] dark:to-[#1A1A1A] text-white shadow-xl transform transition-all duration-300 z-50 lg:hidden"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex items-center justify-between p-6 border-b border-white/20 dark:border-gray-700">
            <div class="flex-1">
                <?php echo $render_logo('w-full h-auto'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <p class="text-xs mt-3 text-white/80 dark:text-gray-400 text-center">Portal de Consultoras</p>
            </div>
            <button type="button" x-on:click="sidebarOpen = false" class="ml-3">
                <?php echo $render_icon('x', 'w-6 h-6'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </button>
        </div>

        <nav class="flex-1 p-4 space-y-2">
            <?php foreach ($menu_items as $item): ?>
                <a
                    href="<?php echo esc_url($item['path']); ?>"
                    x-on:click="sidebarOpen = false"
                    class="<?php echo esc_attr($nav_item_class((bool) $item['active'])); ?>"
                >
                    <?php echo $render_icon((string) $item['icon']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-white/20 dark:border-gray-700 space-y-2">
            <button
                type="button"
                x-on:click="$store.theme.toggle()"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-white/90 dark:text-gray-300 hover:bg-white/10 dark:hover:bg-[#BA007C]/20 dark:hover:text-[#BA007C] transition-all w-full"
            >
                <template x-if="$store.theme.mode === 'light'">
                    <?php echo $render_icon('moon'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
                <template x-if="$store.theme.mode !== 'light'">
                    <?php echo $render_icon('sun'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
                <span x-text="$store.theme.mode === 'light' ? 'Modo Escuro' : 'Modo Claro'"></span>
            </button>
            <button
                type="button"
                x-on:click="window.location.href = '<?php echo esc_js($logout_url); ?>'"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-white/90 dark:text-gray-300 hover:bg-white/10 dark:hover:bg-[#BA007C]/20 dark:hover:text-[#BA007C] transition-all w-full"
            >
                <?php echo $render_icon('logout'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span>Sair</span>
            </button>
        </div>
    </aside>

    <main class="flex-1 overflow-auto">
        <header class="lg:hidden bg-white dark:bg-[#1A1A1A] shadow-sm p-4 flex items-center justify-between sticky top-0 z-30 transition-colors duration-300">
            <button type="button" x-on:click="sidebarOpen = true">
                <?php echo $render_icon('menu', 'w-6 h-6 text-[#BA007C]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </button>
            <?php echo $render_logo('h-8 w-auto'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <button type="button" x-on:click="$store.theme.toggle()">
                <template x-if="$store.theme.mode === 'light'">
                    <?php echo $render_icon('moon', 'w-6 h-6 text-[#BA007C]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
                <template x-if="$store.theme.mode !== 'light'">
                    <?php echo $render_icon('sun', 'w-6 h-6 text-[#D4AF37]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </template>
            </button>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </main>
</div>
