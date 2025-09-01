import { useTranslation as useI18nTranslation } from 'react-i18next';

export const useTranslation = () => {
  const { t, i18n } = useI18nTranslation();
  
  const changeLanguage = (language) => {
    i18n.changeLanguage(language);
    localStorage.setItem('language', language);
  };

  const currentLanguage = i18n.language;

  return {
    t,
    changeLanguage,
    currentLanguage,
    isReady: i18n.isInitialized,
  };
};
