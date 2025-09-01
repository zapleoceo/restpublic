import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const GuitarPage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  // Массив изображений для слайдера (пока одно изображение)
  const images = [
    '/img/guitar/button.jpg'
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
            <h1 className="text-2xl font-bold text-gray-900">{t('guitar.title')}</h1>
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
          <div className="text-6xl mb-4">🎵</div>
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            {t('guitar.title')}
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            {t('guitar.subtitle')}
          </p>
        </div>

        {/* Image Slider */}
        <div className="mb-12">
          <div className="relative bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="relative h-96 md:h-[500px]">
              <img
                src={images[currentImageIndex]}
                alt={`Guitar ${currentImageIndex + 1}`}
                className="w-full h-full object-cover"
              />
              
              {/* Navigation arrows - показываем только если больше одного изображения */}
              {images.length > 1 && (
                <>
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

                  {/* Image indicators */}
                  <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    {images.map((_, index) => (
                      <button
                        key={index}
                        onClick={() => goToImage(index)}
                        className={`w-3 h-3 rounded-full transition-all duration-200 ${
                          index === currentImageIndex 
                            ? 'bg-white' 
                            : 'bg-white bg-opacity-50 hover:bg-opacity-75'
                        }`}
                      />
                    ))}
                  </div>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Description Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
          {/* About Guitar Circle */}
          <div className="bg-white rounded-xl shadow-md p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-6">{t('guitar.atmosphere.title')}</h3>
            <div className="space-y-4 text-gray-700">
              <p>{t('guitar.atmosphere.description')}</p>
              <div className="bg-orange-50 border-l-4 border-orange-400 p-4">
                <p className="text-gray-800 italic">{t('guitar.atmosphere.quote')}</p>
              </div>
            </div>
          </div>

          {/* What to expect */}
          <div className="bg-white rounded-xl shadow-md p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-6">{t('guitar.expect.title')}</h3>
            <div className="space-y-4">
              <div className="flex items-start space-x-3">
                <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <span className="text-orange-600 text-lg">🎵</span>
                </div>
                <div>
                  <h4 className="font-semibold text-gray-900">{t('guitar.expect.live_music.title')}</h4>
                  <p className="text-gray-600 text-sm">{t('guitar.expect.live_music.description')}</p>
                </div>
              </div>
              <div className="flex items-start space-x-3">
                <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <span className="text-orange-600 text-lg">💬</span>
                </div>
                <div>
                  <h4 className="font-semibold text-gray-900">{t('guitar.expect.conversations.title')}</h4>
                  <p className="text-gray-600 text-sm">{t('guitar.expect.conversations.description')}</p>
                </div>
              </div>
              <div className="flex items-start space-x-3">
                <div className="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <span className="text-orange-600 text-lg">☕</span>
                </div>
                <div>
                  <h4 className="font-semibold text-gray-900">{t('guitar.expect.tea.title')}</h4>
                  <p className="text-gray-600 text-sm">{t('guitar.expect.tea.description')}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Rules Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 mb-6 text-center">{t('guitar.rules.title')}</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl mb-2">📖</div>
              <p className="text-gray-700">{t('guitar.rules.memorize')}</p>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl mb-2">🔄</div>
              <p className="text-gray-700">{t('guitar.rules.alternate')}</p>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl mb-2">❤️</div>
              <p className="text-gray-700">{t('guitar.rules.sincere')}</p>
            </div>
          </div>
        </div>

        {/* Schedule Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 mb-6 text-center">{t('guitar.schedule.title')}</h3>
          <div className="text-center">
            <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span className="text-orange-600 text-2xl">🎵</span>
            </div>
            <p className="text-xl text-gray-800 font-semibold mb-2">
              {t('guitar.schedule.time')}
            </p>
            <p className="text-gray-600">
              {t('guitar.schedule.description')}
            </p>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center mb-12">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-8 text-white">
            <h3 className="text-2xl font-bold mb-4">{t('guitar.booking.title')}</h3>
            <p className="text-lg mb-6 opacity-90">
              {t('guitar.booking.description')}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="tel:+84349338758"
                className="bg-white text-orange-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200"
              >
                📞 {t('home.phone')} +84 349 338 758
              </a>
              <a
                href={t('guitar.booking.telegram_link')}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors duration-200"
              >
                {t('guitar.booking.telegram_button')}
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

export default GuitarPage;
