import Alpine from 'alpinejs';

const formatCurrency = (value) => Number(value || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const fetchJson = async (url, nonce, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'X-WP-Nonce': nonce,
            ...(options.headers || {}),
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload?.message || 'Não foi possível concluir a solicitação.');
    }

    return payload;
};

const registerDashboardPage = (AlpineInstance) => {
    AlpineInstance.data('dashboardPage', (data) => ({
        loading: data?.loading !== false,
        ok: false,
        message: '',
        restUrl: data?.restUrl || '',
        nonce: data?.nonce || '',
        goal: data?.goal || { configured: false, metric: 'pecas', target: 0, source: 'none' },
        produtos: [],
        totais: {
            total_itens: 0,
            total_prevenda: 0,
            valor_estoque: 0,
            valor_prevenda: 0,
            valor_sugerido: 0,
        },

        init() {
            this.loadConsignacao();
        },

        async loadConsignacao() {
            if (!this.restUrl) {
                this.loading = false;
                this.ok = false;
                this.message = 'Integração de consignação indisponível.';
                return;
            }

            this.loading = true;
            this.message = '';

            try {
                const payload = await fetchJson(this.restUrl, this.nonce);

                if (!payload?.ok) {
                    throw new Error(payload?.message || 'Não foi possível carregar o estoque.');
                }

                this.ok = true;
                this.produtos = Array.isArray(payload.produtos) ? payload.produtos : [];
                this.totais = payload.totais || this.totais;
                this.applySuggestedGoalFallback();
            } catch (error) {
                this.ok = false;
                this.produtos = [];
                this.message = error.message || 'Não foi possível carregar o estoque.';
            } finally {
                this.loading = false;
            }
        },

        applySuggestedGoalFallback() {
            if (this.hasGoal) {
                return;
            }

            const suggestedValue = Number(this.totais.valor_sugerido || 0);

            if (suggestedValue > 0) {
                this.goal = { configured: true, metric: 'valor', target: suggestedValue, source: 'valor_sugerido' };
            }
        },

        get pecasEstoque() { return Number(this.totais.total_itens || 0); },
        get pecasDisponiveis() { return this.produtos.reduce((sum, product) => sum + Number(product.disponivel_quantidade || 0), 0); },
        get vendasRealizadas() { return Number(this.totais.total_prevenda || 0); },
        get valorPrevenda() { return Number(this.totais.valor_prevenda || 0); },
        get hasGoal() { return Boolean(this.goal?.configured) && Number(this.goal?.target || 0) > 0; },
        get goalMetric() { return this.goal?.metric === 'valor' ? 'valor' : 'pecas'; },
        get goalTitle() { return this.goalMetric === 'valor' ? 'Meta de Valor' : 'Meta de Peças'; },
        get goalCurrentValue() { return this.goalMetric === 'valor' ? this.valorPrevenda : this.vendasRealizadas; },
        get goalTarget() { return Number(this.goal?.target || 0); },
        get goalProgressPercent() { return this.hasGoal ? Math.min(100, (this.goalCurrentValue / this.goalTarget) * 100) : 0; },
        get goalCurrentLabel() { return this.goalMetric === 'valor' ? `R$ ${formatCurrency(this.goalCurrentValue)}` : String(Math.round(this.goalCurrentValue)); },
        get goalTargetLabel() { return this.goalMetric === 'valor' ? `R$ ${formatCurrency(this.goalTarget)}` : String(Math.round(this.goalTarget)); },
        get goalProgressLabel() {
            if (!this.hasGoal) return 'Meta não definida';
            return this.goalMetric === 'valor'
                ? `R$ ${formatCurrency(this.goalCurrentValue)} / R$ ${formatCurrency(this.goalTarget)}`
                : `${Math.round(this.goalCurrentValue)} / ${Math.round(this.goalTarget)}`;
        },
        get produtosDestaque() { return this.produtos.filter((product) => Number(product.disponivel_quantidade || 0) > 0).slice(0, 4); },
        formatCurrency,
    }));
};

