<?php
/**
 * Public consultora storefront template.
 *
 * @package tailpress
 */

$snapshot = $GLOBALS['tailpress_storefront_snapshot'] ?? null;
$storefront_url = (string) ($GLOBALS['tailpress_storefront_url'] ?? home_url('/'));
$logo_url = get_theme_file_uri('img/logo-rosa-lucia-ramos.png');
$products = is_array($snapshot) ? (array) ($snapshot['produtos'] ?? []) : [];
$consultora_nome = is_array($snapshot) ? (string) ($snapshot['consultora_nome'] ?? __('Consultora Lúcia Ramos', 'tailpress')) : '';
$whatsapp = is_array($snapshot) ? preg_replace('/\D+/', '', (string) ($snapshot['consultora_whatsapp'] ?? '')) : '';
$updated_at = is_array($snapshot) ? (string) ($snapshot['updated_at'] ?? '') : '';
$size_order = ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG', 'XXG', 'TAMANHO UNICO', 'U', 'UN', 'UNICO'];

$consultora_nome = trim($consultora_nome);
if ($consultora_nome !== '' && function_exists('mb_convert_case') && function_exists('mb_strtolower')) {
    $consultora_nome = mb_convert_case(mb_strtolower($consultora_nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
} elseif ($consultora_nome !== '') {
    $consultora_nome = ucwords(strtolower($consultora_nome));
}

$normalize_size = static function (string $size): string {
    $size = trim($size);

    if ($size === '') {
        return 'TAMANHO UNICO';
    }

    $size = strtoupper(remove_accents($size));

    if (in_array($size, ['U', 'UN', 'UNICO', 'UNICA', 'TAMANHO UNICO'], true)) {
        return 'TAMANHO UNICO';
    }

    return $size;
};

$display_size = static function (string $size): string {
    return $size === 'TAMANHO UNICO' ? __('Tamanho único', 'tailpress') : $size;
};

$sort_sizes = static function (array &$sizes) use ($size_order): void {
    uksort($sizes, static function ($a, $b) use ($size_order) {
        $position_a = array_search($a, $size_order, true);
        $position_b = array_search($b, $size_order, true);

        $position_a = $position_a === false ? PHP_INT_MAX : $position_a;
        $position_b = $position_b === false ? PHP_INT_MAX : $position_b;

        if ($position_a === $position_b) {
            return strnatcasecmp((string) $a, (string) $b);
        }

        return $position_a <=> $position_b;
    });
};

$grouped_products = [];
$size_summary = [];

foreach ($products as $product) {
    if (!is_array($product)) {
        continue;
    }

    $product_name = trim((string) ($product['nome'] ?? ''));
    $product_code = trim((string) ($product['codigo'] ?? ''));
    $product_unit = trim((string) ($product['unidade'] ?? ''));
    $product_price = (float) ($product['valor_unitario'] ?? 0);
    $product_quantity = max(0, (int) ($product['quantidade'] ?? 0));
    $product_size = $normalize_size((string) ($product['tamanho'] ?? ''));
    $product_image = esc_url_raw((string) ($product['imagem_url'] ?? ''));

    if ($product_name === '' || $product_quantity <= 0) {
        continue;
    }

    $group_key = md5($product_name . '|' . $product_code . '|' . $product_unit . '|' . number_format($product_price, 2, '.', ''));

    if (!isset($grouped_products[$group_key])) {
        $grouped_products[$group_key] = [
            'nome' => $product_name,
            'codigo' => $product_code,
            'unidade' => $product_unit,
            'valor_unitario' => $product_price,
            'imagem_url' => $product_image,
            'quantidade' => 0,
            'tamanhos' => [],
        ];
    }

    if ($grouped_products[$group_key]['imagem_url'] === '' && $product_image !== '') {
        $grouped_products[$group_key]['imagem_url'] = $product_image;
    }

    $grouped_products[$group_key]['quantidade'] += $product_quantity;
    $grouped_products[$group_key]['tamanhos'][$product_size] = ($grouped_products[$group_key]['tamanhos'][$product_size] ?? 0) + $product_quantity;
    $size_summary[$product_size] = ($size_summary[$product_size] ?? 0) + $product_quantity;
}

foreach ($grouped_products as &$grouped_product) {
    $sort_sizes($grouped_product['tamanhos']);
}
unset($grouped_product);

$sort_sizes($size_summary);
uasort($grouped_products, static fn($a, $b) => strnatcasecmp((string) ($a['nome'] ?? ''), (string) ($b['nome'] ?? '')));

$total_models = count($grouped_products);
$total_items = array_reduce($grouped_products, static fn($sum, $product) => $sum + (int) ($product['quantidade'] ?? 0), 0);

get_header();
?>

<div class="min-h-screen bg-[#FAF7F9] text-gray-950">
    <header class="border-b border-gray-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Lúcia Ramos', 'tailpress'); ?>" class="w-50 object-contain">
            </a>
            <span class="rounded-full border border-[#BA007C]/15 bg-[#BA007C]/5 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#BA007C]">
                Vitrine
            </span>
        </div>
    </header>

    <?php if (!is_array($snapshot) || empty($snapshot)): ?>
        <main class="mx-auto flex min-h-[70vh] max-w-3xl flex-col items-center justify-center px-5 py-16 text-center">
            <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-[#BA007C]/10 text-2xl font-bold text-[#BA007C]">
                LR
            </div>
            <h1 class="text-4xl text-gray-950">
                Vitrine não encontrada
            </h1>
            <p class="mt-4 text-gray-600">
                O link pode ter sido digitado incorretamente ou a vitrine ainda não foi publicada pela consultora.
            </p>
        </main>
    <?php else: ?>
        <main>
            <section class="bg-white">
                <div class="mx-auto max-w-7xl px-5 py-7 md:py-12">
                    <div class="max-w-4xl">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.22em] text-[#BA007C]">
                            Lúcia Ramos
                        </p>
                        <h1 class="text-3xl leading-tight text-gray-950 md:text-6xl">
                            Vitrine de <?php echo esc_html($consultora_nome !== '' ? $consultora_nome : __('Consultora', 'tailpress')); ?>
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-gray-600 md:text-base md:leading-7">
                            Selecione os modelos disponíveis e fale direto pelo WhatsApp para confirmar tamanho, forma de pagamento e entrega.
                        </p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 text-sm md:mt-8 md:gap-3">
                        <span class="rounded-full bg-gray-100 px-4 py-2 font-medium text-gray-700">
                            <?php echo esc_html((string) $total_models); ?> modelos
                        </span>
                        <span class="rounded-full bg-[#BA007C]/10 px-4 py-2 font-semibold text-[#BA007C]">
                            <?php echo esc_html((string) $total_items); ?> peças disponíveis
                        </span>
                        <?php if ($updated_at !== ''): ?>
                            <span class="rounded-full bg-gray-100 px-4 py-2 font-medium text-gray-500">
                                Atualizada em <?php echo esc_html(mysql2date('d/m/Y H:i', $updated_at)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section
                x-data="{
                    sizeFilter: 'todos',
                    searchQuery: '',
                    normalize(value) {
                        return String(value || '')
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .trim();
                    },
                    matchesProduct(el) {
                        const sizes = JSON.parse(el.dataset.sizes || '[]');
                        const needle = this.normalize(this.searchQuery);
                        const name = this.normalize(el.dataset.name);
                        const code = this.normalize(el.dataset.code);
                        const matchesSize = this.sizeFilter === 'todos' || sizes.includes(this.sizeFilter);
                        const matchesSearch = needle === '' || name.includes(needle) || code.includes(needle);

                        return matchesSize && matchesSearch;
                    }
                }"
                class="mx-auto max-w-7xl px-5 py-8 md:py-10">
                <?php if (empty($grouped_products)): ?>
                    <div class="rounded-2xl border border-gray-100 bg-white px-6 py-14 text-center shadow-sm">
                        <h2 class="text-3xl text-gray-950">
                            Nenhum produto disponível no momento
                        </h2>
                        <p class="mt-3 text-gray-600">
                            Os produtos podem estar em pré-venda ou a sacola pode estar em atualização.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="sticky top-0 z-20 -mx-5 mb-8 border-b border-gray-100 bg-white/85 px-5 py-4 backdrop-blur-xl">
                        <div class="relative mx-auto max-w-7xl">
                            <div class="relative z-10 mb-5">
                                <label for="lucia-storefront-search" class="sr-only">
                                    <?php esc_html_e('Buscar por nome ou código', 'tailpress'); ?>
                                </label>
                                <div class="rounded-2xl border border-gray-200 bg-white transition-colors duration-200 focus-within:border-[#BA007C]">
                                    <input
                                        id="lucia-storefront-search"
                                        type="search"
                                        x-model.debounce.150ms="searchQuery"
                                        placeholder="<?php esc_attr_e('Buscar por nome ou código', 'tailpress'); ?>"
                                        class="w-full rounded-2xl border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0"
                                    >
                                </div>
                            </div>

                            <div class="flex max-w-full select-none items-center gap-2 overflow-x-auto mt-6 pb-2 pt-1 pr-12">
                                <button
                                    type="button"
                                    x-on:click="sizeFilter = 'todos'"
                                    class="shrink-0 rounded-full px-5 py-2 text-xs font-bold uppercase tracking-widest transition-all"
                                    :class="sizeFilter === 'todos' ? 'bg-gray-950 text-white shadow-lg shadow-gray-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                    Todos
                                </button>
                                <?php foreach ($size_summary as $size => $quantity): ?>
                                    <button
                                        type="button"
                                        x-on:click="sizeFilter = '<?php echo esc_js((string) $size); ?>'"
                                        class="shrink-0 font-sans rounded-full px-5 py-2 text-xs font-bold uppercase tracking-widest transition-all"
                                        :class="sizeFilter === '<?php echo esc_js((string) $size); ?>' ? 'bg-[#BA007C] text-white shadow-lg shadow-pink-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                        <?php echo esc_html($display_size((string) $size)); ?>
                                        <span class="ml-1 opacity-70"><?php echo esc_html((string) $quantity); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="pointer-events-none absolute inset-y-0 right-0 w-12 bg-gradient-to-l from-white/90 to-transparent"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 lg:gap-8">
                        <?php foreach ($grouped_products as $product): ?>
                            <?php
                            $product_name = (string) ($product['nome'] ?? '');
                            $product_code = (string) ($product['codigo'] ?? '');
                            $product_unit = (string) ($product['unidade'] ?? '');
                            $product_image = (string) ($product['imagem_url'] ?? '');
                            $product_sizes = (array) ($product['tamanhos'] ?? []);
                            $product_size_keys = array_keys($product_sizes);
                            $size_message = implode(', ', array_map(
                                static fn($size, $quantity) => sprintf('%s (%d)', $display_size((string) $size), (int) $quantity),
                                $product_size_keys,
                                array_values($product_sizes)
                            ));
                            $message = sprintf(
                                'Olá, tenho interesse no produto %s. Tamanhos disponíveis: %s. Vitrine: %s',
                                $product_name,
                                $size_message !== '' ? $size_message : '-',
                                $storefront_url
                            );
                            $whatsapp_url = $whatsapp !== ''
                                ? 'https://wa.me/55' . $whatsapp . '?text=' . rawurlencode($message)
                                : '';
                            ?>
                            <article
                                data-sizes="<?php echo esc_attr(wp_json_encode(array_values($product_size_keys))); ?>"
                                data-name="<?php echo esc_attr($product_name); ?>"
                                data-code="<?php echo esc_attr($product_code); ?>"
                                x-data="{ imageFailed: false }"
                                x-show="matchesProduct($el)"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="group flex flex-col">
                                <div class="relative aspect-[3/4] w-full overflow-hidden rounded-2xl bg-gray-100">
                                    <?php if ($product_image !== ''): ?>
                                        <img
                                            x-show="!imageFailed"
                                            src="<?php echo esc_url($product_image); ?>"
                                            alt="<?php echo esc_attr($product_name); ?>"
                                            loading="lazy"
                                            x-on:error="imageFailed = true"
                                            class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-110">
                                    <?php endif; ?>
                                    <div x-show="<?php echo $product_image !== '' ? 'imageFailed' : 'true'; ?>" x-cloak class="flex h-full w-full flex-col items-center justify-center bg-[#FDFBFD] px-5 text-center">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="" class="w-16 opacity-50">
                                        <span class="mt-4 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400">
                                            Imagem em breve
                                        </span>
                                    </div>

                                    <div class="absolute bottom-3 left-3 rounded-md bg-white/90 px-2 py-1 text-[10px] font-bold uppercase tracking-tight text-gray-900 shadow-sm backdrop-blur">
                                        <?php echo esc_html((string) ((int) ($product['quantidade'] ?? 0))); ?> disponíveis
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-1 flex-col">
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 md:text-xs">
                                            <?php echo esc_html($product_unit !== '' ? $product_unit : __('Produto', 'tailpress')); ?>
                                        </p>
                                        <?php if ($product_code !== ''): ?>
                                            <span
                                                title="<?php echo esc_attr(sprintf(__('Código do produto: %s', 'tailpress'), $product_code)); ?>"
                                                class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-semibold text-gray-500">
                                                <?php esc_html_e('Cód.', 'tailpress'); ?>
                                                <span class="ml-1"><?php echo esc_html($product_code); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="line-clamp-2 font-sans text-sm font-medium leading-tight text-gray-800 md:text-base">
                                        <?php echo esc_html($product_name); ?>
                                    </h2>

                                    <p class="mt-2 text-lg font-black text-gray-950 md:text-xl">
                                        R$ <?php echo esc_html(number_format((float) ($product['valor_unitario'] ?? 0), 2, ',', '.')); ?>
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <?php foreach ($product_sizes as $size => $quantity): ?>
                                            <span
                                                title="<?php echo esc_attr(sprintf(__('%d peça(s) disponível(is)', 'tailpress'), (int) $quantity)); ?>"
                                                class="inline-flex h-7 min-w-7 items-center justify-center rounded-full border border-gray-200 px-2 text-[11px] font-semibold text-gray-700 md:h-8 md:min-w-8 md:text-xs">
                                                <?php echo esc_html($display_size((string) $size)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="mt-auto pt-4">
                                        <?php if ($whatsapp_url !== ''): ?>
                                            <a
                                                href="<?php echo esc_url($whatsapp_url); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex w-full items-center justify-center rounded-full border border-gray-950 px-3 py-3 text-[11px] font-bold uppercase tracking-widest text-gray-950 transition-all hover:bg-gray-950 hover:text-white md:px-4 md:py-3.5">
                                                Tenho interesse
                                            </a>
                                        <?php else: ?>
                                            <p class="flex w-full items-center justify-center rounded-full bg-gray-100 px-3 py-3 text-[11px] font-bold uppercase tracking-widest text-gray-400 md:px-4 md:py-3.5">
                                                Fale com a consultora
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    <?php endif; ?>
</div>

<?php
get_footer();
