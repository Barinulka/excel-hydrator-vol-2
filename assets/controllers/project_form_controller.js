import { Controller } from '@hotwired/stimulus';
import { storePendingToast } from '../utils/toast.js';
import { fetchJson } from '../utils/http.js';

export default class extends Controller {
    static targets = ['title', 'code', 'description', 'titleError', 'codeError', 'descriptionError', 'formError'];
    static values = {
        apiUrl: String,
        apiMethod: String,
    };

    async submitForm(event) {
        event.preventDefault();

        this.clearAllErrors();

        if (!this.validateForm()) {
            return;
        }

        try {
            const { response, data } = await fetchJson(this.apiUrlValue, {
                method: this.apiMethodValue,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(this.buildPayload()),
            });

            if (response.status === 422) {
                this.applyServerErrors(data.errors ?? {});
                return;
            }

            if (!response.ok) {
                this.showError(this.formErrorTarget, data.message ?? 'Не удалось сохранить проект.');
                return;
            }

            if (!data.shortId) {
                this.showError(this.formErrorTarget, 'Что-то пошло не так. Сервер вернул некорректный ответ.');
                return;
            }

            storePendingToast(data.message ?? 'Проект сохранен.', 'success');

            window.location.href = `/project/${data.shortId}`;
        } catch (error) {
            this.showError(this.formErrorTarget, 'Ошибка сети. Попробуйте еще раз.');
        }
    }

    buildPayload() {
        const title = this.titleTarget.value.trim();
        const code = this.codeTarget.value.trim();
        const description = this.descriptionTarget.value.trim();

        return {
            title,
            code: code === '' ? null : code,
            description: description === '' ? null : description,
        };
    }

    validateForm() {
        const isTitleValid = this.validateTitle();
        const isCodeValid = this.validateCode();

        return isTitleValid && isCodeValid;
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
            this.clearError(this.codeErrorTarget);
            return true;
        }

        if (!/^[A-Za-z0-9_-]+$/.test(value)) {
            this.showError(this.codeErrorTarget, 'Код проекта может содержать только латиницу, цифры, дефис и нижнее подчеркивание.');
            return false;
        }

        this.clearError(this.codeErrorTarget);
        return true;
    }

    applyServerErrors(errors) {
        if (errors.title?.length) {
            this.showError(this.titleErrorTarget, errors.title[0]);
        }

        if (errors.code?.length) {
            this.showError(this.codeErrorTarget, errors.code[0]);
        }

        if (errors.description?.length) {
            this.showError(this.descriptionErrorTarget, errors.description[0]);
        }
    }

    clearAllErrors() {
        this.clearError(this.titleErrorTarget);
        this.clearError(this.codeErrorTarget);
        this.clearError(this.descriptionErrorTarget);
        this.clearError(this.formErrorTarget);
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