const registerEstoquePage = (AlpineInstance) => {
    AlpineInstance.data('estoquePage', (data) => ({
        ok: Boolean(data?.ok),
        loading: data?.loading !== false,
        message: data?.message || '',
        revendedora: data?.revendedora || {},
        restUrl: data?.restUrl || '',
        nonce: data?.nonce || '',
        busca: '',
        unidadeFiltro: 'todos',
        produtos: Array.isArray(data?.produtos) ? data.produtos : [],
        unidades: Array.isArray(data?.unidades) ? data.unidades : ['todos'],
        vitrines: Array.isArray(data?.vitrines) ? data.vitrines : [],
        copiedVitrine: '',

        init() { this.loadConsignacao(); },

        async loadConsignacao() {
            if (!this.restUrl) {
                this.loading = false;
                this.ok = false;
                this.message = 'Integração de consignação indisponível.';
                return;
            }

            this.loading = true;
            this.message = '';

            try {
                const payload = await fetchJson(this.restUrl, this.nonce);

                if (!payload?.ok) throw new Error(payload?.message || 'Não foi possível carregar o estoque.');

                this.ok = true;
                this.revendedora = payload.revendedora || {};
                this.produtos = Array.isArray(payload.produtos) ? payload.produtos : [];
                this.unidades = Array.isArray(payload.unidades) ? payload.unidades : ['todos'];
                this.vitrines = Array.isArray(payload.vitrines) ? payload.vitrines : [];
            } catch (error) {
                this.ok = false;
                this.produtos = [];
                this.unidades = ['todos'];
                this.vitrines = [];
                this.message = error.message || 'Não foi possível carregar o estoque.';
            } finally {
                this.loading = false;
            }
        },

        async copyVitrine(vitrine) {
            if (!vitrine?.url) return;

            try {
                await navigator.clipboard.writeText(vitrine.url);
                this.copiedVitrine = vitrine.consignacao_id;
                window.setTimeout(() => {
                    if (this.copiedVitrine === vitrine.consignacao_id) this.copiedVitrine = '';
                }, 2200);
            } catch {
                window.prompt('Copie o link da vitrine:', vitrine.url);
            }
        },

        normalize(value) { return String(value || '').toLowerCase(); },
        get produtosFiltrados() {
            return this.produtos.filter((product) => {
                const term = this.normalize(this.busca);
                const matchesTerm = this.normalize(product.nome).includes(term)
                    || this.normalize(product.codigo).includes(term)
                    || this.normalize(product.tamanho).includes(term)
                    || this.normalize(product.unidade).includes(term);
                return matchesTerm && (this.unidadeFiltro === 'todos' || product.unidade === this.unidadeFiltro);
            });
        },
        get totalPecas() { return this.produtosFiltrados.reduce((sum, product) => sum + Number(product.quantidade || 0), 0); },
        get totalPrevendas() { return this.produtosFiltrados.reduce((sum, product) => sum + Number(product.prevenda_quantidade || 0), 0); },
        get valorTotalEstoque() { return this.produtosFiltrados.reduce((sum, product) => sum + (Number(product.quantidade || 0) * Number(product.valor_unitario || 0)), 0); },
        formatCurrency,
    }));
};

const registerVendasPage = (AlpineInstance) => {
    AlpineInstance.data('vendasPage', (data) => ({
        ok: Boolean(data?.ok),
        loading: data?.loading !== false,
        message: data?.message || '',
        revendedora: data?.revendedora || {},
        itens: Array.isArray(data?.itens) ? data.itens : [],
        consignacaoUrl: data?.consignacaoUrl || '',
        restUrl: data?.restUrl || '',
        nonce: data?.nonce || '',
        busca: '',
        statusFiltro: 'todos',
        submitting: {},
        errorMessage: '',
        successMessage: '',

        init() { this.loadConsignacao(); },

        async loadConsignacao() {
            if (!this.consignacaoUrl) {
                this.loading = false;
                this.ok = false;
                this.message = 'Integração de consignação indisponível.';
                return;
            }

            this.loading = true;
            this.message = '';
            this.errorMessage = '';

            try {
                const payload = await fetchJson(this.consignacaoUrl, this.nonce);

                if (!payload?.ok) throw new Error(payload?.message || 'Não foi possível carregar as pré-vendas.');

                this.ok = true;
                this.revendedora = payload.revendedora || {};
                this.itens = Array.isArray(payload.itens) ? payload.itens : [];
            } catch (error) {
                this.ok = false;
                this.itens = [];
                this.message = error.message || 'Não foi possível carregar as pré-vendas.';
            } finally {
                this.loading = false;
            }
        },

        normalize(value) { return String(value || '').toLowerCase(); },
        get itensFiltrados() {
            return this.itens.filter((item) => {
                const term = this.normalize(this.busca);
                const matchesTerm = this.normalize(item.descricao).includes(term)
                    || this.normalize(item.tamanho).includes(term)
                    || this.normalize(item.cons_item_id).includes(term)
                    || this.normalize(item.produto_id).includes(term);
                return matchesTerm && (this.statusFiltro === 'todos' || item.pre_venda === this.statusFiltro);
            });
        },
        get totalPrevendas() { return this.itens.filter((item) => item.pre_venda === 'S').length; },
        get valorPrevenda() { return this.itens.filter((item) => item.pre_venda === 'S').reduce((sum, item) => sum + Number(item.valor_unitario || 0), 0); },
        formatCurrency,
        isSubmitting(item) { return Boolean(this.submitting[item.cons_item_id]); },

        async togglePreVenda(item) {
            const nextStatus = item.pre_venda === 'S' ? 'N' : 'S';
            const previousStatus = item.pre_venda;
            this.errorMessage = '';
            this.successMessage = '';
            this.submitting[item.cons_item_id] = true;

            try {
                await fetchJson(this.restUrl, this.nonce, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cons_item_id: item.cons_item_id, pre_venda: nextStatus }),
                });
                item.pre_venda = nextStatus;
                item.data_prevenda = nextStatus === 'S' ? new Date().toISOString().slice(0, 10) : null;
                this.successMessage = nextStatus === 'S' ? 'Item marcado como pré-venda.' : 'Pré-venda removida do item.';
            } catch (error) {
                item.pre_venda = previousStatus;
                this.errorMessage = error.message || 'Não foi possível atualizar a pré-venda.';
            } finally {
                this.submitting[item.cons_item_id] = false;
            }
        },
    }));
};

