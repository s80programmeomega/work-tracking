// resources\js\app.js
import './bootstrap';
import '../css/app.css';
import '../css/animations.css';

// CSS de librairies retiré du bundle global (perf) :
//  - swiper : non utilisé dans l'application ;
//  - jsvectormap : seulement dans un composant orphelin (importera son propre CSS au besoin) ;
//  - flatpickr : déjà importé par le seul composant qui l'utilise (DefaultInputs.vue).

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { i18n } from './locales';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

// NB (perf) : vue3-apexcharts n'est PAS enregistré globalement. Les composants de
// graphiques importent VueApexCharts localement (auto-enregistré via <script setup>),
// ce qui sort cette grosse librairie du bundle app.js chargé sur chaque page.

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.use(Toast);

app.mount('#app');

/**
 * Initialisation du store d'authentification
 */
import { useAuthStore } from './stores/authStore';
const authStore = useAuthStore();
authStore.initialize();