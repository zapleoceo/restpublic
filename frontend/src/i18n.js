import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import Backend from 'i18next-http-backend';

i18n
  .use(Backend)
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    backend: {
      loadPath: '/api/translations/{{lng}}',
      // Fallback на файлы если API недоступен
      allowMultiLoading: false,
      crossDomain: false,
    },
    fallbackLng: 'ru',
    debug: false,
    
    interpolation: {
      escapeValue: false, // React уже экранирует значения
    },
    
    detection: {
      order: ['localStorage', 'navigator'],
      caches: ['localStorage'],
    }
  });

export default i18n;
