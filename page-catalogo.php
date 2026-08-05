<?php
/**
 * Catalogo page template.
 *
 * @package tailpress
 */

get_header();

$materiais = function_exists('tailpress_catalog_material_items_for_portal')
    ? tailpress_catalog_material_items_for_portal()
    : ['videos' => [], 'documentos' => []];
$storefront_link = home_url('/estoque/');
$storefront_helper_text = 'Gerencie suas vitrines no estoque e escolha qual compartilhar com suas clientes.';
$storefront_open_new_tab = false;

if (function_exists('tailpress_storefront_post_type') && function_exists('tailpress_storefront_meta_key') && function_exists('tailpress_storefront_public_url')) {
    $storefront_posts = get_posts([
        'post_type' => tailpress_storefront_post_type(),
        'post_status' => 'publish',
        'numberposts' => 2,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => tailpress_storefront_meta_key('consultora_user_id'),
                'value' => (string) get_current_user_id(),
            ],
        ],
    ]);

    if (count($storefront_posts) === 1 && $storefront_posts[0] instanceof WP_Post) {
        $storefront_link = tailpress_storefront_public_url($storefront_posts[0]->post_name);
        $storefront_helper_text = 'Abra sua vitrine pública e compartilhe o link direto com suas clientes.';
        $storefront_open_new_tab = true;
    }
}

ob_start();
?>

