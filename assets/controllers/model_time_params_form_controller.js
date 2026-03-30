import { Controller } from '@hotwired/stimulus';
import { showToast, storePendingToast } from '../utils/toast.js';


export default class extends Controller {
    static targets = [
        'investmentStartMonth',
        'investmentDurationMonths',
        'commercialOperationDurationMonths',
        'forecastStep',
        'investmentStartMonthError',
        'investmentDurationMonthsError',
        'commercialOperationDurationMonthsError',
        'forecastStepError',
        'formError',
    ];

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
            const response = await fetch(this.apiUrlValue, {
                method: this.apiMethodValue,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(this.buildPayload()),
            });

            let data = {};

            try {
                data = await response.json();
            } catch (error) {
                data = {};
            }

            if (response.status === 422) {
                this.applyServerErrors(data.errors ?? {});
                return;
            }

            if (!response.ok) {
                this.showError(this.formErrorTarget, data.message ?? 'Не удалось сохранить временные параметры.');
                return;
            }

            if (!data.modelShortId) {
                this.showError(this.formErrorTarget, 'Сервер вернул некорректный ответ.');
                return;
            }

            if (data.projectShortId && data.modelShortId) {
                storePendingToast(data.message ?? 'Модель создана.', 'success');
                window.location.href = `/project/${data.projectShortId}/models/${data.modelShortId}/time-params`;
                return;
            }

            showToast(data.message ?? 'Сохранено.', 'success');
        } catch (error) {
            this.showError(this.formErrorTarget, 'Ошибка сети. Попробуйте еще раз.');
        }
    }

    buildPayload() {
        const investmentStartMonth = this.investmentStartMonthTarget.value.trim();
        const investmentDurationMonths = this.investmentDurationMonthsTarget.value.trim();
        const commercialOperationDurationMonths = this.commercialOperationDurationMonthsTarget.value.trim();
        const forecastStep = this.forecastStepTarget.value;

        return {
            investmentStartMonth: investmentStartMonth === '' ? null : investmentStartMonth,
            investmentDurationMonths: investmentDurationMonths === '' ? null : Number.parseInt(investmentDurationMonths, 10),
            commercialOperationDurationMonths: commercialOperationDurationMonths === '' ? null : Number.parseInt(commercialOperationDurationMonths, 10),
            forecastStep: forecastStep === '' ? null : forecastStep,
        };
    }

    validateForm() {
        const isInvestmentStartMonthValid = this.validateInvestmentStartMonth();
        const isInvestmentDurationMonthsValid = this.validateInvestmentDurationMonths();
        const isCommercialOperationDurationMonthsValid = this.validateCommercialOperationDurationMonths();
        const isForecastStepValid = this.validateForecastStep();

        return isInvestmentStartMonthValid
            && isInvestmentDurationMonthsValid
            && isCommercialOperationDurationMonthsValid
            && isForecastStepValid;
    }

    validateInvestmentStartMonth() {
        const value = this.investmentStartMonthTarget.value.trim();

        if (value.length === 0) {
            this.showError(this.investmentStartMonthErrorTarget, 'Укажите дату начала инвестиций.');
            return false;
        }

        if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(value)) {
            this.showError(this.investmentStartMonthErrorTarget, 'Формат даты должен быть YYYY-MM.');
            return false;
        }

        this.clearError(this.investmentStartMonthErrorTarget);
        return true;
    }

    validateInvestmentDurationMonths() {
        const value = this.investmentDurationMonthsTarget.value.trim();

        if (value.length === 0) {
            this.showError(this.investmentDurationMonthsErrorTarget, 'Укажите длительность инвестиций.');
            return false;
        }

        const parsedValue = Number.parseInt(value, 10);
        if (Number.isNaN(parsedValue) || parsedValue <= 0 || parsedValue > 600) {
            this.showError(this.investmentDurationMonthsErrorTarget, 'Укажите значение от 1 до 600.');
            return false;
        }

        this.clearError(this.investmentDurationMonthsErrorTarget);
        return true;
    }

    validateCommercialOperationDurationMonths() {
        const value = this.commercialOperationDurationMonthsTarget.value.trim();

        if (value.length === 0) {
            this.showError(this.commercialOperationDurationMonthsErrorTarget, 'Укажите длительность коммерческой работы.');
            return false;
        }

        const parsedValue = Number.parseInt(value, 10);
        if (Number.isNaN(parsedValue) || parsedValue <= 0 || parsedValue > 1200) {
            this.showError(this.commercialOperationDurationMonthsErrorTarget, 'Укажите значение от 1 до 1200.');
            return false;
        }

        this.clearError(this.commercialOperationDurationMonthsErrorTarget);
        return true;
    }

    validateForecastStep() {
        const value = this.forecastStepTarget.value;

        if (!['month', 'quarter', 'year'].includes(value)) {
            this.showError(this.forecastStepErrorTarget, 'Выберите шаг прогнозирования.');
            return false;
        }

        this.clearError(this.forecastStepErrorTarget);
        return true;
    }

    applyServerErrors(errors) {
        if (errors.investmentStartMonth?.length) {
            this.showError(this.investmentStartMonthErrorTarget, errors.investmentStartMonth[0]);
        }

        if (errors.investmentDurationMonths?.length) {
            this.showError(this.investmentDurationMonthsErrorTarget, errors.investmentDurationMonths[0]);
        }

        if (errors.commercialOperationDurationMonths?.length) {
            this.showError(this.commercialOperationDurationMonthsErrorTarget, errors.commercialOperationDurationMonths[0]);
        }

        if (errors.forecastStep?.length) {
            this.showError(this.forecastStepErrorTarget, errors.forecastStep[0]);
        }
    }

    clearAllErrors() {
        this.clearError(this.investmentStartMonthErrorTarget);
        this.clearError(this.investmentDurationMonthsErrorTarget);
        this.clearError(this.commercialOperationDurationMonthsErrorTarget);
        this.clearError(this.forecastStepErrorTarget);
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
