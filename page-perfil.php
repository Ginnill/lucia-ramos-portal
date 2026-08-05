<?php
/**
 * Perfil page template.
 *
 * @package tailpress
 */

get_header();

$perfil_data = function_exists('tailpress_profile_frontend_payload')
    ? tailpress_profile_frontend_payload()
    : [];

ob_start();
?>

<div x-data='perfilPage(<?php echo esc_attr(wp_json_encode($perfil_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>)' class="max-w-4xl mx-auto space-y-6">
  <!-- Header -->
  <div>
    <h1 class="font-display text-4xl text-gray-900 dark:text-white mb-2">
      Meu Perfil
    </h1>
    <p class="text-gray-600 dark:text-gray-400">Consulte seus dados cadastrados e de acesso</p>
  </div>

  <template x-if="emailStatus === 'confirmed'">
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
      E-mail confirmado com sucesso.
    </div>
  </template>

  <div class="rounded-xl border border-[#D4AF37]/40 bg-[#D4AF37]/10 px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
    Seus dados são sincronizados automaticamente pelo sistema e não podem ser alterados nesta tela.
  </div>

  <!-- Avatar e Info Rápida -->
  <div class="bg-gradient-to-r from-[#BA007C] to-[#8B1538] rounded-xl p-8 text-white shadow-lg">
    <div class="flex items-center gap-6">
      <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 20.25a7.5 7.5 0 0115 0"></path>
        </svg>
      </div>
      <div class="min-w-0">
        <h2 class="font-display text-3xl mb-1 break-words" x-text="dadosPerfil.nome || 'Consultora'"></h2>
        <p class="text-white/90">Consultora Lúcia Ramos</p>
        <p class="text-white/80 text-sm mt-2" x-text="dadosPerfil.membroDesde"></p>
      </div>
    </div>
  </div>

  <!-- Dados Cadastrais -->
  <div class="bg-white dark:bg-[#1A1A1A] rounded-xl p-6 shadow-lg transition-colors duration-300">
    <div class="flex items-center gap-3 mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#BA007C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 20.25a7.5 7.5 0 0115 0"></path>
      </svg>
      <h2 class="font-display text-2xl text-gray-900 dark:text-white">
        Dados Cadastrais
      </h2>
    </div>

    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Nome Completo
          </label>
          <input
            type="text"
            x-model="dadosPerfil.nome"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            CPF
          </label>
          <input
            type="text"
            x-model="dadosPerfil.cpf"
            readonly
            placeholder="CPF cadastrado pelo administrativo"
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            E-mail atual
          </label>
          <input
            type="email"
            x-model="dadosPerfil.email"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            E-mail
          </label>
          <input
            type="email"
            x-model="dadosPerfil.email"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>
      </div>

      <template x-if="emailPendente.email">
        <div class="rounded-xl border border-[#D4AF37]/40 bg-[#D4AF37]/10 px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
          Confirmação pendente para <strong x-text="emailPendente.email"></strong>. Verifique a caixa de entrada desse e-mail.
        </div>
      </template>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          WhatsApp
        </label>
        <input
          type="tel"
          x-model="dadosPerfil.whatsapp"
          readonly
          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Endereço
        </label>
        <input
          type="text"
          x-model="dadosPerfil.endereco"
          readonly
          class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Cidade
          </label>
          <input
            type="text"
            x-model="dadosPerfil.cidade"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Estado
          </label>
          <input
            type="text"
            x-model="dadosPerfil.estado"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            CEP
          </label>
          <input
            type="text"
            x-model="dadosPerfil.cep"
            readonly
            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#111827] text-gray-700 dark:text-gray-300 rounded-lg cursor-not-allowed transition-colors duration-300"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- Informações de Segurança -->
  <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
    <h3 class="font-semibold text-lg mb-3 text-blue-900">Dicas de Segurança</h3>
    <ul class="space-y-2 text-blue-800 text-sm">
      <li class="flex items-start gap-2">
        <span class="text-blue-600 mt-1">•</span>
        <span>Nunca compartilhe sua senha com outras pessoas</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-blue-600 mt-1">•</span>
        <span>Use o link "Esqueci minha senha" na tela de login se precisar redefinir o acesso</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-blue-600 mt-1">•</span>
        <span>Se algum dado cadastral estiver incorreto, solicite ajuste ao time responsável pela integração</span>
      </li>
    </ul>
  </div>
</div>

<script>
if (!window.perfilPage) {
  window.perfilPage = (data) => ({
    emailStatus: data?.emailStatus || '',
    dadosPerfil: data?.dadosPerfil || {
      nome: '',
      email: '',
      cpf: '',
      whatsapp: '',
      membroDesde: '',
      endereco: '',
      cidade: '',
      estado: '',
      cep: '',
    },
    emailPendente: data?.emailPendente || {
      email: '',
      createdAt: 0,
    },
  });
}
</script>

<?php
$content = ob_get_clean();

get_template_part('template-parts/sidebar-nav', null, [
    'title' => __('Perfil', 'tailpress'),
    'content' => $content,
]);

get_footer();
