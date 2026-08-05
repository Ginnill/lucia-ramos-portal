<?php
/**
 * Entrar page template.
 *
 * @package tailpress
 */

if (is_user_logged_in()) {
    wp_safe_redirect(tailpress_user_is_consultora() ? home_url('/') : admin_url());
    exit;
}

$logo_light_url = get_theme_file_uri('img/logo-rosa-lucia-ramos.png');

$login_status = isset($_GET['login']) ? sanitize_key(wp_unslash($_GET['login'])) : '';

get_header();
?>

<div class="min-h-screen bg-light flex items-center justify-center p-6 transition-colors duration-500 relative overflow-hidden">
  <!-- Padrao de fundo sutil -->
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(186,0,124,0.04),transparent_50%)]"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(212,175,55,0.04),transparent_50%)]"></div>

  <!-- Container do Login -->
  <div class="w-full max-w-110 relative z-10">
    <!-- Logo/Header -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center justify-center mb-6 relative">
        <div class="absolute inset-0 bg-linear-to-r from-[#BA007C]/20 to-[#D4AF37]/20 blur-2xl opacity-60"></div>
        <img src="<?php echo esc_url($logo_light_url); ?>" alt="Lucia Ramos" class="h-14 w-auto relative z-10" />
      </div>
      <p class="text-muted tracking-wide uppercase text-xs font-medium">Portal de Revendedores</p>
    </div>

    <!-- Card de Login -->
    <div class="bg-light backdrop-blur-xl rounded-3xl p-10 border border-border relative transition-colors duration-500 shadow-[0_8px_32px_-8px_rgba(0,0,0,0.08)]">
      <!-- Borda gradiente sutil -->
      <div class="absolute inset-0 rounded-3xl bg-linear-to-br from-primary/15 via-transparent to-secondary/15 opacity-60 pointer-events-none"></div>

      <div class="relative z-10" x-data="{ showPassword: false, loading: false }">
        <div class="mb-8">
          <h2 class="text-3xl font-light text-dark mb-2 tracking-tight">
            Bem-vindo
          </h2>
          <p class="text-muted">
            Acesse sua conta para continuar
          </p>
        </div>

        <?php if ($login_status === 'failed'): ?>
          <div class="mb-6 rounded-xl border border-primary/30 bg-primary/10 px-4 py-3 text-sm text-primary">
            Usuario ou senha incorretos.
          </div>
        <?php elseif ($login_status === 'forbidden'): ?>
          <div class="mb-6 rounded-xl border border-secondary/30 bg-secondary/10 px-4 py-3 text-sm text-dark">
            Acesso permitido apenas para consultoras.
          </div>
        <?php endif; ?>

        <form id="consultora-login-form" method="post" action="<?php echo esc_url(wp_login_url(home_url('/'))); ?>" class="space-y-6" @submit="loading = true">
          <input type="hidden" name="lucia_portal_login" value="1" />
          <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/')); ?>" />

          <!-- Campo Usuario -->
          <div>
            <label
              for="usuario"
              class="block text-xs font-medium text-muted mb-3 uppercase tracking-wider"
            >
              Usuario
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-4.5 w-4.5 text-slate transition-colors group-focus-within:text-primary">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.5 20.25a7.5 7.5 0 0115 0"></path>
                </svg>
              </div>
              <input
                id="usuario"
                name="log"
                type="text"
                placeholder="seu.usuario"
                required
                class="block w-full pl-11 pr-4 py-3.5 border border-border rounded-xl bg-light text-dark placeholder-slate focus:outline-none focus:border-primary focus:bg-light transition-all duration-300"
              />
            </div>
          </div>

          <!-- Campo Senha -->
          <div>
            <label
              for="senha"
              class="block text-xs font-medium text-muted mb-3 uppercase tracking-wider"
            >
              Senha
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-4.5 w-4.5 text-slate transition-colors group-focus-within:text-primary">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5a.75.75 0 01.75.75v8.25a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75v-8.25a.75.75 0 01.75-.75z"></path>
                </svg>
              </div>
              <input
                id="senha"
                name="pwd"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                required
                class="block w-full pl-11 pr-12 py-3.5 border border-border rounded-xl bg-light text-dark placeholder-slate focus:outline-none focus:border-primary focus:bg-light transition-all duration-300"
              />
              <button
                id="toggle-senha"
                type="button"
                @click="showPassword = !showPassword"
                class="absolute cursor-pointer inset-y-0 right-0 pr-4 flex items-center text-slate hover:text-primary transition-colors duration-300"
              >
                <svg id="icon-eye" x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-4.5 w-4.5">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15a3 3 0 100-6 3 3 0 000 6z"></path>
                </svg>
                <svg id="icon-eye-off" x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-4.5 w-4.5">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3l18 18"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.58 10.58A3 3 0 0013.42 13.42"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.88 5.09A10.92 10.92 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a18.19 18.19 0 01-3.16 4.19M6.1 6.1A18.6 18.6 0 002.25 12s3.75 7.5 9.75 7.5a10.9 10.9 0 005.09-1.27"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Link Esqueci a Senha -->
          <div class="flex items-center justify-end">
            <a
              href="<?php echo esc_url(wp_lostpassword_url()); ?>"
              class="text-sm text-muted hover:text-primary transition-colors duration-300 cursor-pointer"
            >
              Esqueci minha senha
            </a>
          </div>

          <!-- Botao de Login -->
          <button
            id="submit-login"
            type="submit"
            :disabled="loading"
            class="w-full bg-linear-to-r from-primary via-primary to-primary bg-size-200 bg-pos-0 hover:bg-pos-100 text-white font-medium py-4 px-6 rounded-xl transition-all duration-500 disabled:opacity-50 disabled:cursor-not-allowed mt-8 relative overflow-hidden group cursor-pointer"
          >
            <div class="absolute inset-0 bg-linear-to-r from-secondary/0 via-secondary/20 to-secondary/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <span id="login-loading" x-show="loading" x-cloak class="flex items-center justify-center relative z-10">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Entrando...
            </span>
            <span id="login-label" x-show="!loading" class="relative z-10">Entrar</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-8">
      <p class="text-xs text-slate">
        &copy; <?php echo esc_html(date_i18n('Y')); ?> Consultoria Lucia Ramos. Todos os direitos reservados.
      </p>
    </div>
  </div>
</div>

<?php
get_footer();
