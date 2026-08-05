<?php
/**
 * Vendas page template.
 *
 * @package tailpress
 */

get_header();

$vendas_data = [
    'ok' => false,
    'loading' => true,
    'message' => '',
    'revendedora' => ['cpf' => '', 'nome' => ''],
    'itens' => [],
    'consignacaoUrl' => rest_url('lucia-portal/v1/consignacao'),
    'restUrl' => rest_url('lucia-portal/v1/pre-venda'),
    'nonce' => wp_create_nonce('wp_rest'),
];

ob_start();
?>

<script>
window.luciaVendasData = <?php echo wp_json_encode($vendas_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div x-data="vendasPage(window.luciaVendasData)" class="max-w-7xl mx-auto space-y-6">
  <div>
    <h1 class="font-display text-4xl text-gray-900 dark:text-white mb-2">
      Pré-vendas
    </h1>
    <p class="text-gray-600 dark:text-gray-400">
      <template x-if="ok && revendedora.nome">
        <span x-text="'Marque os itens em pré-venda para ' + revendedora.nome"></span>
      </template>
      <template x-if="!ok || !revendedora.nome">
        <span>Marque itens individuais da consignação como pré-venda</span>
      </template>
    </p>
  </div>

  <div x-show="loading" x-cloak class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <template x-for="index in 3" :key="index">
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-gray-200 dark:border-gray-700 animate-pulse">
          <div class="h-4 w-28 rounded bg-gray-200 dark:bg-gray-700 mb-4"></div>
          <div class="h-8 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
      </template>
    </div>
    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg animate-pulse">
      <div class="h-12 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
    </div>
    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl shadow-lg overflow-hidden animate-pulse">
      <div class="space-y-0">
        <template x-for="index in 6" :key="index">
          <div class="grid grid-cols-5 gap-4 border-b border-gray-100 dark:border-gray-800 p-4">
            <div class="col-span-2 space-y-2">
              <div class="h-4 w-4/5 rounded bg-gray-200 dark:bg-gray-700"></div>
              <div class="h-3 w-3/5 rounded bg-gray-200 dark:bg-gray-700"></div>
            </div>
            <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-9 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <div x-show="!loading && !ok" x-cloak class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#BA007C]">
    <h2 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Não foi possível carregar as pré-vendas</h2>
    <p class="text-gray-600 dark:text-gray-300" x-text="message"></p>
  </div>

  <template x-if="!loading && ok">
    <div class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#BA007C] transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Itens na Consignação</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="itens.length"></p>
        </div>
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-green-500 transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Itens em Pré-venda</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="totalPrevendas"></p>
        </div>
        <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#D4AF37] transition-colors duration-300">
          <p class="text-sm text-gray-600 dark:text-gray-400">Valor em Pré-venda</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="'R$ ' + formatCurrency(valorPrevenda)"></p>
        </div>
      </div>

      <div x-show="errorMessage" x-cloak class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="errorMessage"></div>
      <div x-show="successMessage" x-cloak class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" x-text="successMessage"></div>

      <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
        <div class="flex flex-col sm:flex-row gap-4">
          <input
            type="text"
            placeholder="Buscar por descrição, tamanho ou código..."
            x-model.debounce.250ms="busca"
            class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-[#0F0F0F] dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-[#BA007C] focus:border-transparent transition-colors duration-300"
          />

          <select
            x-model="statusFiltro"
            class="px-4 py-3 border cursor-pointer border-gray-300 dark:border-gray-600 dark:bg-[#0F0F0F] dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-[#BA007C] focus:border-transparent bg-white transition-colors duration-300"
          >
            <option value="todos">Todos os status</option>
            <option value="S">Em pré-venda</option>
            <option value="N">Disponíveis</option>
          </select>
        </div>
      </div>

      <div class="bg-white dark:bg-[#1A1A1A] rounded-xl shadow-lg overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Item</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Tamanho</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Valor</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Status</th>
                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Ação</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="item in itensFiltrados" :key="item.cons_item_id">
                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-[#0F0F0F]">
                  <td class="py-4 px-4">
                    <p class="font-semibold text-sm text-gray-900 dark:text-white" x-text="item.descricao"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      Item <span x-text="item.cons_item_id"></span> · Produto <span x-text="item.produto_id || '-'"></span> · <span x-text="item.unidade || '-'"></span>
                    </p>
                  </td>
                  <td class="py-4 px-4 text-sm text-gray-900 dark:text-white" x-text="item.tamanho || '-'"></td>
                  <td class="py-4 px-4 text-sm text-gray-900 dark:text-white" x-text="'R$ ' + formatCurrency(item.valor_unitario)"></td>
                  <td class="py-4 px-4">
                    <span
                      class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                      :class="item.pre_venda === 'S' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                      x-text="item.pre_venda === 'S' ? 'Em pré-venda' : 'Disponível'"
                    ></span>
                  </td>
                  <td class="py-4 px-4 text-right">
                    <button
                      type="button"
                      x-on:click="togglePreVenda(item)"
                      :disabled="isSubmitting(item)"
                      class="inline-flex items-center cursor-pointer justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :class="item.pre_venda === 'S' ? 'bg-gray-900 text-white hover:bg-gray-700' : 'bg-[#BA007C] text-white hover:bg-[#8B1538]'"
                      x-text="isSubmitting(item) ? 'Salvando...' : (item.pre_venda === 'S' ? 'Desmarcar' : 'Marcar')"
                    ></button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div x-show="itensFiltrados.length === 0" x-cloak class="p-12 text-center">
          <p class="text-gray-600 dark:text-gray-400">Nenhum item encontrado</p>
        </div>
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
