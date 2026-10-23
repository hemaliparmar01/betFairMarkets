import { escapeHtml } from './dom';
import { fillFingerprintFields } from './fingerprint';

class CustomerForm {
    constructor(root) {
        this.root = root;
        this.countries = this.readCountries();
        this.selectedCountry = root.dataset.selectedCountry || '';
        this.selectedCode = root.dataset.selectedCountryCode || this.countries[0]?.code || '';
        this.selectedDial = root.dataset.selectedDialCode || this.countries[0]?.dial_code || '';
        this.timezone = root.dataset.defaultTimezone || '';
        this.openDropdown = null;
        this.bind();
        this.syncFromInitialCountry();
        this.paint();
        fillFingerprintFields(root).then(({ timezone }) => {
            const timezoneInput = root.querySelector('[name="timezone"]');
            if (timezoneInput && !this.timezone) timezoneInput.value = timezone;
        });
    }

    readCountries() {
        const source = this.root.querySelector('[data-country-config]')?.content?.textContent?.trim();
        try { return source ? JSON.parse(source) : []; } catch { return []; }
    }

    syncFromInitialCountry() {
        const country = this.countries.find(({ name }) => name === this.selectedCountry)
            || this.countries.find(({ code }) => code === this.selectedCode)
            || this.countries.find(({ dial_code }) => dial_code === this.selectedDial);
        if (country) this.select(country, this.selectedCountry ? 'country' : 'code', false);
    }

    bind() {
        this.root.addEventListener('click', (event) => this.onClick(event));
        this.root.addEventListener('input', (event) => this.onInput(event));
        this.root.querySelectorAll('form[data-customer-submit]').forEach((form) => {
            form.addEventListener('submit', () => this.setSubmitting(form));
        });
        document.addEventListener('click', (event) => {
            if (!this.root.contains(event.target)) this.close();
        });
    }

    onClick(event) {
        const loginTab = event.target.closest('[data-login-tab]');
        if (loginTab) return this.showLoginTab(loginTab.dataset.loginTab);

        const password = event.target.closest('[data-password-toggle]');
        if (password) return this.togglePassword(password);

        const toggle = event.target.closest('[data-country-toggle]');
        if (toggle) {
            const type = toggle.dataset.countryToggle;
            this.openDropdown = this.openDropdown === type ? null : type;
            this.paint();
            return;
        }

        const option = event.target.closest('[data-country-option-index]');
        if (option) this.select(this.countries[Number(option.dataset.countryOptionIndex)], option.dataset.countryOptionType);
        else if (!event.target.closest('[data-country-dropdown]')) this.close();
    }

    onInput(event) {
        if (event.target.matches('[data-country-search]')) this.renderOptions(event.target.dataset.countrySearch);
        if (event.target.matches('[data-password-input]')) this.paintStrength(event.target);
    }

    select(country, type, repaint = true) {
        if (!country) return;
        this.selectedDial = country.dial_code || this.selectedDial;
        this.selectedCode = country.code || this.selectedCode;
        this.selectedCountry = country.name || this.selectedCountry;
        this.timezone = country.timezone || this.timezone;
        this.openDropdown = null;
        if (repaint) this.paint();
    }

    close() {
        if (!this.openDropdown) return;
        this.openDropdown = null;
        this.paint();
    }

    matchingSelected(type) {
        return type === 'country'
            ? this.countries.find(({ name }) => name === this.selectedCountry)
            : this.countries.find(({ code, dial_code }) => code === this.selectedCode || dial_code === this.selectedDial);
    }

    optionMarkup(country, index, type) {
        return `<button type="button" data-country-option-index="${index}" data-country-option-type="${type}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-[rgb(var(--surface-soft))]">
            <img src="${escapeHtml(country.flag || '')}" alt="${escapeHtml(country.name || '')}" class="h-5 w-7 rounded-sm object-cover shadow-sm">
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold theme-text-main">${escapeHtml(country.name || '')}</p><p class="text-xs theme-text-muted">${escapeHtml(country.dial_code || '')}</p></div>
        </button>`;
    }

    renderOptions(type) {
        const search = this.root.querySelector(`[data-country-search="${type}"]`)?.value.trim().toLowerCase() || '';
        const filtered = this.countries.map((country, index) => ({ country, index })).filter(({ country }) => {
            if (!search) return true;
            return [country.name, country.code, country.dial_code].some((value) => String(value || '').toLowerCase().includes(search));
        });
        const container = this.root.querySelector(`[data-country-options="${type}"]`);
        if (container) container.innerHTML = filtered.map(({ country, index }) => this.optionMarkup(country, index, type)).join('');
        const empty = this.root.querySelector(`[data-country-empty="${type}"]`);
        if (empty) empty.hidden = filtered.length > 0;
    }

