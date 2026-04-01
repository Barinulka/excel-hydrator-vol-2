import { Controller } from '@hotwired/stimulus';
import { showToast } from '../utils/toast.js';
import { fetchJson } from '../utils/http.js';

export default class extends Controller {
    static targets = ['toggle', 'title', 'input', 'submit'];
    static values = {
        apiUrl: String,
    };

    toggleChanged() {
        if (!this.hasToggleTarget) {
            return;
        }

        if (this.toggleTarget.checked) {
            this.inputTarget.focus();
            this.inputTarget.select();
            return;
        }

        this.inputTarget.value = this.titleTarget.textContent.trim();
    }

    async submit(event) {
        event.preventDefault();

        const title = this.inputTarget.value.trim();
        if (title.length === 0) {
            showToast('Название модели не может быть пустым.', 'error');
            this.inputTarget.focus();
            return;
        }

        this.inputTarget.value = title;

        if (!this.hasApiUrlValue || this.apiUrlValue === '') {
            showToast('Не найден endpoint для обновления названия модели.', 'error');
            return;
        }

        this.setLoading(true);

        try {
            const { response, data: payload } = await fetchJson(this.apiUrlValue, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ title }),
            });

            if (!response.ok) {
                const validationMessage = payload?.errors?.title?.[0];
                const message = validationMessage || payload?.message || 'Не удалось обновить название модели.';
                showToast(message, 'error');
                return;
            }

            const updatedTitle = payload?.title || title;
            this.titleTarget.textContent = updatedTitle;
            this.inputTarget.value = updatedTitle;
            showToast(payload?.message || 'Название модели обновлено.', 'success');

            if (this.hasToggleTarget) {
                this.toggleTarget.checked = false;
                this.toggleChanged();
            }
        } catch (error) {
            showToast('Сетевая ошибка. Попробуйте еще раз.', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(isLoading) {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = isLoading;
            this.submitTarget.textContent = isLoading ? 'Сохранение...' : 'Сохранить';
        }

        this.inputTarget.disabled = isLoading;
    }
}
