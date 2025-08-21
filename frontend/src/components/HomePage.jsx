import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const HomePage = () => {
  const { t } = useTranslation();

  const sections = [
    {
      id: 'menu',
      icon: '🍽️',
      link: '/m'
    },
    {
      id: 'lasertag',
      icon: '🎯',
      link: '/lt'
    },
    {
      id: 'bow',
      icon: '🏹',
      link: '/bow'
    },
    {
      id: 'cinema',
      icon: '🎬',
      link: '/cinema'
    },
    {
      id: 'rent',
      icon: '🏕️',
      link: '/rent'
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex-1"></div>
            <h1 className="text-2xl font-bold text-gray-900">GoodZone</h1>
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
          <div className="text-6xl mb-4">🎯</div>
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
              className="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 p-6 text-center group"
            >
              <div className="text-4xl mb-4 group-hover:scale-110 transition-transform duration-200">
                {section.icon}
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                {t(`sections.${section.id}.title`)}
              </h3>
              <p className="text-gray-600 text-sm">
                {t(`sections.${section.id}.description`)}
              </p>
            </Link>
          ))}
        </div>

        {/* Contact Section */}
        <ContactSection />
      </div>

      {/* Version info - hidden div */}
      <div className="fixed bottom-2 right-2 text-xs text-gray-400 opacity-30 pointer-events-none">
        v2.2.11
      </div>
    </div>
  );
};

export default HomePage;
