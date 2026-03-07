import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        delay: Number,
    };

    connect() {
        const delay = this.hasDelayValue ? this.delayValue : 4500;
        this.hideTimeout = window.setTimeout(() => this.hide(), delay);
    }

    disconnect() {
        if (this.hideTimeout) {
            window.clearTimeout(this.hideTimeout);
        }

        if (this.removeTimeout) {
            window.clearTimeout(this.removeTimeout);
        }
    }

    hide() {
        this.element.classList.add('projects-alert--hidden');
        this.removeTimeout = window.setTimeout(() => this.element.remove(), 350);
    }
}
