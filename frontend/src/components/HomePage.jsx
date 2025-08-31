import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';
import useSiteConfig from '../hooks/useSiteConfig';
import useSiteSections from '../hooks/useSiteSections';

const HomePage = () => {
  const { t, i18n } = useTranslation();
  const { getSiteName } = useSiteConfig();
  const { sections, loading: sectionsLoading, getEnabledSections } = useSiteSections();
  const [enabledSections, setEnabledSections] = useState({});
  const [loading, setLoading] = useState(true);

  // Статическая конфигурация секций
  const sectionsConfig = {
    menu: {
      id: 'menu',
      icon: '/img/menu/icon.png',
      logo: '/img/menu/big.jpg',
      link: '/menu'
    },
    lasertag: {
      id: 'lasertag',
      icon: '/img/lazertag/icon.png',
      logo: '/img/lazertag/logo.png',
      link: '/lasertag'
    },
    bow: {
      id: 'bow',
      icon: '/img/archery/icon.png',
      logo: '/img/archery/logo.png',
      link: '/archerytag'
    },
    cinema: {
      id: 'cinema',
      icon: '/img/cinema/icon.png',
      logo: '/img/cinema/big.jpg',
      link: '/cinema'
    },
    rent: {
      id: 'rent',
      icon: '/img/bbq/icon.png',
      logo: '/img/bbq/buttton.png',
      link: '/bbq_zone'
    },
    quests: {
      id: 'quests',
      icon: '/img/quests/icon.png',
      logo: '/img/quests/big.jpg',
      link: '/quests'
    },
    guitar: {
      id: 'guitar',
      icon: '/img/guitar/icon.png',
      logo: '/img/guitar/button.jpg',
      link: '/guitar'
    },
    boardgames: {
      id: 'boardgames',
      icon: '/img/boardgames/icon.png',
      logo: '/img/boardgames/button.jpg',
      link: '/boardgames'
    },
    yoga: {
      id: 'yoga',
      icon: '/img/yoga/icon.png',
      logo: '/img/yoga/button.jpg?v=1',
      link: '/yoga'
    }
  };

  useEffect(() => {
    if (!sectionsLoading && sections) {
      // Используем секции из API хука
      const apiEnabledSections = getEnabledSections();
      
      // Объединяем с статической конфигурацией для совместимости
      const mergedSections = {};
      Object.entries(apiEnabledSections).forEach(([key, section]) => {
        if (sectionsConfig[key]) {
          mergedSections[key] = {
            ...sectionsConfig[key],
            ...section,
            enabled: section.enabled
          };
        }
      });
      
      setEnabledSections(mergedSections);
      setLoading(false);
    } else if (!sectionsLoading && !sections) {
      // Fallback на статическую конфигурацию
      const allSections = {};
      Object.keys(sectionsConfig).forEach(key => {
        allSections[key] = { ...sectionsConfig[key], enabled: true };
      });
      setEnabledSections(allSections);
      setLoading(false);
    }
  }, [sectionsLoading, sections]);

  // Фильтруем только активные секции
  const sections = Object.keys(enabledSections)
    .filter(key => enabledSections[key]?.enabled)
    .map(key => sectionsConfig[key])
    .filter(Boolean);

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex-1"></div>
            <img src="/img/logo.png" alt={`${getSiteName(i18n.language)} Logo`} className="h-36 w-auto mt-10" />
            <div className="flex-1 flex justify-end">
              <LanguageSwitcher />
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Welcome Section */}
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            {t('home.title')}
          </h2>
          <p className="text-lg text-gray-600 max-w-2xl mx-auto">
            {t('home.subtitle')}
          </p>
        </div>

        {/* Services Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
          {sections.map((section) => (
            <Link
              key={section.id}
              to={section.link}
              className={`bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 p-6 text-center group relative overflow-hidden ${section.id === 'menu' || section.id === 'lasertag' || section.id === 'bow' || section.id === 'rent' || section.id === 'cinema' || section.id === 'quests' || section.id === 'guitar' || section.id === 'boardgames' || section.id === 'yoga' ? 'group' : ''}`}
            >
              {(section.id === 'menu' || section.id === 'lasertag' || section.id === 'bow' || section.id === 'rent' || section.id === 'cinema' || section.id === 'quests' || section.id === 'guitar' || section.id === 'boardgames' || section.id === 'yoga') && (
                <img 
                  src={section.logo} 
                  alt={`${getSiteName(i18n.language)} Logo`} 
                  className="absolute inset-0 w-full h-full object-cover opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-xl" 
                />
              )}
              <div className="mb-4 group-hover:scale-110 transition-transform duration-200 relative z-10">
                {(section.id === 'menu' || section.id === 'lasertag' || section.id === 'bow' || section.id === 'rent' || section.id === 'cinema' || section.id === 'quests' || section.id === 'guitar' || section.id === 'boardgames' || section.id === 'yoga') ? (
                  <img 
                    src={section.icon} 
                    alt={`${section.id === 'menu' ? 'Menu' : section.id === 'lasertag' ? 'Lasertag' : section.id === 'bow' ? 'Archery Tag' : section.id === 'rent' ? 'BBQ Zone' : section.id === 'cinema' ? 'Cinema' : section.id === 'quests' ? 'Quests' : section.id === 'guitar' ? 'Guitar' : section.id === 'boardgames' ? 'Boardgames' : 'Yoga'} Icon`}
                    className="w-16 h-16 mx-auto group-hover:opacity-0 transition-opacity duration-200 object-cover" 
                  />
                ) : (
                  <div className="text-4xl">{section.icon}</div>
                )}
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2 relative z-10 group-hover:text-white transition-colors duration-200">
                {t(`sections.${section.id}.title`)}
              </h3>
              <p className="text-gray-600 text-sm relative z-10 group-hover:text-white transition-colors duration-200">
                {t(`sections.${section.id}.description`)}
              </p>
            </Link>
          ))}
        </div>

        {/* Contact Section */}
        <div className="mt-7">
          <ContactSection />
        </div>
      </div>

      {/* Version info - hidden div */}
      <div className="fixed bottom-2 right-2 text-xs text-gray-400 opacity-30 pointer-events-none">
        v2.2.12
      </div>
    </div>
  );
};

export default HomePage;
