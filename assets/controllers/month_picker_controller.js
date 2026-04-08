import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';

export default class extends Controller {
    static targets = ['input'];

    connect() {
        this.picker = flatpickr(this.inputTarget, {
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: 'Y-m',
                    altFormat: 'F Y',
                    theme: 'light',
                }),
            ],
            dateFormat: 'Y-m',
            allowInput: true,
            disableMobile: true,
            static: true,
        });
    }


    disconnect() {
        if (this.picker) {
            this.picker.destroy();
            this.picker = null;
        }
    }
}

