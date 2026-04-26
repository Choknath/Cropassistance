// src/main.js
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;