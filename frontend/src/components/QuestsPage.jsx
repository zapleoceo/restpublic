import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const QuestsPage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  // Массив изображений для слайдера из папки /quests
  const images = [
    '/img/quests/1.jpg',
    '/img/quests/2.jpg',
    '/img/quests/3.jpg',
    '/img/quests/4.jpg',
    '/img/quests/5.jpg',
    '/img/quests/6.jpg',
    '/img/quests/7.jpg',
    '/img/quests/8.jpg',
    '/img/quests/9.jpg',
    '/img/quests/10.jpg',
    '/img/quests/11.jpg',
    '/img/quests/12.jpg',
    '/img/quests/13.jpg',
    '/img/quests/14.jpg',
    '/img/quests/15.jpg'
  ];

  const nextImage = () => {
    setCurrentImageIndex((prevIndex) => 
      prevIndex === images.length - 1 ? 0 : prevIndex + 1
    );
  };

  const prevImage = () => {
    setCurrentImageIndex((prevIndex) => 
      prevIndex === 0 ? images.length - 1 : prevIndex - 1
    );
  };

  const goToImage = (index) => {
    setCurrentImageIndex(index);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex-1">
              <Link to="/" className="text-orange-600 hover:text-orange-700 font-medium">
                ← {t('back')}
              </Link>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">{t('quests.title')}</h1>
            <div className="flex-1 flex justify-end">
              <LanguageSwitcher />
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        {/* Hero Section */}
        <div className="text-center mb-12">
          <div className="text-6xl mb-4">🔍</div>
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            {t('quests.title')}
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            {t('quests.subtitle')}
          </p>
        </div>

        {/* Image Slider */}
        <div className="mb-12">
          <div className="relative bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="relative h-96 md:h-[500px]">
              <img
                src={images[currentImageIndex]}
                alt={`Quest ${currentImageIndex + 1}`}
                className="w-full h-full object-cover"
              />
              
              {/* Navigation arrows */}
              <button
                onClick={prevImage}
                className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              
              <button
                onClick={nextImage}
                className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
            
            {/* Image indicators */}
            <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
              {images.map((_, index) => (
                <button
                  key={index}
                  onClick={() => goToImage(index)}
                  className={`w-3 h-3 rounded-full transition-all duration-200 ${
                    index === currentImageIndex ? 'bg-white' : 'bg-white bg-opacity-50'
                  }`}
                />
              ))}
            </div>
          </div>
        </div>

        {/* About Section */}
        <div className="mb-12">
          <div className="bg-white rounded-xl shadow-lg p-8">
            <h3 className="text-3xl font-bold text-gray-900 mb-6 text-center">
              {t('quests.about.title')}
            </h3>
            <div className="grid md:grid-cols-1 gap-6 text-lg text-gray-700 leading-relaxed">
              <p>{t('quests.about.paragraph1')}</p>
              <p>{t('quests.about.paragraph2')}</p>
              <p>{t('quests.about.paragraph3')}</p>
            </div>
          </div>
        </div>

        {/* How it works Section */}
        <div className="mb-12">
          <div className="bg-white rounded-xl shadow-lg p-8">
            <h3 className="text-3xl font-bold text-gray-900 mb-6 text-center">
              {t('quests.how.title')}
            </h3>
            <div className="grid md:grid-cols-1 gap-6 text-lg text-gray-700 leading-relaxed">
              <p>{t('quests.how.paragraph1')}</p>
              <p>{t('quests.how.paragraph2')}</p>
              <p>{t('quests.how.paragraph3')}</p>
            </div>
          </div>
        </div>

        {/* Conditions Section - временно отключено, услуга недоступна */}
        {/* 
        <div className="mb-12">
          <div className="bg-white rounded-xl shadow-lg p-8">
            <h3 className="text-3xl font-bold text-gray-900 mb-8 text-center">
              {t('quests.conditions.title')}
            </h3>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">💰</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.price_per_person')}</h4>
                <p className="text-gray-600">300,000 VND</p>
              </div>
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">⏱️</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.duration_label')}</h4>
                <p className="text-gray-600">{t('quests.conditions.duration')}</p>
              </div>
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">👥</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.participants')}</h4>
                <p className="text-gray-600">2-6 человек</p>
              </div>
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">🎭</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.actors')}</h4>
                <p className="text-gray-600">Профессиональные актеры</p>
              </div>
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">🎨</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.equipment')}</h4>
                <p className="text-gray-600">Современные технологии</p>
              </div>
              <div className="text-center p-4 bg-orange-50 rounded-lg">
                <div className="text-2xl mb-2">📋</div>
                <h4 className="font-semibold text-gray-900 mb-2">{t('quests.conditions.briefing')}</h4>
                <p className="text-gray-600">Перед началом игры</p>
              </div>
            </div>
          </div>
        </div>
        */}

        {/* CTA Section */}
        <div className="text-center">
          <div className="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl shadow-lg p-8 text-white">
            <h3 className="text-3xl font-bold mb-4">{t('quests.cta.title')}</h3>
            <p className="text-lg mb-6 opacity-90">
              {t('quests.cta.description')}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="tel:+84349338758"
                className="bg-white text-orange-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200"
              >
                {t('quests.cta.call_button')}
              </a>
              <a
                href="https://t.me/gamezone_vietnam/1725"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors duration-200"
              >
                {t('quests.cta.telegram_button')}
              </a>
            </div>
          </div>
        </div>

        {/* Contact Section */}
        <ContactSection />
      </div>
    </div>
  );
};

export default QuestsPage;
