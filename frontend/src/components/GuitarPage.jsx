import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const GuitarPage = () => {
  const { t } = useTranslation();

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex-1">
              <Link to="/" className="text-gray-600 hover:text-gray-900">
                ← Назад
              </Link>
            </div>
            <img src="/img/logo.png" alt="GoodZone Logo" className="h-36 w-auto mt-10" />
            <div className="flex-1 flex justify-end">
              <LanguageSwitcher />
            </div>
          </div>
        </div>
      </div>

      {/* Hero Section */}
      <div className="relative bg-cover bg-center h-96" style={{ backgroundImage: 'url(/img/guitar/button.jpg)' }}>
        <div className="absolute inset-0 bg-black bg-opacity-40"></div>
        <div className="relative h-full flex items-center justify-center">
          <div className="text-center text-white">
            <h1 className="text-4xl md:text-6xl font-bold mb-4">
              {t('guitar.title')}
            </h1>
            <p className="text-xl md:text-2xl">
              {t('guitar.subtitle')}
            </p>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Description */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('guitar.atmosphere.title')}
          </h2>
          <p className="text-lg text-gray-700 mb-6 leading-relaxed">
            {t('guitar.atmosphere.description')}
          </p>
          
          <div className="bg-orange-50 border-l-4 border-orange-400 p-6 mb-6">
            <p className="text-lg text-gray-800 italic">
              {t('guitar.atmosphere.quote')}
            </p>
          </div>
        </div>

        {/* What to expect */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('guitar.expect.title')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <span className="text-orange-600 text-lg">🎵</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('guitar.expect.live_music.title')}</h3>
                <p className="text-gray-600">{t('guitar.expect.live_music.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <span className="text-orange-600 text-lg">💬</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('guitar.expect.conversations.title')}</h3>
                <p className="text-gray-600">{t('guitar.expect.conversations.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <span className="text-orange-600 text-lg">☕</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('guitar.expect.tea.title')}</h3>
                <p className="text-gray-600">{t('guitar.expect.tea.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <span className="text-orange-600 text-lg">🎤</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('guitar.expect.perform.title')}</h3>
                <p className="text-gray-600">{t('guitar.expect.perform.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <span className="text-orange-600 text-lg">🤝</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('guitar.expect.friendly.title')}</h3>
                <p className="text-gray-600">{t('guitar.expect.friendly.description')}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Rules */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('guitar.rules.title')}
          </h2>
          <div className="space-y-4">
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                <span className="text-red-600 text-sm">1</span>
              </div>
              <p className="text-gray-700">{t('guitar.rules.memorize')}</p>
            </div>
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                <span className="text-red-600 text-sm">2</span>
              </div>
              <p className="text-gray-700">{t('guitar.rules.alternate')}</p>
            </div>
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                <span className="text-red-600 text-sm">3</span>
              </div>
              <p className="text-gray-700">{t('guitar.rules.sincere')}</p>
            </div>
          </div>
        </div>

        {/* For whom */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('guitar.for_whom.title')}
          </h2>
          <p className="text-lg text-gray-700 mb-6 leading-relaxed">
            {t('guitar.for_whom.description')}
          </p>
          <p className="text-lg text-gray-700 leading-relaxed">
            {t('guitar.for_whom.conclusion')}
          </p>
        </div>

        {/* Schedule info */}
        <div className="bg-orange-50 rounded-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 text-center">
            {t('guitar.schedule.title')}
          </h2>
          <div className="text-center">
            <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span className="text-orange-600 text-2xl">🎵</span>
            </div>
            <p className="text-xl text-gray-800 font-semibold">
              {t('guitar.schedule.time')}
            </p>
            <p className="text-gray-600 mt-2">
              {t('guitar.schedule.description')}
            </p>
          </div>
        </div>

        {/* Booking info */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 text-center">
            {t('guitar.booking.title')}
          </h2>
          <div className="text-center">
            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span className="text-blue-600 text-2xl">📱</span>
            </div>
            <p className="text-lg text-gray-700 mb-6">
              {t('guitar.booking.description')}
            </p>
            <a 
              href={t('guitar.booking.telegram_link')}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200"
            >
              {t('guitar.booking.telegram_button')}
            </a>
          </div>
        </div>

        {/* Final quote */}
        <div className="text-center mt-12">
          <div className="bg-white rounded-lg shadow-lg p-8">
            <p className="text-2xl md:text-3xl text-gray-800 italic font-serif">
              {t('guitar.final_quote')}
            </p>
          </div>
        </div>

        {/* Contact Section */}
        <div className="mt-7">
          <ContactSection />
        </div>
      </div>
    </div>
  );
};

export default GuitarPage;