const registerPremiacoesPage = (AlpineInstance) => {
    AlpineInstance.data('premiacoesPage', (data) => ({
        loading: true,
        campaigns: [],
        filter: 'todos',
        restUrl: data?.restUrl || '',
        acceptUrl: data?.acceptUrl || '',
        nonce: data?.nonce || '',
        expanded: {},
        submitting: {},
        errorMessage: '',

        init() { this.loadCampaigns(); },

        async loadCampaigns() {
            this.loading = true;
            this.errorMessage = '';

            try {
                const payload = await fetchJson(this.restUrl, this.nonce);
                this.campaigns = Array.isArray(payload?.campaigns) ? payload.campaigns : [];
            } catch (error) {
                this.campaigns = [];
                this.errorMessage = error.message || 'Não foi possível carregar as premiações.';
            } finally {
                this.loading = false;
            }
        },

        get visibleCampaigns() {
            if (this.filter === 'todos') return this.campaigns.filter((item) => !this.isInactive(item));
            if (this.filter === 'encerrado') return this.campaigns.filter((item) => ['encerrado', 'ganhador'].includes(item.status));
            return this.campaigns.filter((item) => item.status === this.filter);
        },
        get openCount() { return this.campaigns.filter((item) => item.status === 'aberto').length; },
        get participatingCount() { return this.campaigns.filter((item) => item.status === 'participando').length; },
        isInactive(item) { return ['encerrado', 'ganhador'].includes(item.status); },
        isParticipating(item) { return item.status === 'participando'; },
        isExpanded(item) { return Boolean(this.expanded[item.id]); },
        toggleRules(item) { this.expanded[item.id] = !this.expanded[item.id]; },
        isSubmitting(item) { return Boolean(this.submitting[item.id]); },
        progressPercent(item) {
            const goal = Number(item.goalValue || 0);
            return goal > 0 ? Math.min(100, (Number(item.progress || 0) / goal) * 100) : 0;
        },
        progressLabel(item, value) {
            if (item.goalMetric === 'valor') return `R$ ${formatCurrency(value)}`;
            return String(Math.round(Number(value || 0)));
        },

        async accept(item) {
            if (this.isSubmitting(item) || item.status !== 'aberto') return;

            this.errorMessage = '';
            this.submitting[item.id] = true;

            try {
                const payload = await fetchJson(this.acceptUrl, this.nonce, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ premiacao_id: item.id }),
                });
                this.campaigns = Array.isArray(payload?.campaigns) ? payload.campaigns : this.campaigns;
            } catch (error) {
                this.errorMessage = error.message || 'Não foi possível registrar sua participação.';
            } finally {
                this.submitting[item.id] = false;
            }
        },
    }));
};

const themeStorageKey = 'lucia-theme';
const forcedTheme = () => document.documentElement.dataset.luciaForcedTheme || '';
const savedTheme = () => {
    const forced = forcedTheme();
    if (forced === 'dark' || forced === 'light') return forced;

    try {
        const saved = localStorage.getItem(themeStorageKey);
        if (saved === 'dark' || saved === 'light') return saved;
    } catch {
        // Local storage can be unavailable in private browsing contexts.
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyTheme = (mode) => document.documentElement.classList.toggle('dark', mode === 'dark');

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerDashboardPage(Alpine);
    registerEstoquePage(Alpine);
    registerVendasPage(Alpine);
    registerPremiacoesPage(Alpine);

    Alpine.store('theme', {
        mode: 'light',
        set(mode) {
            this.mode = mode === 'dark' ? 'dark' : 'light';
            this.apply();
        },
        toggle() { this.set(this.mode === 'dark' ? 'light' : 'dark'); },
        apply() {
            const forced = forcedTheme();
            applyTheme(forced || this.mode);
            try {
                if (!forced) localStorage.setItem(themeStorageKey, this.mode);
            } catch {
                // The selected theme remains active for this page.
            }
        },
    });

    Alpine.store('theme').set(savedTheme());
});

Alpine.start();

window.addEventListener('load', () => {
    const navigation = document.getElementById('primary-navigation');
    const toggle = document.getElementById('primary-menu-toggle');

    if (navigation && toggle) {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            navigation.classList.toggle('hidden');
        });
    }
});
