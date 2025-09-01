import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';

const BoardgamesPage = () => {
  const { t } = useTranslation();

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex-1">
              <Link to="/" className="text-gray-600 hover:text-gray-900">
                ← Назад
              </Link>
            </div>
            <div className="flex-1 flex justify-end">
              <LanguageSwitcher />
            </div>
          </div>
        </div>
      </div>

      {/* Hero Section */}
      <div className="relative bg-cover bg-center h-96" style={{ backgroundImage: 'url(/img/boardgames/button.jpg)' }}>
        <div className="absolute inset-0 bg-black bg-opacity-40"></div>
        <div className="relative h-full flex items-center justify-center">
          <div className="text-center text-white">
            <h1 className="text-4xl md:text-6xl font-bold mb-4">
              {t('boardgames.title')}
            </h1>
            <p className="text-xl md:text-2xl">
              {t('boardgames.subtitle')}
            </p>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Description */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('boardgames.description.title')}
          </h2>
          <p className="text-lg text-gray-700 mb-6 leading-relaxed">
            {t('boardgames.description.text')}
          </p>
        </div>

        {/* Deduction Games */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">🎭</span>
            {t('boardgames.deduction.title')}
          </h2>
          
          <div className="space-y-8">
            {/* Мафия */}
            <div className="border-l-4 border-red-500 pl-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.deduction.mafia.title')}</h3>
              <p className="text-lg text-gray-700 mb-4">{t('boardgames.deduction.mafia.description')}</p>
              <div className="bg-red-50 p-4 rounded-lg">
                <p className="text-gray-800 italic">{t('boardgames.deduction.mafia.quote')}</p>
              </div>
            </div>

            {/* Тайный Гитлер */}
            <div className="border-l-4 border-orange-500 pl-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.deduction.hitler.title')}</h3>
              <p className="text-lg text-gray-700 mb-4">{t('boardgames.deduction.hitler.description')}</p>
              <div className="bg-orange-50 p-4 rounded-lg">
                <p className="text-gray-800 italic">{t('boardgames.deduction.hitler.quote')}</p>
              </div>
            </div>

            {/* Бункер */}
            <div className="border-l-4 border-gray-500 pl-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.deduction.bunker.title')}</h3>
              <p className="text-lg text-gray-700 mb-4">{t('boardgames.deduction.bunker.description')}</p>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-gray-800 italic">{t('boardgames.deduction.bunker.quote')}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Adventure Games */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">⚔️</span>
            {t('boardgames.adventure.title')}
          </h2>
          
          <div className="border-l-4 border-yellow-500 pl-6">
            <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.adventure.shakal.title')}</h3>
            <p className="text-lg text-gray-700 mb-4">{t('boardgames.adventure.shakal.description')}</p>
            <div className="bg-yellow-50 p-4 rounded-lg">
              <p className="text-gray-800 italic">{t('boardgames.adventure.shakal.quote')}</p>
            </div>
          </div>
        </div>

        {/* Dexterity Games */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">🎯</span>
            {t('boardgames.dexterity.title')}
          </h2>
          
          <div className="border-l-4 border-green-500 pl-6">
            <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.dexterity.jenga.title')}</h3>
            <p className="text-lg text-gray-700 mb-4">{t('boardgames.dexterity.jenga.description')}</p>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-gray-800 italic">{t('boardgames.dexterity.jenga.quote')}</p>
            </div>
          </div>
        </div>

        {/* Card Games */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">🃏</span>
            {t('boardgames.cards.title')}
          </h2>
          
          <div className="space-y-8">
            {/* Взрывные Котята */}
            <div className="border-l-4 border-pink-500 pl-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.cards.kittens.title')}</h3>
              <p className="text-lg text-gray-700 mb-4">{t('boardgames.cards.kittens.description')}</p>
              <div className="bg-pink-50 p-4 rounded-lg">
                <p className="text-gray-800 italic">{t('boardgames.cards.kittens.quote')}</p>
              </div>
            </div>

            {/* УНО */}
            <div className="border-l-4 border-blue-500 pl-6">
              <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.cards.uno.title')}</h3>
              <p className="text-lg text-gray-700 mb-4">{t('boardgames.cards.uno.description')}</p>
              <div className="bg-blue-50 p-4 rounded-lg">
                <p className="text-gray-800 italic">{t('boardgames.cards.uno.quote')}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Economic Games */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">💰</span>
            {t('boardgames.economic.title')}
          </h2>
          
          <div className="border-l-4 border-green-600 pl-6">
            <h3 className="text-2xl font-bold text-gray-900 mb-3">{t('boardgames.economic.monopoly.title')}</h3>
            <p className="text-lg text-gray-700 mb-4">{t('boardgames.economic.monopoly.description')}</p>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-gray-800 italic">{t('boardgames.economic.monopoly.quote')}</p>
            </div>
          </div>
        </div>

        {/* Atmosphere */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 flex items-center">
            <span className="text-2xl mr-3">🎲</span>
            {t('boardgames.atmosphere.title')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <span className="text-purple-600 text-lg">🪑</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('boardgames.atmosphere.tables.title')}</h3>
                <p className="text-gray-600">{t('boardgames.atmosphere.tables.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <span className="text-purple-600 text-lg">📖</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('boardgames.atmosphere.rules.title')}</h3>
                <p className="text-gray-600">{t('boardgames.atmosphere.rules.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <span className="text-purple-600 text-lg">🏆</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('boardgames.atmosphere.tournaments.title')}</h3>
                <p className="text-gray-600">{t('boardgames.atmosphere.tournaments.description')}</p>
              </div>
            </div>
            
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <span className="text-purple-600 text-lg">☕</span>
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">{t('boardgames.atmosphere.refreshments.title')}</h3>
                <p className="text-gray-600">{t('boardgames.atmosphere.refreshments.description')}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Why Play */}
        <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            {t('boardgames.why_play.title')}
          </h2>
          <p className="text-lg text-gray-700 mb-6 leading-relaxed">
            {t('boardgames.why_play.description')}
          </p>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-blue-600 text-2xl">🆕</span>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{t('boardgames.why_play.beginners.title')}</h3>
              <p className="text-gray-600 text-sm">{t('boardgames.why_play.beginners.description')}</p>
            </div>
            
            <div className="text-center">
              <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-green-600 text-2xl">🎯</span>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{t('boardgames.why_play.experienced.title')}</h3>
              <p className="text-gray-600 text-sm">{t('boardgames.why_play.experienced.description')}</p>
            </div>
            
            <div className="text-center">
              <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-purple-600 text-2xl">👥</span>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{t('boardgames.why_play.companies.title')}</h3>
              <p className="text-gray-600 text-sm">{t('boardgames.why_play.companies.description')}</p>
            </div>
            
            <div className="text-center">
              <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-orange-600 text-2xl">👨‍👩‍👧‍👦</span>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">{t('boardgames.why_play.families.title')}</h3>
              <p className="text-gray-600 text-sm">{t('boardgames.why_play.families.description')}</p>
            </div>
          </div>
        </div>

        {/* Final CTA */}
        <div className="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg p-8 text-center text-white">
          <h2 className="text-3xl font-bold mb-4">
            {t('boardgames.cta.title')}
          </h2>
          <p className="text-xl mb-6">
            {t('boardgames.cta.description')}
          </p>
          <div className="bg-white bg-opacity-20 p-4 rounded-lg">
            <p className="text-lg italic">
              {t('boardgames.cta.quote')}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BoardgamesPage;
