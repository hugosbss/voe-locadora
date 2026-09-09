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

    const totalSteps = Number(window.registrationForm?.totalSteps ?? 6);
    const cepLookupUrl = window.registrationForm?.cepLookupUrl ?? '/cep';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let currentStep = 1;

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

        document.querySelectorAll('.step-panel').forEach((el, index) => {
            el.classList.toggle('hidden', index + 1 !== currentStep);
        });

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
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    };

    const updateNavigation = () => {
        document.getElementById('prev-btn').classList.toggle('invisible', currentStep === 1);
        document.getElementById('next-btn').classList.toggle('invisible', currentStep === totalSteps);
    };

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
        cnh_front_file: 'Adicione a foto da frente da CNH.',
        cnh_back_file: 'Adicione a foto do verso da CNH.',
        proof_of_residence_file: 'Adicione o comprovante de residência.',
        selfie_file: 'Adicione sua selfie.',
        veracity_declaration_accepted: 'Confirme a declaração de veracidade.',
        privacy_policy_accepted: 'Aceite a política de privacidade.',
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

    const markInvalid = (input, message) => {
        displayTarget(input).classList.add('input-invalid');
        if (input instanceof HTMLSelectElement) {
            input.closest('[data-custom-select]')?.classList.add('has-error');
        }
        const card = input.closest('.upload-card');
        if (card) card.classList.add('has-error');
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
        3: ['cnh_number', 'cnh_category', 'cnh_expiry_date'],
        4: ['cnh_front_file', 'cnh_back_file', 'proof_of_residence_file'],
        5: ['selfie_file'],
        6: ['veracity_declaration_accepted', 'privacy_policy_accepted'],
    };

    const isEmailValid = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

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
            }

            if (!valid) {
                markInvalid(input);
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
                    ? 'text-emerald-600'
                    : state === 'error' || state === 'incomplete'
                        ? 'text-red-600'
                        : 'text-slate-500';
            feedback.className = `mt-1.5 flex items-center gap-1.5 text-xs ${tone}`;
            feedback.textContent = {
                idle: 'Preencha e clique em "Buscar" para preencher o endereço automaticamente.',
                looking: 'Buscando endereço...',
                success: 'Endereço preenchido automaticamente.',
                error: 'Não foi possível encontrar o endereço para este CEP.',
                incomplete: 'Informe um CEP válido para buscar o endereço.',
            }[state];
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

    /* ---------- Resumo da última etapa ---------- */
    const setupSummary = () => {
        const update = () => {
            const name = form.elements.namedItem('full_name')?.value ?? '';
            const cpf = form.elements.namedItem('cpf')?.value ?? '';
            const phone = form.elements.namedItem('phone')?.value ?? '';
            const email = form.elements.namedItem('email')?.value ?? '';
            const city = form.elements.namedItem('city')?.value ?? '';
            const state = form.elements.namedItem('state')?.value ?? '';

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

    /* ---------- Navegação principal ---------- */
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const submitBtn = document.getElementById('submit-btn');

    nextBtn.addEventListener('click', () => {
        const { errors, firstInvalid } = validateStep(currentStep);

        if (errors.length > 0) {
            firstInvalid?.focus({ preventScroll: true });
            return;
        }

        gotoStep(currentStep + 1);
    });

    prevBtn.addEventListener('click', () => gotoStep(currentStep - 1));

    const setSubmitting = (submitting) => {
        if (!submitBtn) return;
        submitBtn.disabled = submitting;
        submitBtn.classList.toggle('is-loading', submitting);
        const spinner = submitBtn.querySelector('[data-submit-spinner]');
        const label = submitBtn.querySelector('[data-submit-label]');
        if (spinner) spinner.classList.toggle('hidden', !submitting);
        if (label) label.textContent = submitting ? 'Enviando cadastro...' : 'Enviar cadastro';
    };

    form.addEventListener('submit', (event) => {
        for (let step = 1; step <= totalSteps; step++) {
            const { errors, firstInvalid } = validateStep(step);
            if (errors.length > 0) {
                event.preventDefault();
                gotoStep(step);
                firstInvalid?.focus({ preventScroll: true });
                return;
            }
        }

        setSubmitting(true);
    });

    /* ---------- Init ---------- */
    applyMasks();
    bindLiveClear();
    setupFileInputs();
    setupCepLookup();
    setupSummary();
    gotoStep(1);
};