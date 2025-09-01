import React from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { Globe } from 'lucide-react';

const LanguageSwitcher = () => {
  const { i18n } = useTranslation();

  const languages = [
    { code: 'en', name: 'EN' },
    { code: 'ru', name: 'RU' }, 
    { code: 'vi', name: 'VI' }
  ];

  const currentLanguage = languages.find(lang => lang.code === i18n.language) || languages[0];

  const handleLanguageChange = (languageCode) => {
    i18n.changeLanguage(languageCode);
  };

  return (
    <div className="language-switcher">
      <button className="language-switcher__button">
        <Globe className="language-switcher__icon" />
        <span className="language-switcher__current">{currentLanguage.name}</span>
      </button>
      
      <div className="language-switcher__dropdown">
        {languages.map((language) => (
          <button
            key={language.code}
            onClick={() => handleLanguageChange(language.code)}
            className={`language-switcher__option ${
              i18n.language === language.code ? 'language-switcher__option--active' : ''
            }`}
          >
            {language.name}
          </button>
        ))}
      </div>
    </div>
  );
};

export default LanguageSwitcher;
