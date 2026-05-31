// resources\js\app.js
import './bootstrap';
import '../css/app.css';
import '../css/animations.css';

// Import libraries CSS
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'jsvectormap/dist/jsvectormap.css';
import 'flatpickr/dist/flatpickr.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import VueApexCharts from 'vue3-apexcharts';
import { i18n } from './locales';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(VueApexCharts);
app.use(i18n);
app.use(Toast);

app.mount('#app');

/**
 * Initialisation du store d'authentification
 */
import { useAuthStore } from './stores/authStore';
const authStore = useAuthStore();
authStore.initialize();