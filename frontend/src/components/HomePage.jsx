import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';

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
        <div className="mt-16">
          <div className="bg-white rounded-xl shadow-md p-6 max-w-4xl mx-auto">
            <h3 className="text-xl font-bold text-gray-900 mb-4 text-center">{t('home.contacts')}</h3>
            <div className="flex flex-col sm:flex-row justify-center items-center space-y-3 sm:space-y-0 sm:space-x-8 text-gray-600">
              <div className="flex items-center">
                <span className="mr-2">📍</span>
                <a href="https://maps.app.goo.gl/Hgbn5n83PA11NcqLA" target="_blank" rel="noopener noreferrer" className="text-orange-600 hover:text-orange-700">{t('home.location')}</a>
              </div>
              <div className="flex items-center">
                <span className="mr-2">📞</span>
                <span>+84 349 338 758</span>
              </div>
              <div className="flex items-center">
                <span className="mr-2">🌐</span>
                <a href="https://t.me/goodzone_vn" target="_blank" rel="noopener noreferrer" className="text-orange-600 hover:text-orange-700">{t('home.group')}</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Version info - hidden div */}
      <div className="fixed bottom-2 right-2 text-xs text-gray-400 opacity-30 pointer-events-none">
        v2.2.6
      </div>
    </div>
  );
};

export default HomePage;
