/**
 * Cadastro de cliente em etapas — mobile first.
 * Preserva o fluxo atual (máscaras, nas validações, uploads, CEP, resumo)
 * e adiciona feedback por campo, barra de progresso mobile e estados de loading.
 */
export const initClientRegistration = () => {
    const form = document.getElementById('registration-form');

    if (!form) {
        return;
    }

    // Configuração do servidor em bloco de dados (CSP-safe). Mantém o fallback
    // para window.registrationForm apenas por compatibilidade.
    const readConfig = () => {
        const el = document.getElementById('registration-form-config');

        if (el) {
            try {
                return JSON.parse(el.textContent) ?? {};
            } catch {
                return {};
            }
        }

        return window.registrationForm ?? {};
    };

    const config = readConfig();
    const totalSteps = Number(config.totalSteps ?? 6);
    const reviewStep = totalSteps - 1;
    const cepLookupUrl = config.cepLookupUrl ?? '/cep';
    const successUrl = config.successUrl ?? null;
    const fieldToStep = config.fieldToStep ?? {};

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Reajusta o tamanho do canvas de assinatura quando o painel da etapa final
    // fica visível (a largura só está disponível com o layout renderizado).
    let resizeSignatureCanvas = () => {};

    // A partir de 768px o formulário passa a ser uma página única onde todas as
    // seções ficam visíveis simultaneamente. A navegação por etapas (wizard)
    // continua exclusiva do mobile.
    const isDesktopLayout = () => window.matchMedia('(min-width: 768px)').matches;

    let currentStep = 1;

    // Erros vindos do servidor (após um envio rejeitado) agrupados por etapa.
    // Só servem como "sugestão inicial" de correção; são limpos assim que o
    // usuário revisa a etapa correspondente sem mais erros locais.
    let serverErrorSteps = { ...(config.serverErrorSteps ?? {}) };

    // Mensagens reais por campo (primeira de cada campo). Preservam o texto do
    // servidor (ex.: "Informe uma data de validade futura.") sobre o genérico.
    let serverErrors = { ...(config.serverFieldErrors ?? {}) };

    // Recalcula o agrupamento por etapa a partir das mensagens por campo.
    const rebuildServerErrorSteps = () => {
        serverErrorSteps = {};

        for (const field of Object.keys(serverErrors)) {
            const step = fieldToStep[field] ?? 4;
            serverErrorSteps[step] = (serverErrorSteps[step] ?? 0) + 1;
        }
    };

    // Remove as mensagens do servidor da etapa informada (passou a ser validada).
    const clearStepServerErrors = (step) => {
        for (const field of Object.keys(serverErrors)) {
            if ((fieldToStep[field] ?? 4) === step) {
                delete serverErrors[field];
            }
        }

        rebuildServerErrorSteps();
    };

    // Primeiro campo da etapa com erro do servidor (mensagem por campo).
    const serverFieldOfStep = (step) =>
        Object.keys(serverErrors).find((field) => (fieldToStep[field] ?? 4) === step) ?? null;

    // Modo de correção: o usuário veio da revisão para ajustar uma etapa e o
    // botão principal passa a ser "Revisar cadastro" (leva para a última etapa,
    // Contrato e assinatura).
    let correctionMode = false;

    const dot = (step) => document.querySelector(`.step-dot[data-step="${step}"]`);
    const label = (step) => document.querySelector(`.step-label[data-step="${step}"]`);
    const panel = (step) => document.querySelector(`.step-panel[data-panel="${step}"]`);

    const stepCountEl = document.getElementById('step-count');
    const stepTitleEl = document.getElementById('step-title');
    const stepbarFill = document.getElementById('stepbar-fill');
    const stepProgressBar = document.getElementById('step-progress');
    const stepCounterNav = document.getElementById('step-counter');

    const updateStepHeader = (step) => {
        if (stepCountEl) stepCountEl.textContent = `Etapa ${step} de ${totalSteps}`;
        if (stepTitleEl && panel(step)) {
            stepTitleEl.textContent = panel(step).dataset.title || `Etapa ${step}`;
        }
        if (stepbarFill) stepbarFill.style.width = `${(step / totalSteps) * 100}%`;
        if (stepProgressBar) {
            stepProgressBar.setAttribute('aria-valuemax', String(totalSteps));
            stepProgressBar.setAttribute('aria-valuenow', String(step));
        }
        if (stepCounterNav) stepCounterNav.textContent = `${step} / ${totalSteps}`;
    };

    const gotoStep = (step) => {
        if (step < 1 || step > totalSteps) {
            return;
        }

        currentStep = step;

        // No desktop (página única) todas as seções ficam visíveis por CSS; o
        // controle de visibilidade por classe é aplicado somente no wizard mobile.
        if (!isDesktopLayout()) {
            document.querySelectorAll('.step-panel').forEach((el, index) => {
                el.classList.toggle('hidden', index + 1 !== currentStep);
            });
        }

        for (let i = 1; i <= totalSteps; i++) {
            const el = dot(i);
            el.dataset.active = String(i === currentStep);
            el.dataset.done = String(i < currentStep);
            el.textContent = i < currentStep ? '✓' : String(i);

            const lbl = label(i);
            if (lbl) {
                lbl.dataset.active = String(i === currentStep);
                lbl.dataset.done = String(i < currentStep);
            }
        }

        updateStepHeader(currentStep);
        updateNavigation();

        if (currentStep === reviewStep) {
            renderReviewProblems();
        }

        if (currentStep === totalSteps) {
            resizeSignatureCanvas();
        }

        if (!isDesktopLayout()) {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
    };

    const nextLabelEl = document.getElementById('next-btn')?.querySelector('[data-next-label]');

    const updateNavigation = () => {
        document.getElementById('prev-btn').classList.toggle('invisible', currentStep === 1);
        document.getElementById('next-btn').classList.toggle('invisible', currentStep === totalSteps);

        if (nextLabelEl) {
            const inCorrection = correctionMode && currentStep !== reviewStep;
            nextLabelEl.textContent = inCorrection ? 'Revisar cadastro' : 'Continuar';
        }
    };

    /* ---------- Revisão com problemas agrupados por etapa ---------- */
    const reviewProblemsEl = document.getElementById('review-problems');
    const reviewProblemsIntro = document.getElementById('review-problems-intro')?.querySelector('[data-review-problems-intro-text]');
    const reviewProblemsList = document.querySelector('[data-review-problems-list]');

    const pluralizeProblems = (count) =>
        `${count} ${count === 1 ? 'campo precisa' : 'campos precisam'} de correção.`;

    const fieldLabels = {
        full_name: 'Nome completo',
        cpf: 'CPF',
        birth_date: 'Data de nascimento',
        phone: 'Telefone',
        whatsapp: 'WhatsApp',
        email: 'E-mail',
        cep: 'CEP',
        address: 'Rua',
        address_number: 'Número',
        neighborhood: 'Bairro',
        city: 'Cidade',
        state: 'Estado',
        cnh_number: 'Número da CNH',
        cnh_category: 'Categoria da CNH',
        cnh_expiry_date: 'Validade da CNH',
        vehicle_id: 'Veículo',
        quota_type_id: 'Tipo de cota',
        start_date: 'Data de início',
        end_date: 'Data de fim',
        cnh_front_file: 'Foto da CNH (frente)',
        cnh_back_file: 'Foto da CNH (verso)',
        proof_of_residence_file: 'Comprovante de residência',
        selfie_file: 'Selfie',
        veracity_declaration_accepted: 'Declaração de veracidade',
        privacy_policy_accepted: 'Política de privacidade',
        contract_signature: 'Assinatura do contrato',
        contract_signer_name: 'Nome do signatário',
        contract_accepted: 'Aceite do contrato',
    };

    const buildProblemGroup = ({ step, count, field }) => {
        const row = document.createElement('div');
        row.className =
            'flex items-center justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3';

        const text = document.createElement('div');
        text.className = 'min-w-0';

        const title = document.createElement('p');
        title.className = 'truncate text-sm font-medium text-gray-100';
        title.textContent = panel(step)?.dataset.title || `Etapa ${step}`;

        const detail = document.createElement('p');
        detail.className = 'mt-0.5 text-xs text-amber-200';
        detail.textContent = fieldLabels[field] ? `Corrigir: ${fieldLabels[field]}` : pluralizeProblems(count);

        text.append(title, detail);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-secondary btn-sm shrink-0';
        button.dataset.corrigirStep = String(step);
        button.dataset.corrigirField = field ?? '';
        button.setAttribute(
            'aria-label',
            `Corrigir ${panel(step)?.dataset.title || `etapa ${step}`}${fieldLabels[field] ? ` — ${fieldLabels[field]}` : ''}`,
        );
        button.textContent = 'Corrigir';

        row.append(text, button);

        return row;
    };

    // Aplica estilo e mensagem do servidor a cada campo com erro. É chamada
    // após a validação local para que campos válidos localmente (ex.: validade
    // passada) ainda exibam a mensagem do servidor na revisão.
    const renderServerFieldErrors = () => {
        for (const [name, message] of Object.entries(serverErrors)) {
            const input = form.elements.namedItem(name);

            if (!input) {
                continue;
            }

            const el = findErrorEl(input);
            if (el) {
                el.textContent = message || messages[name] || 'Corrija este campo.';
                el.hidden = false;
            }

            displayTarget(input).classList.add('input-invalid');
            if (input instanceof HTMLSelectElement) {
                input.closest('[data-custom-select]')?.classList.add('has-error');
            }
            input.closest('.upload-card')?.classList.add('has-error');
            input.setAttribute('aria-invalid', 'true');
        }
    };

    const renderReviewProblems = () => {
        if (!reviewProblemsEl) {
            return;
        }

        const groups = [];

        for (let step = 1; step <= reviewStep; step++) {
            const { errors, firstInvalid } = validateStep(step);
            const liveCount = errors.length;
            const serverField = serverFieldOfStep(step);
            const serverCount = Number(serverErrorSteps[step] ?? 0);

            if (liveCount > 0 || serverField) {
                groups.push({
                    step,
                    count: Math.max(liveCount, serverCount),
                    field: firstInvalid?.name ?? serverField,
                });
            }
        }

        // Mensagens do servidor por campo (ex.: validade futura) são renderizadas
        // após a validação local para não serem apagadas por ela.
        renderServerFieldErrors();

        const total = groups.reduce((sum, group) => sum + group.count, 0);

        if (groups.length === 0) {
            reviewProblemsEl.hidden = true;
            reviewProblemsEl.removeAttribute('aria-live');
            reviewProblemsList.replaceChildren();
            return;
        }

        if (reviewProblemsIntro) {
            reviewProblemsIntro.textContent = `Encontramos ${total} ${total === 1 ? 'item' : 'itens'} para corrigir.`;
        }

        reviewProblemsList.replaceChildren(...groups.map(buildProblemGroup));
        reviewProblemsEl.hidden = false;
        reviewProblemsEl.setAttribute('aria-live', 'polite');
    };

    reviewProblemsList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-corrigir-step]');
        if (!button) {
            return;
        }

        // Navegação direta para a etapa do campo: sem submit, sem reload e sem
        // disparar a validação local no clique.
        correctionMode = true;
        const step = Number(button.dataset.corrigirStep);
        gotoStep(step);

        const targetName = button.dataset.corrigirField;
        const target = targetName ? form.elements.namedItem(targetName) : null;
        const uploadCard = target?.closest('.upload-card');
        const scrollEl = uploadCard ?? target?.closest('[data-custom-select]') ?? target;

        if (scrollEl) {
            scrollEl.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        }

        // Foco no campo quando tecnicamente apropriado (campos de upload não
        // recebem foco de forma útil — nesses casos apenas o scroll é feito).
        if (target && !uploadCard) {
            displayTarget(target).focus({ preventScroll: true });
        }
    });

    /* ---------- Máscaras ---------- */
    const masks = {
        cpf: (v) =>
            v.replace(/\D/g, '')
                .slice(0, 11)
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4'),
        phone: (v) => {
            const d = v.replace(/\D/g, '').slice(0, 11);
            return d.length > 10
                ? d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
                : d.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        },
        cep: (v) => v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
    };

    const applyMasks = () => {
        document.querySelectorAll('[data-mask]').forEach((input) => {
            const mask = masks[input.dataset.mask];
            if (!mask) {
                return;
            }

            input.addEventListener('input', () => {
                const next = mask(input.value);
                if (input.value !== next) {
                    input.value = next;
                }
            });
        });
    };

    /* ---------- Erros por campo ---------- */
    const messages = {
        full_name: 'Informe seu nome completo.',
        cpf: 'Informe o CPF completo.',
        birth_date: 'Informe sua data de nascimento.',
        phone: 'Informe um telefone válido.',
        whatsapp: 'Informe um WhatsApp válido.',
        email: 'Informe um e-mail válido.',
        cep: 'Informe um CEP válido.',
        address: 'Informe a rua.',
        address_number: 'Informe o número.',
        neighborhood: 'Informe o bairro.',
        city: 'Informe a cidade.',
        state: 'Selecione o estado.',
        cnh_number: 'Informe o número da CNH.',
        cnh_category: 'Selecione a categoria da CNH.',
        cnh_expiry_date: 'Informe a validade da CNH.',
        vehicle_id: 'Selecione um veículo disponível.',
        quota_type_id: 'Selecione uma cota disponível.',
        start_date: 'Informe a data de início.',
        end_date: 'Informe a data de fim.',
        cnh_front_file: 'Adicione a foto da frente da CNH.',
        cnh_back_file: 'Adicione a foto do verso da CNH.',
        proof_of_residence_file: 'Adicione o comprovante de residência.',
        selfie_file: 'Adicione sua selfie.',
        veracity_declaration_accepted: 'Confirme a declaração de veracidade.',
        privacy_policy_accepted: 'Aceite a política de privacidade.',
        contract_signature: 'Desenhe sua assinatura antes de continuar.',
        contract_signer_name: 'Informe o nome do signatário.',
        contract_accepted: 'Aceite os termos do contrato.',
    };

    const findErrorEl = (input) => {
        const own = document.getElementById(`${input.name}-error`);
        if (own) {
            return own;
        }
        return input.closest('.upload-card')?.querySelector('.field-error') ?? null;
    };

    const displayTarget = (input) =>
        input.closest('[data-custom-select]')?.querySelector('.cs-trigger') ?? input;

    // No desktop (página única) os erros ficam abaixo da dobra; rola até o
    // container de problemas da revisão para que o usuário os localize.
    const scrollReviewProblems = () => {
        if (reviewProblemsEl && !reviewProblemsEl.hidden) {
            reviewProblemsEl.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        }
    };

    // Rola até o cartão/controle do campo inválido e tenta dar foco no desktop.
    const revealField = (input) => {
        const uploadCard = input.closest('.upload-card');
        const scrollEl = uploadCard ?? input.closest('[data-custom-select]') ?? input;

        scrollEl.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });

        if (!uploadCard) {
            displayTarget(input).focus({ preventScroll: true });
        }
    };

    const markInvalid = (input, message) => {
        displayTarget(input).classList.add('input-invalid');
        if (input instanceof HTMLSelectElement) {
            input.closest('[data-custom-select]')?.classList.add('has-error');
        }
        const card = input.closest('.upload-card');
        if (card) card.classList.add('has-error');
        if (input.type === 'hidden' && input.id === 'contract_signature') {
            document.getElementById('signature-canvas-wrap')?.classList.add('has-error');
        }
        input.setAttribute('aria-invalid', 'true');

        const el = findErrorEl(input);
        if (el) {
            el.textContent = message || messages[input.name] || 'Campo obrigatório.';
            el.hidden = false;
        }
    };

    const clearError = (input) => {
        displayTarget(input).classList.remove('input-invalid');
        input.closest('[data-custom-select]')?.classList.remove('has-error');
        input.closest('.upload-card')?.classList.remove('has-error');
        if (input.type === 'hidden' && input.id === 'contract_signature') {
            document.getElementById('signature-canvas-wrap')?.classList.remove('has-error');
        }
        input.removeAttribute('aria-invalid');

        const el = findErrorEl(input);
        if (el) {
            el.textContent = '';
            el.hidden = true;
        }
    };

    const bindLiveClear = () => {
        form.querySelectorAll('input, select').forEach((input) => {
            input.addEventListener('input', () => clearError(input));
            input.addEventListener('change', () => clearError(input));
            input.addEventListener('blur', () => clearError(input));
        });
        form.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => clearError(input));
        });
    };

    /* ---------- Validação por etapa ---------- */
    const requiredFieldNames = {
        1: ['full_name', 'cpf', 'birth_date', 'phone', 'whatsapp', 'email'],
        2: ['cep', 'address', 'address_number', 'neighborhood', 'city', 'state'],
        3: ['cnh_number', 'cnh_category', 'cnh_expiry_date', 'vehicle_id', 'quota_type_id', 'start_date', 'end_date'],
        4: ['cnh_front_file', 'cnh_back_file', 'proof_of_residence_file'],
        5: ['selfie_file'],
        6: ['veracity_declaration_accepted', 'privacy_policy_accepted'],
        7: ['contract_signature', 'contract_signer_name', 'contract_accepted'],
    };

    const isEmailValid = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    const hasCompatibleQuotaSelection = () => {
        const vehicleId = String(form.elements.namedItem('vehicle_id')?.value ?? '');
        const quotaId = String(form.elements.namedItem('quota_type_id')?.value ?? '');

        if (!vehicleId || !quotaId) {
            return false;
        }

        const quotaInput = form.elements.namedItem('quota_type_id');
        if (!(quotaInput instanceof HTMLSelectElement)) {
            return false;
        }

        return Array.from(quotaInput.options).some((option) => {
            const optionVehicleId = String(option.dataset.vehicleId ?? '');
            const optionQuotaId = String(option.value ?? '');

            return optionVehicleId === vehicleId && optionQuotaId === quotaId && !option.hidden;
        });
    };

    const validateStep = (step) => {
        const fields = requiredFieldNames[step] ?? [];
        const errors = [];
        let firstInvalid = null;

        for (const name of fields) {
            const input = form.elements.namedItem(name);

            if (!input) {
                continue;
            }

            const value = (input.value ?? '').trim();
            let valid = true;

            if (input.type === 'file') {
                valid = input.files.length > 0;
            } else if (input.type === 'checkbox') {
                valid = input.checked;
            } else if (!value) {
                valid = false;
            } else if (name === 'email') {
                valid = isEmailValid(value);
            } else if (name === 'cpf') {
                valid = /^\d{3}\.\d{3}\.\d{3}-\d{2}$/.test(value);
            } else if (name === 'phone' || name === 'whatsapp') {
                valid = value.replace(/\D/g, '').length >= 10;
            } else if (name === 'cep') {
                valid = /^\d{5}-?\d{3}$/.test(value);
            } else if (name === 'quota_type_id') {
                valid = hasCompatibleQuotaSelection();
            }

            if (!valid) {
                markInvalid(input, serverErrors[name]);
                errors.push(input);
                if (!firstInvalid) firstInvalid = input;
            } else {
                clearError(input);
            }
        }

        return { errors, firstInvalid };
    };

    /* ---------- Pré-visualização de imagens ---------- */
    const setupFileInputs = () => {
        document.querySelectorAll('.document-input').forEach((input) => {
            input.addEventListener('change', () => {
                markFilled(input.dataset.doc, input.files[0]);
                showPreview(input.dataset.doc, input.files[0]);
            });
        });

        document.querySelectorAll('.gallery-input').forEach((input) => {
            input.addEventListener('change', () => {
                const doc = input.dataset.doc;
                const target = form.elements.namedItem(`${doc}_file`);

                if (target && input.files[0]) {
                    const transfer = new DataTransfer();
                    transfer.items.add(input.files[0]);
                    target.files = transfer.files;
                    markFilled(doc, input.files[0]);
                    showPreview(doc, input.files[0]);
                    clearError(target);
                    input.value = '';
                }
            });
        });

        document.querySelectorAll('.remove-preview').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const doc = btn.dataset.doc;
                const target = form.elements.namedItem(`${doc}_file`);
                if (target) target.value = '';
                const wrapper = document.querySelector(`[data-preview="${doc}"]`);
                const card = wrapper?.closest('.upload-card');
                wrapper?.querySelector('.preview-image')?.classList.add('hidden');
                wrapper?.querySelector('.preview-image')?.removeAttribute('src');
                wrapper?.querySelector('.preview-empty-icon')?.classList.remove('hidden');
                const nameEl = card?.querySelector('.file-name');
                if (nameEl) {
                    nameEl.classList.add('hidden');
                    nameEl.textContent = '';
                }
                card?.classList.remove('is-filled', 'has-error');
                clearError(target);
            });
        });
    };

    const markFilled = (doc, file) => {
        const wrapper = document.querySelector(`[data-preview="${doc}"]`);
        const card = wrapper?.closest('.upload-card');
        if (card) card.classList.add('is-filled');

        const nameEl = card?.querySelector('.file-name');
        if (nameEl) {
            nameEl.classList.remove('hidden');
            nameEl.textContent = file?.name ?? '';
        }
    };

    const showPreview = (doc, file) => {
        const wrapper = document.querySelector(`[data-preview="${doc}"]`);

        if (!wrapper || !file) {
            return;
        }

        const placeholder = wrapper.querySelector('.preview-empty-icon');
        const image = wrapper.querySelector('.preview-image');

        const reader = new FileReader();
        reader.onload = () => {
            placeholder?.classList.add('hidden');
            image.classList.remove('hidden');
            image.src = reader.result;
        };
        reader.readAsDataURL(file);
    };

    /* ---------- Busca de endereço pelo CEP (ViaCEP) ---------- */
    const setupCepLookup = () => {
        const cepInput = document.getElementById('cep');
        const searchBtn = document.getElementById('cep-search');
        const feedback = document.getElementById('cep-feedback');
        const spinner = searchBtn?.querySelector('[data-cep-spinner]');
        const label = searchBtn?.querySelector('[data-cep-label]');

        if (!cepInput || !searchBtn) {
            return;
        }

        const setFeedback = (state) => {
            if (!feedback) return;
            const tone =
                state === 'success'
                    ? 'text-emerald-400'
                    : state === 'error' || state === 'incomplete'
                        ? 'text-red-400'
                        : 'text-zinc-500';
            feedback.className = `mt-1.5 flex items-center gap-1.5 text-xs ${tone}`;
            feedback.textContent = {
                looking: 'Buscando...',
                success: 'Endereço preenchido.',
                error: 'CEP não encontrado. Verifique e tente novamente.',
                incomplete: 'Informe um CEP válido.',
            }[state] ?? '';
            feedback.hidden = feedback.textContent === '';
        };

        const setLoading = (loading) => {
            searchBtn.disabled = loading;
            if (spinner) spinner.classList.toggle('hidden', !loading);
            if (label) label.classList.toggle('hidden', loading);
        };

        const setValue = (name, value) => {
            const field = document.getElementById(name);
            if (field) field.value = value ?? '';
        };

        const mapFields = (data) => {
            setValue('address', data.address);
            setValue('neighborhood', data.neighborhood);
            setValue('city', data.city);

            const state = document.getElementById('state');
            if (state && data.state) {
                const option = Array.from(state.options).some((opt) => opt.value === data.state);
                if (option) {
                    state.value = data.state;
                    state.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        };

        const lookup = async () => {
            const cep = cepInput.value.replace(/\D/g, '');

            if (cep.length !== 8) {
                setFeedback('incomplete');
                return;
            }

            setFeedback('looking');
            setLoading(true);

            try {
                const response = await fetch(`${cepLookupUrl}?cep=${encodeURIComponent(cep)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('CEP não encontrado');
                }

                const data = await response.json();
                mapFields(data);
                setFeedback('success');
            } catch {
                setFeedback('error');
            } finally {
                setLoading(false);
            }
        };

        searchBtn.addEventListener('click', lookup);
        cepInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookup();
            }
        });

        let autoTimer = null;
        cepInput.addEventListener('input', () => {
            clearTimeout(autoTimer);
            if (cepInput.value.replace(/\D/g, '').length === 8) {
                autoTimer = setTimeout(lookup, 350);
            }
        });
    };

    const setupQuotaVehicleFilter = () => {
        const vehicleInput = form.elements.namedItem('vehicle_id');
        const quotaInput = form.elements.namedItem('quota_type_id');

        if (!vehicleInput || !quotaInput) {
            return;
        }

        // Guard de reentrância: refreshQuotaOptions() é listener de 'change' do
        // quotaInput E dispara 'change' no próprio quotaInput. Sem o guard
        // haveria recursão infinita (RangeError: Maximum call stack size exceeded).
        let applyingQuotaValue = false;

        const refreshQuotaOptions = () => {
            if (applyingQuotaValue) {
                return;
            }

            const selectedVehicleId = String(vehicleInput.value ?? '');
            const previousQuotaValue = String(quotaInput.value ?? '');

            Array.from(quotaInput.options).forEach((option) => {
                const optionVehicleId = String(option.dataset.vehicleId ?? '');
                const matchesVehicle = !selectedVehicleId || optionVehicleId === selectedVehicleId;
                option.hidden = !matchesVehicle;
                option.disabled = !matchesVehicle;
                option.selected = false;
            });

            if (!selectedVehicleId) {
                applyingQuotaValue = true;
                quotaInput.value = '';
                quotaInput.dispatchEvent(new Event('change', { bubbles: true }));
                applyingQuotaValue = false;
                syncQuotaOptions();
                return;
            }

            const compatibleOptions = Array.from(quotaInput.options).filter(
                (option) => String(option.dataset.vehicleId ?? '') === selectedVehicleId && !option.hidden,
            );

            const nextQuotaValue = compatibleOptions.some((option) => String(option.value ?? '') === previousQuotaValue)
                ? previousQuotaValue
                : compatibleOptions[0]?.value ?? '';

            applyingQuotaValue = true;
            quotaInput.value = nextQuotaValue;
            quotaInput.dispatchEvent(new Event('change', { bubbles: true }));
            applyingQuotaValue = false;
            syncQuotaOptions();
        };

        const syncQuotaOptions = () => {
            quotaInput.dispatchEvent(new CustomEvent('cs:sync', { bubbles: true }));
        };

        vehicleInput.addEventListener('change', refreshQuotaOptions);
        vehicleInput.addEventListener('input', refreshQuotaOptions);
        quotaInput.addEventListener('change', refreshQuotaOptions);
        refreshQuotaOptions();
    };

    /* ---------- Resumo da última etapa ---------- */
    const setupSummary = () => {
        const syncContractSignerName = () => {
            const name = form.elements.namedItem('full_name')?.value ?? '';
            const signer = form.elements.namedItem('contract_signer_name');

            if (signer && name.trim() !== '') {
                signer.value = name.trim();
            }
        };

        const update = () => {
            const name = form.elements.namedItem('full_name')?.value ?? '';
            const cpf = form.elements.namedItem('cpf')?.value ?? '';
            const phone = form.elements.namedItem('phone')?.value ?? '';
            const email = form.elements.namedItem('email')?.value ?? '';
            const city = form.elements.namedItem('city')?.value ?? '';
            const state = form.elements.namedItem('state')?.value ?? '';

            syncContractSignerName();

            document.getElementById('summary-name').textContent = name;
            document.getElementById('summary-cpf').textContent = cpf ? `CPF: ${cpf}` : '';
            document.getElementById('summary-phone').textContent = phone ? `Telefone: ${phone}` : '';
            document.getElementById('summary-email').textContent = email || '';
            document.getElementById('summary-city').textContent = city
                ? `Endereço: ${city}${state ? ' - ' + state : ''}`
                : '';
        };

        form.addEventListener('input', update);
        form.addEventListener('change', update);
        update();
    };

    /* ---------- Assinatura digital (canvas) ---------- */
    const signatureSetup = () => {
        const canvas = document.getElementById('signature-canvas');
        const hidden = document.getElementById('contract_signature');
        const clearBtn = document.getElementById('signature-clear');
        const hint = document.getElementById('signature-hint');

        if (!canvas || !hidden) {
            return;
        }

        const DEFAULT_HEIGHT = 200;
        const dpr = Math.max(window.devicePixelRatio || 1, 1);

        let ctx = null;
        let drawing = false;
        let hasInk = false;
        let initialized = false;
        let lastX = 0;
        let lastY = 0;

        const initCanvas = () => {
            initialized = true;
            const rect = canvas.getBoundingClientRect();
            const cssWidth = Math.max(rect.width || canvas.clientWidth || 300, 1);

            canvas.width = Math.round(cssWidth * dpr);
            canvas.height = Math.round(DEFAULT_HEIGHT * dpr);

            ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = '#111827';
            ctx.lineWidth = Math.max(2.5, 2.5 * dpr);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        };

        // Reajusta o canvas quando o painel da etapa final fica visível.
        resizeSignatureCanvas = () => {
            if (!initialized) {
                initCanvas();
                return;
            }

            if (hasInk) {
                return; // preserva o traço já desenhado
            }

            const rect = canvas.getBoundingClientRect();
            const cssWidth = Math.max(rect.width || canvas.clientWidth || 300, 1);
            if (Math.abs(cssWidth * dpr - canvas.width) > 2) {
                initCanvas();
            }
        };

        const toCanvasCoords = (event) => {
            const rect = canvas.getBoundingClientRect();
            const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX;
            const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;

            return [
                (clientX - rect.left) * (canvas.width / (rect.width || 1)),
                (clientY - rect.top) * (canvas.height / (rect.height || 1)),
            ];
        };

        const detectInk = () => {
            const image = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

            for (let i = 0; i < image.length; i += 4) {
                if (image[i + 3] > 0 && (image[i] < 250 || image[i + 1] < 250 || image[i + 2] < 250)) {
                    return true;
                }
            }

            return false;
        };

        const syncHidden = () => {
            if (!detectInk()) {
                hasInk = false;
                hidden.value = '';
            } else {
                hasInk = true;
                hidden.value = canvas.toDataURL('image/png');
            }

            updateSubmitState();
        };

        // Mantém o envio bloqueado enquanto não houver assinatura válida e aceite (apenas interface).
        const updateSubmitState = () => {
            if (!submitBtn) return;
            const accepted = form.elements.namedItem('contract_accepted')?.checked ?? false;
            submitBtn.disabled = !(hidden.value !== '' && accepted);
        };

        const start = (event) => {
            if (event.button !== undefined && event.button !== 0) return;

            event.preventDefault();
            if (!initialized) initCanvas();

            canvas.setPointerCapture?.(event.pointerId);
            drawing = true;
            [lastX, lastY] = toCanvasCoords(event);
            hint?.classList.add('hidden');
        };

        const move = (event) => {
            if (!drawing) return;
            if (!ctx) return;

            event.preventDefault();
            const [x, y] = toCanvasCoords(event);

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
        };

        const end = (event) => {
            if (!drawing) return;

            drawing = false;
            canvas.releasePointerCapture?.(event.pointerId);
            syncHidden();
            clearError(hidden);
        };

        const clear = () => {
            drawing = false;
            hasInk = false;
            lastX = 0;
            lastY = 0;

            if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }

            hidden.value = '';
            hint?.classList.remove('hidden');
            clearError(hidden);
            updateSubmitState();
        };

        canvas.addEventListener('pointerdown', start);
        canvas.addEventListener('pointermove', move);
        canvas.addEventListener('pointerup', end);
        canvas.addEventListener('pointercancel', end);
        canvas.addEventListener('pointerleave', end);
        clearBtn?.addEventListener('click', clear);
        form.elements.namedItem('contract_accepted')?.addEventListener('change', updateSubmitState);
        window.addEventListener('resize', () => {
            if (isDesktopLayout()) resizeSignatureCanvas();
        });

        initCanvas();
        updateSubmitState();
    };

    /* ---------- Navegação principal ---------- */
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const submitBtn = document.getElementById('submit-btn');

    nextBtn.addEventListener('click', () => {
        if (correctionMode) {
            const { errors, firstInvalid } = validateStep(currentStep);

            if (errors.length > 0) {
                firstInvalid?.focus({ preventScroll: true });
                return;
            }

            // Etapa corrigida: "Revisar cadastro" leva direto para a última
            // etapa (Contrato e assinatura).
            clearStepServerErrors(currentStep);
            correctionMode = false;
            gotoStep(totalSteps);
            return;
        }

        const { errors, firstInvalid } = validateStep(currentStep);

        if (errors.length > 0) {
            firstInvalid?.focus({ preventScroll: true });
            return;
        }

        gotoStep(currentStep + 1);
    });

    prevBtn.addEventListener('click', () => {
        if (correctionMode) {
            correctionMode = false;
            gotoStep(reviewStep);
            return;
        }

        gotoStep(currentStep - 1);
    });

    const setSubmitting = (submitting) => {
        if (!submitBtn) return;
        submitBtn.disabled = submitting;
        submitBtn.classList.toggle('is-loading', submitting);
        const spinner = submitBtn.querySelector('[data-submit-spinner]');
        const label = submitBtn.querySelector('[data-submit-label]');
        if (spinner) spinner.classList.toggle('hidden', !submitting);
        if (label) label.textContent = submitting ? 'Enviando...' : 'Enviar cadastro';
    };

    const readCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const mapServerErrors = (errors) => {
        const next = {};

        for (const [field, list] of Object.entries(errors ?? {})) {
            if (Array.isArray(list) && list[0]) {
                next[field] = String(list[0]);
            }
        }

        return next;
    };

    const successPath = successUrl ? new URL(successUrl, window.location.href).pathname : null;

    const submitWarning = document.querySelector('[data-submit-warning]');

    const showSubmitWarning = (message) => {
        if (!submitWarning) return;
        submitWarning.textContent = message;
        submitWarning.classList.remove('hidden');
        submitWarning.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const hideSubmitWarning = () => {
        if (!submitWarning) return;
        submitWarning.classList.add('hidden');
    };

    form.addEventListener('submit', async (event) => {
        if (isDesktopLayout()) {
            // Página única: valida todas as seções de uma vez, destaca os erros
            // em cada campo visível e rola até o primeiro problema encontrado.
            let firstInvalid = null;
            let errorCount = 0;

            for (let step = 1; step <= totalSteps; step++) {
                const { errors, firstInvalid: first } = validateStep(step);
                errorCount += errors.length;

                if (first && !firstInvalid) {
                    firstInvalid = first;
                }
            }

            if (errorCount > 0) {
                event.preventDefault();
                correctionMode = true;
                renderReviewProblems();
                if (firstInvalid) revealField(firstInvalid);
                return;
            }
        } else {
            for (let step = 1; step <= totalSteps; step++) {
                const { errors, firstInvalid } = validateStep(step);
                if (errors.length > 0) {
                    event.preventDefault();
                    // Entra em modo correção: após ajustar, o usuário volta
                    // direto para a revisão sem percorrer as etapas de novo.
                    correctionMode = true;
                    gotoStep(step);
                    firstInvalid?.focus({ preventScroll: true });
                    return;
                }
            }
        }

        event.preventDefault();
        hideSubmitWarning();
        setSubmitting(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': readCsrfToken(),
                },
            });

            if (response.status === 422) {
                // Validação rejeitada pelo servidor: mantém o DOM e os arquivos
                // em memória e volta para a revisão apontando os problemas.
                const data = await response.json().catch(() => null);
                serverErrors = mapServerErrors(data?.errors);
                rebuildServerErrorSteps();
                setSubmitting(false);
                correctionMode = false;
                gotoStep(reviewStep);
                if (isDesktopLayout()) scrollReviewProblems();
                reviewProblemsList?.querySelector('[data-corrigir-step]')?.focus({ preventScroll: true });
                return;
            }

            setSubmitting(false);

            // Sucesso: a rota do servidor redireciona para /sucesso.
            if (successPath && new URL(response.url, window.location.href).pathname === successPath) {
                window.location.href = response.url;
                return;
            }

            // 429: limite de tentativas atingido. NUNCA recarrega a página:
            // isso devolveria o usuário à etapa 1 e descartaria os dados
            // preenchidos. Mantém o formulário intacto e orienta a aguardar.
            if (response.status === 429) {
                showSubmitWarning('Detectamos muitas tentativas de envio em pouco tempo. Aguarde alguns minutos e tente novamente — seus dados foram preservados.');
                return;
            }

            // Demais respostas (419/erro de processamento) seguem o fluxo
            // clássico do navegador, recarregando a página de origem.
            window.location.href = response.url;
        } catch {
            setSubmitting(false);
        }
    });

    /* ---------- Init ---------- */
    applyMasks();
    bindLiveClear();
    setupFileInputs();
    setupCepLookup();
    setupQuotaVehicleFilter();
    setupSummary();
    signatureSetup();

    const hasServerErrors = Object.values(serverErrorSteps).some((count) => Number(count) > 0);
    gotoStep(hasServerErrors ? reviewStep : 1);

    // No desktop (página única), erros de validação do servidor ficam no resumo
    // da revisão — abaixo da dobra. Rola até o container para que o usuário
    // localize rapidamente os problemas.
    if (isDesktopLayout() && hasServerErrors) {
        window.requestAnimationFrame(scrollReviewProblems);
    }
};