import React, { useState } from 'react';
import { Globe } from 'lucide-react';

const LanguageSwitcher = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [currentLanguage, setCurrentLanguage] = useState('EN');

  const languages = [
    { code: 'en', name: 'EN' },
    { code: 'ru', name: 'RU' }, 
    { code: 'vi', name: 'VI' }
  ];

  const handleLanguageChange = (languageCode, languageName) => {
    setCurrentLanguage(languageName);
    setIsOpen(false);
    // TODO: Добавить логику смены языка через i18n
  };

  return (
    <div className="language-switcher">
      <button 
        className="language-switcher__button"
        onClick={() => setIsOpen(!isOpen)}
      >
        <Globe className="language-switcher__icon" size={16} />
        <span className="language-switcher__current">{currentLanguage}</span>
      </button>
      
      {isOpen && (
        <div className="language-switcher__dropdown">
          {languages.map((language) => (
            <button
              key={language.code}
              onClick={() => handleLanguageChange(language.code, language.name)}
              className={`language-switcher__option ${
                currentLanguage === language.name ? 'language-switcher__option--active' : ''
              }`}
            >
              {language.name}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default LanguageSwitcher;
