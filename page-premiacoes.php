<?php
/**
 * Premiações page template.
 *
 * @package tailpress
 */

get_header();

$premiacoes_data = [
    'restUrl' => rest_url('lucia-portal/v1/premiacoes'),
    'acceptUrl' => rest_url('lucia-portal/v1/premiacoes/participar'),
    'nonce' => wp_create_nonce('wp_rest'),
];

ob_start();
?>

<script>
window.luciaPremiacoesData = <?php echo wp_json_encode($premiacoes_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div x-data="premiacoesPage(window.luciaPremiacoesData)" class="max-w-3xl mx-auto space-y-8">
    <div>
        <h1 class="font-display text-4xl text-gray-900 dark:text-white mb-1">Premiações</h1>
        <p class="text-gray-400 dark:text-gray-500 text-sm">Sorteios e bonificações exclusivos para consultoras Lúcia Ramos</p>
    </div>

    <div x-show="loading" x-cloak class="space-y-5" aria-live="polite">
        <div class="flex gap-6">
            <div class="h-12 w-24 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
            <div class="h-12 w-24 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
            <div class="h-12 w-24 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
        </div>
        <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
        <template x-for="index in 2" :key="index">
            <div class="h-72 rounded-2xl bg-white dark:bg-[#1A1A1A] border border-gray-100 dark:border-gray-800 animate-pulse"></div>
        </template>
    </div>

    <div x-show="!loading" x-cloak class="space-y-8">
        <div x-show="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
            <span x-text="errorMessage"></span>
            <button type="button" class="ml-2 font-semibold underline" x-on:click="loadCampaigns()">Tentar novamente</button>
        </div>

        <div class="flex flex-wrap gap-6" x-show="!errorMessage || campaigns.length">
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="openCount"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">campanhas abertas</p>
            </div>
            <div class="w-px bg-gray-100 dark:bg-gray-800"></div>
            <div>
                <p class="text-2xl font-bold text-[#BA007C]" x-text="participatingCount"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">você está participando</p>
            </div>
            <div class="w-px bg-gray-100 dark:bg-gray-800"></div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="campaigns.length"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">campanhas no total</p>
            </div>
        </div>

        <div class="flex gap-1 border-b border-gray-100 dark:border-gray-800 overflow-x-auto overflow-y-hidden" x-show="campaigns.length">
            <template x-for="tab in [{key:'todos',label:'Todas'},{key:'aberto',label:'Disponíveis'},{key:'participando',label:'Participando'},{key:'encerrado',label:'Encerradas'}]" :key="tab.key">
                <button
                    type="button"
                    x-on:click="filter = tab.key"
                    class="flex-1 text-center py-2.5 px-2 text-sm font-medium transition-all duration-200 border-b-2 -mb-px whitespace-nowrap"
                    :class="filter === tab.key ? 'border-[#BA007C] text-[#BA007C]' : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200'"
                    x-text="tab.label"
                ></button>
            </template>
        </div>

        <div class="space-y-3">
            <template x-if="!errorMessage && campaigns.length === 0">
                <div class="text-center py-16">
                    <svg class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 4.5h7.5M9 4.5v1.125a3 3 0 006 0V4.5m-6 0H5.625A1.125 1.125 0 004.5 5.625v.75A4.125 4.125 0 008.625 10.5M15 4.5h3.375A1.125 1.125 0 0119.5 5.625v.75a4.125 4.125 0 01-4.125 4.125M12 9v6m-3.75 4.5h7.5"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Nenhuma campanha publicada no momento.</p>
                </div>
            </template>

            <template x-if="campaigns.length && visibleCampaigns.length === 0">
                <div class="text-center py-16">
                    <svg class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 4.5h7.5M9 4.5v1.125a3 3 0 006 0V4.5m-6 0H5.625A1.125 1.125 0 004.5 5.625v.75A4.125 4.125 0 008.625 10.5M15 4.5h3.375A1.125 1.125 0 0119.5 5.625v.75a4.125 4.125 0 01-4.125 4.125"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Nenhuma campanha nesta categoria.</p>
                </div>
            </template>

            <template x-for="item in visibleCampaigns" :key="item.id">
                <article
                    class="bg-white dark:bg-[#1A1A1A] rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden transition-all duration-300"
                    :class="isInactive(item) ? 'opacity-60' : 'hover:border-gray-200 dark:hover:border-gray-700 hover:shadow-md'"
                >
                    <div class="flex">
                        <div class="w-1 shrink-0" :class="isInactive(item) ? 'bg-gray-200 dark:bg-gray-700' : (isParticipating(item) ? 'bg-[#BA007C]' : 'bg-[#D4AF37]')"></div>
                        <div class="flex-1 p-5 sm:p-6 min-w-0">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider" x-text="item.type === 'sorteio' ? 'Sorteio' : 'Bonificação'"></span>
                                        <span class="text-gray-200 dark:text-gray-700">·</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="item.endDate"></span>
                                        </span>
                                    </div>
                                    <h2 class="font-display text-xl font-bold text-gray-900 dark:text-white leading-snug" x-text="item.title"></h2>
                                </div>
                                <svg x-show="item.status === 'ganhador'" class="w-5 h-5 text-[#D4AF37] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8.25 4.5h7.5M9 4.5v1.125a3 3 0 006 0V4.5m-6 0H5.625A1.125 1.125 0 004.5 5.625v.75A4.125 4.125 0 008.625 10.5M15 4.5h3.375A1.125 1.125 0 0119.5 5.625v.75a4.125 4.125 0 01-4.125 4.125M12 9v6m-3.75 4.5h7.5"/></svg>
                                <svg x-show="isParticipating(item)" class="w-5 h-5 text-[#BA007C] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75l2.25 2.25L15 9.75m6 2.25a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>

                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-5" x-text="item.description"></p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                                <div class="border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Prêmio</p>
                                    <template x-if="!item.prizes.length"><p class="text-sm font-semibold text-gray-800 dark:text-white">A definir</p></template>
                                    <div class="space-y-1">
                                        <template x-for="(prize, prizeIndex) in item.prizes" :key="prizeIndex">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                                <span x-show="prize.position" x-text="prize.position + ': '"></span><span x-text="prize.description"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                                <div class="border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Meta</p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white" x-text="item.goalLabel || 'Consulte as regras da campanha'"></p>
                                </div>
                            </div>

                            <div x-show="isParticipating(item) && !isInactive(item) && item.goalMetric !== 'nenhum' && Number(item.goalValue) > 0" class="mt-4">
                                <div class="flex justify-between gap-3 text-xs text-gray-400 dark:text-gray-500 mb-2">
                                    <span>Seu progresso</span>
                                    <span class="font-medium text-gray-600 dark:text-gray-300 text-right">
                                        <span x-text="progressLabel(item, item.progress)"></span> de <span x-text="progressLabel(item, item.goalValue)"></span>
                                    </span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700 bg-[#BA007C]" :style="`width: ${progressPercent(item)}%`"></div>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5" x-text="`${progressPercent(item).toFixed(0)}% concluído`"></p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-50 dark:border-gray-800 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex flex-wrap items-center gap-4">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.941 3.199v.75c0 .414-.336.75-.75.75H6.75a.75.75 0 01-.75-.75v-.75m12 0a5.971 5.971 0 00-.941-3.199m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.059 2.771m10.118 0a3 3 0 00-4.682 2.72M12 12.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"/></svg>
                                        <span x-text="`${item.participants} ${Number(item.participants) === 1 ? 'participante' : 'participantes'}`"></span>
                                    </div>
                                    <button x-show="item.rules.length" type="button" x-on:click="toggleRules(item)" class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5 transition-transform" :class="isExpanded(item) ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 8.25L12 15.75 4.5 8.25"/></svg>
                                        <span x-text="isExpanded(item) ? 'Ocultar regras' : 'Ver regras'"></span>
                                    </button>
                                </div>

                                <span x-show="isInactive(item)" class="text-xs text-gray-400 dark:text-gray-500 self-start sm:self-auto" x-text="item.status === 'ganhador' ? 'Encerrada com ganhador' : 'Inscrições encerradas'"></span>
                                <span x-show="isParticipating(item)" class="text-xs font-semibold text-[#BA007C] flex items-center gap-1.5 self-start sm:self-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75l2.25 2.25L15 9.75m6 2.25a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Você está participando
                                </span>
                                <button
                                    x-show="item.status === 'aberto'"
                                    type="button"
                                    x-on:click="accept(item)"
                                    :disabled="isSubmitting(item)"
                                    class="flex items-center justify-center cursor-pointer gap-1.5 text-sm font-semibold text-white bg-[#BA007C] hover:bg-[#8B1538] disabled:opacity-60 disabled:cursor-wait px-5 py-2.5 rounded-lg transition-all duration-200 active:scale-95 w-full sm:w-auto"
                                >
                                    <span x-text="isSubmitting(item) ? 'Registrando...' : 'Aceitar e participar'"></span>
                                    <svg x-show="!isSubmitting(item)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                </button>
                            </div>

                            <ul x-show="isExpanded(item)" class="mt-4 space-y-2.5 pt-4 border-t border-gray-50 dark:border-gray-800">
                                <template x-for="(rule, ruleIndex) in item.rules" :key="ruleIndex">
                                    <li class="flex items-start gap-3 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="text-xs font-semibold text-gray-300 dark:text-gray-600 mt-0.5 w-4 shrink-0" x-text="`${ruleIndex + 1}.`"></span>
                                        <span x-text="rule"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

get_template_part('template-parts/sidebar-nav', null, ['content' => $content]);

get_footer();
