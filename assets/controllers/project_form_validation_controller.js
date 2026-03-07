import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['title', 'code', 'titleError', 'codeError'];

    validateForm(event) {
        const isTitleValid = this.validateTitle();
        const isCodeValid = this.validateCode();

        if (!isTitleValid || !isCodeValid) {
            event.preventDefault();
        }
    }

    validateTitle() {
        const value = this.titleTarget.value.trim();

        if (value.length === 0) {
            this.showError(this.titleErrorTarget, 'Название проекта обязательно для заполнения.');
            return false;
        }

        this.clearError(this.titleErrorTarget);
        return true;
    }

    validateCode() {
        const value = this.codeTarget.value.trim();

        if (value.length === 0) {
            this.showError(this.codeErrorTarget, 'Код проекта обязателен для заполнения.');
            return false;
        }

        if (!/^[A-Za-z0-9_-]+$/.test(value)) {
            this.showError(this.codeErrorTarget, 'Код проекта может содержать только латиницу, цифры, дефис и нижнее подчеркивание.');
            return false;
        }

        this.clearError(this.codeErrorTarget);
        return true;
    }

    showError(target, message) {
        target.textContent = message;
        target.classList.add('project-form__client-error--visible');
    }

    clearError(target) {
        target.textContent = '';
        target.classList.remove('project-form__client-error--visible');
    }
}
