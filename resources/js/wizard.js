/**
 * Step-by-step forms (user rule 2026-11-22: forms with many fields are wizards, with proper steps and grouping).
 *
 * Usage: x-data="formWizard({ ...your component... }, { steps: 6, initial: 1 })"
 * Mark each step's wrapper with data-step="n" and x-show="step === n", and use <x-wizard-steps> for the
 * step bar and <x-wizard-nav> for Back / Next / Save. A step's own inputs are checked (HTML required,
 * type, min/max, pattern) before moving on; the component may add a guard(n) method for anything else
 * (e.g. "a land file must be picked"). The form itself should carry novalidate, because the browser
 * cannot show its own messages on fields in hidden steps.
 */
window.formWizard = function (component, { steps, initial = 1 }) {
    const wizard = {
        step: initial,
        steps,
        furthest: initial,

        stepFields(n) {
            return [...this.$root.querySelectorAll(`[data-step="${n}"] input, [data-step="${n}"] select, [data-step="${n}"] textarea`)]
                .filter((el) => ! el.disabled && el.type !== 'hidden');
        },

        validStep(n) {
            if (typeof this.guard === 'function' && this.guard(n) === false) {
                this.step = n;
                return false;
            }
            const bad = this.stepFields(n).find((el) => ! el.checkValidity());
            if (bad) {
                this.step = n;
                this.$nextTick(() => bad.reportValidity());
                return false;
            }
            return true;
        },

        next() {
            if (! this.validStep(this.step)) return;
            this.step = Math.min(this.step + 1, this.steps);
            this.furthest = Math.max(this.furthest, this.step);
            this.top();
        },

        back() {
            this.step = Math.max(this.step - 1, 1);
            this.top();
        },

        go(n) {
            if (n === this.step) return;
            // Going back is free; going forward checks every step on the way.
            for (let i = this.step; i < n; i++) {
                if (! this.validStep(i)) return;
            }
            this.step = n;
            this.furthest = Math.max(this.furthest, n);
            this.top();
        },

        submitAll(event) {
            for (let i = 1; i <= this.steps; i++) {
                if (! this.validStep(i)) {
                    event.preventDefault();
                    this.top();
                    return;
                }
            }
            this.saving = true;
        },

        saving: false,

        top() {
            this.$nextTick(() => this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
    };

    // Keep the component's getters live (a plain spread would freeze them into values).
    return Object.defineProperties(wizard, Object.getOwnPropertyDescriptors(component));
};
