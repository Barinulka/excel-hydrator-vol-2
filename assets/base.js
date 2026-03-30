import './stimulus_bootstrap.js';
import { flushPendingToast } from './utils/toast.js';

import './styles/base.css';

document.addEventListener('DOMContentLoaded', flushPendingToast);
document.addEventListener('turbo:load', flushPendingToast);
