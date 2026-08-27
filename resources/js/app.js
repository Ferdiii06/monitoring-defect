import './bootstrap';

import Chart from 'chart.js/auto';
import flatpickr from 'flatpickr';

window.Chart = Chart;
window.flatpickr = flatpickr;

import Pusher from 'pusher-js';
window.Pusher = Pusher;

import Echo from 'laravel-echo';
window.Echo = Echo;
