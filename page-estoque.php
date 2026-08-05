<?php
/**
 * Estoque page template.
 *
 * @package tailpress
 */

get_header();

$estoque_data = [
    'ok' => false,
    'loading' => true,
    'message' => '',
    'revendedora' => ['cpf' => '', 'nome' => ''],
    'produtos' => [],
    'unidades' => ['todos'],
    'vitrines' => [],
    'restUrl' => rest_url('lucia-portal/v1/consignacao'),
    'nonce' => wp_create_nonce('wp_rest'),
];

ob_start();
?>

<script>
window.luciaEstoqueData = <?php echo wp_json_encode($estoque_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div x-data="estoquePage(window.luciaEstoqueData)" class="max-w-7xl mx-auto space-y-6">
  <div>
    <h1 class="font-display text-4xl text-gray-900 dark:text-white mb-2">
      Estoque em Mãos
    </h1>
    <p class="text-gray-600 dark:text-gray-400">
      <template x-if="ok && revendedora.nome">
        <span x-text="'Produtos em consignação para ' + revendedora.nome"></span>
      </template>
      <template x-if="!ok || !revendedora.nome">
        <span>Gerencie seus produtos disponíveis para venda</span>
      </template>
    </p>
  </div>

  <div x-show="loading" x-cloak class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <template x-for="index in 3" :key="index">
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-gray-200 dark:border-gray-700 animate-pulse">
          <div class="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700 mb-4"></div>
          <div class="h-8 w-32 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
      </template>
    </div>
    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg animate-pulse">
      <div class="h-12 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <template x-for="index in 6" :key="index">
        <div class="animate-pulse">
          <div class="aspect-[3/4] rounded-2xl bg-gray-200 dark:bg-gray-700"></div>
          <div class="mt-4 space-y-4">
            <div class="h-5 w-4/5 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="space-y-2">
              <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
              <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
            </div>
            <div class="h-20 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
      </template>
    </div>
  </div>

  <div x-show="!loading && !ok" x-cloak class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#BA007C]">
    <h2 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Não foi possível carregar o estoque</h2>
    <p class="text-gray-600 dark:text-gray-300" x-text="message"></p>
  </div>

  <template x-if="!loading && ok">
    <div class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#BA007C] transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Total de Peças</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="totalPecas"></p>
        </div>
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#D4AF37] transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Valor Total</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="'R$ ' + formatCurrency(valorTotalEstoque)"></p>
        </div>
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-green-500 transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Em Pré-venda</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="totalPrevendas"></p>
        </div>
      </div>

      <div x-show="vitrines.length > 0" x-cloak class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h2 class="font-display text-2xl text-gray-900 dark:text-white">
              Vitrine pública
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Compartilhe este link com clientes. A vitrine mostra apenas produtos disponíveis.
            </p>
          </div>

          <div class="grid w-full gap-3 lg:max-w-2xl">
            <template x-for="vitrine in vitrines" :key="vitrine.consignacao_id">
              <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-[#0F0F0F] sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    Sacola <span x-text="vitrine.consignacao_id"></span>
                  </p>
                  <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" x-text="vitrine.url"></p>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="vitrine.produtos"></span> produtos · <span x-text="vitrine.pecas"></span> peças disponíveis
                  </p>
                </div>
                <div class="flex gap-2">
                  <a
                    :href="vitrine.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-[#BA007C] hover:text-[#BA007C] dark:border-gray-600 dark:text-gray-200">
                    Abrir
                  </a>
                  <button
                    type="button"
                    x-on:click="copyVitrine(vitrine)"
                    class="inline-flex items-center justify-center rounded-lg bg-[#BA007C] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#8B1538]"
                    x-text="copiedVitrine === vitrine.consignacao_id ? 'Copiado' : 'Copiar'">
                  </button>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1 relative">
            <input
              type="text"
              placeholder="Buscar por descrição, tamanho ou unidade..."
              x-model.debounce.250ms="busca"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-[#0F0F0F] dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-[#BA007C] focus:border-transparent transition-colors duration-300"
            />
          </div>

          <select
            x-model="unidadeFiltro"
            class="px-4 py-3 border cursor-pointer border-gray-300 dark:border-gray-600 dark:bg-[#0F0F0F] dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-[#BA007C] focus:border-transparent bg-white transition-colors duration-300"
          >
            <template x-for="unidade in unidades" :key="unidade">
              <option :value="unidade" x-text="unidade === 'todos' ? 'Todas Unidades' : unidade"></option>
            </template>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8">
        <template x-for="produto in produtosFiltrados" :key="produto.id">
          <article x-data="{ imageFailed: false }" class="group flex h-full flex-col">
            <div class="relative aspect-[3/4] w-full overflow-hidden rounded-2xl bg-gray-100 dark:bg-[#0F0F0F]">
              <template x-if="produto.imagem_url && !imageFailed">
                <img
                  :src="produto.imagem_url"
                  :alt="produto.nome"
                  loading="lazy"
                  x-on:error="imageFailed = true"
                  class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-110"
                />
              </template>
              <template x-if="!produto.imagem_url || imageFailed">
                <div class="flex h-full w-full flex-col items-center justify-center bg-[#FDFBFD] px-6 text-center dark:bg-[#0F0F0F]">
                  <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-2xl font-bold text-[#BA007C] shadow-sm dark:bg-[#1A1A1A]">
                    LR
                  </div>
                  <span class="mt-4 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400">
                    Imagem em breve
                  </span>
                </div>
              </template>

              <div class="absolute bottom-3 left-3 rounded-md bg-white/90 px-2 py-1 text-[10px] font-bold uppercase tracking-tight text-gray-900 shadow-sm backdrop-blur">
                <span x-text="produto.disponivel_quantidade"></span> disp.
              </div>
              <div x-show="produto.prevenda_quantidade > 0" class="absolute right-3 top-3 rounded-full bg-green-100/95 px-3 py-1 text-xs font-bold text-green-700 shadow-sm backdrop-blur">
                <span x-text="produto.prevenda_quantidade"></span> pré-venda
              </div>
            </div>

              <div class="mt-4 flex flex-1 flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition-all duration-300 group-hover:shadow-lg dark:bg-[#1A1A1A] dark:ring-gray-800">
              <div>
                <div class="mb-1 flex flex-wrap items-center gap-2">
                  <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 md:text-xs" x-text="produto.unidade || 'Produto'"></p>
                  <template x-if="produto.codigo">
                    <span
                      class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-semibold text-gray-500 dark:border-gray-700 dark:bg-[#0F0F0F] dark:text-gray-400"
                      :title="'Código do produto: ' + produto.codigo"
                    >
                      Cód. <span class="ml-1" x-text="produto.codigo"></span>
                    </span>
                  </template>
                </div>
                <h3 class="line-clamp-2 text-base font-semibold leading-tight text-gray-900 dark:text-white" x-text="produto.nome"></h3>
                <p class="mt-2 text-xl font-black text-gray-950 dark:text-white" x-text="'R$ ' + formatCurrency(produto.valor_unitario)"></p>
              </div>

              <div class="mt-4 flex flex-wrap gap-2">
                <div class="inline-flex min-h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">
                  Tam. <span class="ml-1" x-text="produto.tamanho || 'Único'"></span>
                </div>
                <div class="inline-flex min-h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300">
                  <span x-text="produto.unidade || '-'"></span>
                </div>
              </div>

              <div class="mt-auto pt-5">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-[#0F0F0F]">
                  <div class="grid grid-cols-3 divide-x divide-gray-200 dark:divide-gray-700">
                    <div class="px-3 py-3 text-center">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</p>
                      <p class="mt-1 text-2xl font-black leading-none text-[#BA007C]" x-text="produto.quantidade"></p>
                    </div>
                    <div class="px-3 py-3 text-center">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Disp.</p>
                      <p class="mt-1 text-2xl font-black leading-none text-gray-950 dark:text-white" x-text="produto.disponivel_quantidade"></p>
                    </div>
                    <div class="px-3 py-3 text-center">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pré</p>
                      <p class="mt-1 text-2xl font-black leading-none text-green-600" x-text="produto.prevenda_quantidade"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </template>
      </div>

      <div x-show="produtosFiltrados.length === 0" x-cloak class="bg-white dark:bg-[#1A1A1A] rounded-xl p-12 text-center shadow-lg transition-colors duration-300">
        <p class="text-gray-600 dark:text-gray-400">Nenhum produto encontrado</p>
      </div>
    </div>
  </template>
</div>

<?php
$content = ob_get_clean();

get_template_part('template-parts/sidebar-nav', null, [
    'content' => $content,
]);

get_footer();
