import { createI18n } from 'vue-i18n';
import fr from './fr.json';
import en from './en.json';

// Types pour TypeScript
type MessageSchema = typeof fr;

// Configuration i18n
export const i18n = createI18n<[MessageSchema], 'fr' | 'en'>({
  legacy: false, // Utilisez l'API Composition
  locale: 'fr', // Langue par défaut
  fallbackLocale: 'en', // Langue de repli
  globalInjection: true, // Rend $t disponible globalement
  messages: {
    fr,
    en
  },
  datetimeFormats: {
    fr: {
      short: {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      },
      long: {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      }
    },
    en: {
      short: {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      },
      long: {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      }
    }
  },
  numberFormats: {
    fr: {
      currency: {
        style: 'currency',
        currency: 'EUR',
        notation: 'standard'
      },
      decimal: {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }
    },
    en: {
      currency: {
        style: 'currency',
        currency: 'USD',
        notation: 'standard'
      },
      decimal: {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }
    }
  }
});

// Export pour une utilisation facile
export default i18n;