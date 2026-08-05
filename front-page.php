<?php

/**
 * Front page template (consultora dashboard).
 *
 * @package tailpress
 */

get_header();

$current_user = wp_get_current_user();
$nome_consultora = $current_user instanceof WP_User && $current_user->exists()
  ? ((string) $current_user->first_name !== '' ? (string) $current_user->first_name : (string) $current_user->display_name)
  : __('Consultora', 'tailpress');

$user_data = [
  'nome' => $nome_consultora,
];

$dashboard_data = [
  'loading' => true,
  'restUrl' => rest_url('lucia-portal/v1/consignacao'),
  'nonce' => wp_create_nonce('wp_rest'),
  'goal' => function_exists('tailpress_dashboard_goal_for_user')
    ? tailpress_dashboard_goal_for_user(get_current_user_id())
    : [
      'configured' => false,
      'month' => current_time('Y-m'),
      'metric' => 'pecas',
      'target' => 0,
      'source' => 'none',
    ],
];

$avisos = function_exists('tailpress_dashboard_notice_items')
  ? tailpress_dashboard_notice_items(3)
  : [];

ob_start();
?>

<script>
window.luciaDashboardData = <?php echo wp_json_encode($dashboard_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div x-data="dashboardPage(window.luciaDashboardData)" class="max-w-7xl mx-auto space-y-8">
  <div class="bg-gradient-to-r from-[#BA007C] to-[#8B1538] rounded-2xl p-8 text-white shadow-lg">
    <h1 class="font-display text-4xl mb-2">
      Olá, <?php echo esc_html($user_data['nome']); ?>! 👋
    </h1>
    <p class="text-white/90 text-lg">
      Aqui está um resumo da sua performance este mês
    </p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#D4AF37] transition-colors duration-300">
      <div class="flex items-center justify-between mb-4">
        <div class="p-3 bg-[#D4AF37]/10 dark:bg-[#D4AF37]/20 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M7.5 7.5h6.75a2.25 2.25 0 010 4.5H9.75a2.25 2.25 0 100 4.5H16.5" />
          </svg>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
        </svg>
      </div>
      <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-1">Valor em Pré-venda</h3>
      <p x-show="loading" x-cloak class="mt-2 h-9 w-32 rounded bg-gray-200 dark:bg-gray-700 animate-pulse"></p>
      <p x-show="!loading" x-cloak class="text-3xl font-bold text-gray-900 dark:text-white" x-text="ok ? 'R$ ' + formatCurrency(valorPrevenda) : '—'"></p>
      <p x-show="!loading && ok" x-cloak class="text-xs text-green-600 dark:text-green-400 mt-2">
        <span x-text="vendasRealizadas"></span> peças em pré-venda
      </p>
    </div>

    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#BA007C] transition-colors duration-300">
      <div class="flex items-center justify-between mb-4">
        <div class="p-3 bg-[#BA007C]/10 dark:bg-[#BA007C]/20 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#BA007C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 7.5L12 3l8.25 4.5L12 12 3.75 7.5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 12L12 16.5 20.25 12M3.75 16.5L12 21l8.25-4.5" />
          </svg>
        </div>
      </div>
      <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-1">Peças em Estoque</h3>
      <p x-show="loading" x-cloak class="mt-2 h-9 w-20 rounded bg-gray-200 dark:bg-gray-700 animate-pulse"></p>
      <p x-show="!loading" x-cloak class="text-3xl font-bold text-gray-900 dark:text-white" x-text="ok ? pecasDisponiveis : '—'">
      </p>
      <a href="<?php echo esc_url(home_url('/estoque/')); ?>" class="text-xs text-[#BA007C] dark:text-[#BA007C] hover:underline mt-2 inline-block">
        Gerenciar estoque →
      </a>
      <p x-show="!loading && !ok" x-cloak class="text-xs text-gray-500 dark:text-gray-400 mt-2" x-text="message"></p>
    </div>

    <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg border-l-4 border-[#101828] dark:border-gray-600 transition-colors duration-300">
      <div class="flex items-center justify-between mb-4">
        <div class="p-3 bg-[#101828]/10 dark:bg-gray-700/50 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#101828] dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l8.25 4.5V12c0 4.5-3 7.5-8.25 9-5.25-1.5-8.25-4.5-8.25-9V7.5L12 3z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8.25v4.5l3 1.5" />
          </svg>
        </div>
      </div>
      <h3 class="text-gray-600 dark:text-gray-400 text-sm mb-1" x-text="goalTitle"></h3>
      <div x-show="loading" x-cloak class="mt-2 space-y-3">
        <p class="h-9 w-28 rounded bg-gray-200 dark:bg-gray-700 animate-pulse"></p>
        <p class="h-12 rounded-lg bg-gray-200 dark:bg-gray-700 animate-pulse"></p>
      </div>
      <div x-show="!loading && hasGoal" x-cloak class="mt-3 grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-[#0F0F0F]">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Realizado</p>
          <p class="mt-1 text-xl font-bold leading-tight text-gray-900 dark:text-white" x-text="goalCurrentLabel"></p>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-[#0F0F0F]">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Meta</p>
          <p class="mt-1 text-xl font-bold leading-tight text-[#BA007C]" x-text="goalTargetLabel"></p>
        </div>
      </div>
      <p x-show="!loading && !hasGoal" x-cloak class="text-lg font-semibold text-gray-900 dark:text-white">Meta não definida</p>
      <div class="mt-3">
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
          <div
            class="bg-[#101828] dark:bg-[#D4AF37] h-2 rounded-full transition-all duration-500"
            :style="'width: ' + goalProgressPercent + '%;'"></div>
        </div>
        <p x-show="!loading && hasGoal" x-cloak class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="Math.round(goalProgressPercent) + '% concluído'"></p>
        <p x-show="!loading && !hasGoal" x-cloak class="text-xs text-gray-600 dark:text-gray-400 mt-1">Cadastre a meta do mês no WordPress.</p>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <h2 class="font-display text-2xl mb-6 text-gray-900 dark:text-white">
      Atalhos Rápidos
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <a
        href="<?php echo esc_url(home_url('/vendas/')); ?>"
        class="flex items-center gap-4 p-6 bg-gradient-to-r from-[#BA007C] to-[#8B1538] dark:from-[#BA007C]/90 dark:to-[#8B1538]/90 rounded-xl text-white hover:from-[#8B1538] hover:to-[#6B0F2A] dark:hover:from-[#BA007C] dark:hover:to-[#8B1538] transition-all duration-300 hover:shadow-xl">
        <div class="p-3 bg-white/20 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6.75h16.5l-1.5 9H6l-2.25-9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM16.5 19.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-lg">Pré-vendas</h3>
          <p class="text-sm text-white/80">Marque itens da consignação</p>
        </div>
      </a>

      <a
        href="<?php echo esc_url(home_url('/estoque/')); ?>"
        class="flex items-center gap-4 p-6 bg-gradient-to-r from-[#D4AF37] to-[#B89730] dark:from-gray-700 dark:to-gray-800 rounded-xl text-white hover:from-[#B89730] hover:to-[#9A7D28] dark:hover:from-gray-600 dark:hover:to-gray-700 transition-all duration-300 hover:shadow-xl">
        <div class="p-3 bg-white/20 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5.25v13.5M5.25 12h13.5" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-lg">Ver Estoque</h3>
          <p class="text-sm text-white/80">Consulte suas peças em mãos</p>
        </div>
      </a>
    </div>
  </div>

  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <div class="flex items-center justify-between mb-6">
      <h2 class="font-display text-2xl text-gray-900 dark:text-white">
        Estoque em Mãos
      </h2>
      <a
        href="<?php echo esc_url(home_url('/estoque/')); ?>"
        class="text-sm text-[#BA007C] hover:underline">
        Ver tudo →
      </a>
    </div>
    <div x-show="loading" x-cloak class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <template x-for="index in 4" :key="index">
        <div class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-[#0F0F0F] overflow-hidden animate-pulse">
          <div class="aspect-square bg-gray-200 dark:bg-gray-700"></div>
          <div class="p-3 space-y-2">
            <div class="h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-3 w-2/3 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="h-3 rounded bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </div>
      </template>
    </div>

    <div x-show="!loading && !ok" x-cloak class="rounded-lg border border-[#BA007C]/20 bg-[#BA007C]/5 px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="message"></div>

    <div x-show="!loading && ok && produtosDestaque.length === 0" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#0F0F0F] px-4 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
        Nenhum item disponível encontrado na consignação.
    </div>

    <div x-show="!loading && ok && produtosDestaque.length > 0" x-cloak class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <template x-for="produto in produtosDestaque" :key="produto.id">
        <div
          x-data="{ imageFailed: false }"
          class="bg-gray-50 dark:bg-[#0F0F0F] rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:border-[#BA007C] dark:hover:border-[#BA007C] transition-all hover:shadow-lg group">
          <div class="aspect-square bg-gradient-to-br from-[#BA007C]/10 to-[#D4AF37]/20 dark:from-[#BA007C]/20 dark:to-[#D4AF37]/10 flex items-center justify-center overflow-hidden">
            <template x-if="produto.imagem_url && !imageFailed">
              <img
                :src="produto.imagem_url"
                :alt="produto.nome"
                loading="lazy"
                x-on:error="imageFailed = true"
                class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
              />
            </template>
            <div x-show="!produto.imagem_url || imageFailed" x-cloak class="flex h-full w-full items-center justify-center">
              <div class="h-16 w-16 rounded-full bg-white/80 dark:bg-[#1A1A1A] flex items-center justify-center text-[#BA007C] font-bold text-xl group-hover:scale-105 transition-transform duration-300">
                LR
              </div>
            </div>
          </div>
          <div class="p-3">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-1 line-clamp-1" x-text="produto.nome"></h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
              <span x-text="produto.unidade || '-'"></span> • <span x-text="produto.tamanho || '-'"></span>
            </p>
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                Disponível:
              </span>
              <span class="text-sm font-bold text-[#BA007C]" x-text="produto.disponivel_quantidade"></span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>

  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <div class="flex items-center gap-2 mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#BA007C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <circle cx="12" cy="12" r="9" stroke-width="1.8" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8.25v4.5M12 16.5h.008" />
      </svg>
      <h2 class="font-display text-2xl text-gray-900 dark:text-white">
        Avisos da Lúcia Ramos
      </h2>
    </div>
    <div class="space-y-4">
      <?php if (!empty($avisos)): ?>
        <?php foreach ($avisos as $aviso): ?>
          <div
            class="p-4 border-l-4 border-[#BA007C] dark:bg-[#3e4b5f40] bg-[#D0D0D0]/25 rounded-r-lg hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-2 gap-4">
              <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo esc_html($aviso['titulo']); ?></h3>
              <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"><?php echo esc_html($aviso['data']); ?></span>
            </div>
            <?php if ($aviso['descricao'] !== ''): ?>
              <p class="text-gray-600 dark:text-gray-300 text-sm"><?php echo esc_html($aviso['descricao']); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="rounded-r-lg border-l-4 border-[#BA007C]/40 bg-[#D0D0D0]/15 p-4 text-sm text-gray-600 dark:bg-[#3e4b5f20] dark:text-gray-300">
          Nenhum aviso publicado no momento.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();

get_template_part('template-parts/sidebar-nav', null, [
  'content' => $content,
]);

get_footer();