    paintSelected(type) {
        const country = this.matchingSelected(type);
        const selected = this.root.querySelector(`[data-country-selected="${type}"]`);
        const placeholder = this.root.querySelector(`[data-country-placeholder="${type}"]`);
        if (selected) selected.hidden = !country;
        if (placeholder) placeholder.hidden = Boolean(country);
        if (!country) return;
        const image = selected?.querySelector('[data-country-selected-flag]');
        if (image) { image.src = country.flag || ''; image.alt = country.name || ''; }
        const label = selected?.querySelector('[data-country-selected-label]');
        if (label) label.textContent = type === 'country' ? country.name : country.dial_code;
    }

    paint() {
        ['code', 'country'].forEach((type) => {
            const dropdown = this.root.querySelector(`[data-country-dropdown="${type}"]`);
            if (dropdown) dropdown.hidden = this.openDropdown !== type;
            const toggle = this.root.querySelector(`[data-country-toggle="${type}"]`);
            toggle?.setAttribute('aria-expanded', String(this.openDropdown === type));
            this.paintSelected(type);
            this.renderOptions(type);
        });
        const values = { mobile_code: this.selectedDial, country_code: this.selectedCode, country: this.selectedCountry };
        Object.entries(values).forEach(([name, value]) => {
            this.root.querySelectorAll(`[name="${name}"]`).forEach((input) => input.value = value || '');
        });
        if (this.timezone) this.root.querySelectorAll('[name="timezone"]').forEach((input) => input.value = this.timezone);
    }

    showLoginTab(name) {
        this.root.querySelectorAll('[data-login-tab]').forEach((button) => {
            const active = button.dataset.loginTab === name;
            button.classList.toggle('betguru-toggle-tab-active', active);
            button.classList.toggle('betguru-toggle-tab-inactive', !active);
        });
        this.root.querySelectorAll('[data-login-panel]').forEach((panel) => panel.hidden = panel.dataset.loginPanel !== name);
    }

    togglePassword(button) {
        const input = button.closest('.relative')?.querySelector('[data-password-input]');
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        const label = button.querySelector('[data-password-toggle-label]');
        if (label) label.textContent = visible ? label.dataset.hideLabel : label.dataset.showLabel;
    }

    paintStrength(input) {
        let level = 0;
        if (input.value.length >= 8) level++;
        if (/[A-Z]/.test(input.value) && /[0-9]/.test(input.value)) level++;
        if (/[^A-Za-z0-9]/.test(input.value)) level++;
        const Georgian = document.documentElement.lang === 'ka';
        const messages = Georgian
            ? ['გამოიყენეთ მინიმუმ 8 სიმბოლო ასოებით, ციფრებით და ნიშნებით.', 'სუსტი პაროლი', 'საშუალო სირთულის პაროლი', 'ძლიერი პაროლი']
            : ['Use at least 8 characters with letters, numbers, and symbols.', 'Weak password', 'Medium password', 'Strong password'];
        const label = this.root.querySelector('[data-password-strength-text]');
        if (label) {
            label.textContent = messages[level];
            label.classList.remove('theme-text-muted', 'theme-text-danger', 'theme-text-warning', 'theme-text-success');
            label.classList.add(['theme-text-muted', 'theme-text-danger', 'theme-text-warning', 'theme-text-success'][level]);
        }
        this.root.querySelectorAll('[data-password-strength-segment]').forEach((segment, index) => {
            segment.classList.remove('theme-bg-border', 'betguru-strength-weak', 'betguru-strength-medium', 'betguru-strength-strong');
            segment.classList.add(level > index ? ['betguru-strength-weak', 'betguru-strength-medium', 'betguru-strength-strong'][index] : 'theme-bg-border');
        });
    }

    setSubmitting(form) {
        const button = form.querySelector('[data-submit-button]');
        if (!button) return;
        button.disabled = true;
        button.querySelector('[data-submit-spinner]')?.removeAttribute('hidden');
        const label = button.querySelector('[data-submit-label]');
        if (label) label.textContent = label.dataset.loadingLabel || label.textContent;
    }
}

export const initCustomerForms = () => {
    document.querySelectorAll('[data-customer-form]:not([data-customer-form-initialized])').forEach((root) => {
        root.dataset.customerFormInitialized = '1';
        new CustomerForm(root);
    });
};