<div class="max-w-7xl mx-auto space-y-6">
  <!-- Header -->
  <div>
    <h1 class="font-display text-4xl text-gray-900 dark:text-white mb-2">
      Catálogo Digital
    </h1>
    <p class="text-gray-600 dark:text-gray-400">Materiais de apoio e conteúdo para suas vendas</p>
  </div>

  <!-- Card Explicativo Rosa -->
  <div class="bg-gradient-to-r from-[#BA007C] to-[#8B1538] rounded-xl p-8 text-white shadow-lg">
    <h2 class="font-display text-2xl mb-4">
      O que é o Catálogo Digital?
    </h2>
    <p class="text-white/95 text-lg leading-relaxed mb-4">
      Este é o seu arsenal completo de vendas! Aqui você encontra todos os materiais que a Lúcia Ramos preparou especialmente para você vender mais e melhor.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
      <a
        href="<?php echo esc_url($storefront_link); ?>"
        class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20 transition hover:bg-white/15 hover:border-white/30 block"
        <?php echo $storefront_open_new_tab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <rect x="3.5" y="5" width="17" height="14" rx="2" stroke-width="1.8"></rect>
          <circle cx="8.5" cy="10" r="1.5" stroke-width="1.8"></circle>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.5 17l5-5 3 3 2-2 3 4"></path>
        </svg>
        <h3 class="font-semibold mb-1">Vitrine Pública</h3>
        <p class="text-sm text-white/90"><?php echo esc_html($storefront_helper_text); ?></p>
      </a>
      <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <rect x="3.5" y="6" width="17" height="12" rx="2" stroke-width="1.8"></rect>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9.5l5 2.5-5 2.5v-5z"></path>
        </svg>
        <h3 class="font-semibold mb-1">Vídeos de Apoio</h3>
        <p class="text-sm text-white/90">Aprenda técnicas de venda</p>
      </div>
      <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7l4 4V20.25H7z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75V8h4"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12h5M9.5 15h5"></path>
        </svg>
        <h3 class="font-semibold mb-1">Documentos Úteis</h3>
        <p class="text-sm text-white/90">Tabelas e guias para clientes</p>
      </div>
    </div>
  </div>

  <?php /* Seção "Fotos de Campanha" oculta temporariamente. */ ?>

  <!-- Vídeos de Treinamento -->
  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <div class="flex items-center gap-3 mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#BA007C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <rect x="3.5" y="6" width="17" height="12" rx="2" stroke-width="1.8"></rect>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9.5l5 2.5-5 2.5v-5z"></path>
      </svg>
      <h2 class="font-display text-2xl text-gray-900 dark:text-white">
        Vídeos de Treinamento
      </h2>
    </div>
    <p class="text-gray-600 dark:text-gray-400 mb-4">Assista aos vídeos e aprenda técnicas de vendas e apresentação de produtos</p>
    <?php if (!empty($materiais['videos'])): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($materiais['videos'] as $video): ?>
          <a
            href="<?php echo esc_url($video['link']); ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-[#BA007C] dark:hover:border-[#BA007C] hover:shadow-md transition-all group bg-white dark:bg-[#0F0F0F]"
          >
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-[#BA007C] transition-colors"><?php echo esc_html($video['nome']); ?></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Duração: <?php echo esc_html($video['duracao']); ?>
                </p>
              </div>
            <div class="p-3 bg-[#BA007C] text-white rounded-lg group-hover:bg-[#8B1538] transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 15 15 9"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 9H15v4.5"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4.5h7.5A3.5 3.5 0 0120 8v7.5A3.5 3.5 0 0116.5 19H8A3.5 3.5 0 014.5 15.5V9A4.5 4.5 0 019 4.5z"></path>
              </svg>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-600 dark:border-gray-700 dark:bg-[#0F0F0F] dark:text-gray-400">
        Nenhum vídeo disponível no momento.
      </div>
    <?php endif; ?>
  </div>

  <!-- Documentos e Guias -->
  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <div class="flex items-center gap-3 mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#BA007C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7l4 4V20.25H7z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75V8h4"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12h5M9.5 15h5"></path>
      </svg>
      <h2 class="font-display text-2xl text-gray-900 dark:text-white">
        Documentos e Guias
      </h2>
    </div>
    <p class="text-gray-600 dark:text-gray-400 mb-4">Baixe tabelas, guias e scripts para facilitar suas vendas</p>
    <?php if (!empty($materiais['documentos'])): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($materiais['documentos'] as $doc): ?>
          <a
            href="<?php echo esc_url($doc['arquivo_url']); ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-[#BA007C] dark:hover:border-[#BA007C] hover:shadow-md transition-all bg-white dark:bg-[#0F0F0F] group"
          >
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-[#BA007C] transition-colors"><?php echo esc_html($doc['nome']); ?></h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <?php echo esc_html($doc['tipo']); ?><?php echo $doc['tamanho'] !== '' ? ' • ' . esc_html($doc['tamanho']) : ''; ?>
              </p>
            </div>
            <span class="p-3 bg-[#BA007C] text-white rounded-lg group-hover:bg-[#8B1538] transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v11.25M8.25 10.5 12 14.25 15.75 10.5"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 15.75V18a2.25 2.25 0 002.25 2.25h10.5A2.25 2.25 0 0019.5 18v-2.25"></path>
              </svg>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-600 dark:border-gray-700 dark:bg-[#0F0F0F] dark:text-gray-400">
        Nenhum documento disponível no momento.
      </div>
    <?php endif; ?>
  </div>

  <!-- Dicas -->
  <div class="bg-[#D4AF37]/10 dark:bg-[#D4AF37]/5 border border-[#D4AF37]/30 dark:border-[#D4AF37]/20 rounded-xl p-6 transition-colors duration-300">
    <h3 class="font-semibold text-lg mb-3 text-gray-900 dark:text-white">💡 Dicas para Usar o Catálogo</h3>
    <ul class="space-y-2 text-gray-700 dark:text-gray-300">
      <!-- <li class="flex items-start gap-2">
        <span class="text-[#BA007C] mt-1">•</span>
        <span>Baixe as fotos e poste diariamente nas suas redes sociais para atrair mais clientes</span>
      </li> -->
      <li class="flex items-start gap-2">
        <span class="text-[#BA007C] mt-1">•</span>
        <span>Assista aos vídeos de treinamento para aprender técnicas comprovadas de vendas</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-[#BA007C] mt-1">•</span>
        <span>Envie a tabela de medidas para suas clientes antes da compra evitar trocas</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-[#BA007C] mt-1">•</span>
        <span>Use o script de vendas como base para suas conversas no WhatsApp</span>
      </li>
    </ul>
  </div>
</div>

<?php
$content = ob_get_clean();

get_template_part('template-parts/sidebar-nav', null, [
    'title' => __('Catalogo', 'tailpress'),
    'content' => $content,
]);

get_footer();
