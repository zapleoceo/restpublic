import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const LasertagPage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  // Массив изображений для слайдера (реальные изображения с сайта-донора)
  const images = [
    '/img/lazertag/1.jpg',
    '/img/lazertag/2.jpg',
    '/img/lazertag/3',
    '/img/lazertag/4.jpg',
    '/img/lazertag/5.jpg',
    '/img/lazertag/6.jpg',
    '/img/lazertag/7.jpg',
    '/img/lazertag/8.jpg',
    '/img/lazertag/9.jpg',
    '/img/lazertag/10.jpg',
    '/img/lazertag/11.jpg',
    '/img/lazertag/12.jpg',
    '/img/lazertag/13.jpg',
    '/img/lazertag/14.jpg',
    '/img/lazertag/15.jpg'
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
                ← Назад
              </Link>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">Lasertag</h1>
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
          <div className="text-6xl mb-4">🎯</div>
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            {t('lasertag.title')}
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            {t('lasertag.subtitle')}
          </p>
        </div>

        {/* Image Slider */}
        <div className="mb-12">
          <div className="relative bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="relative h-96 md:h-[500px]">
              <img
                src={images[currentImageIndex]}
                alt={`Lasertag ${currentImageIndex + 1}`}
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
            </div>
          </div>
        </div>

        {/* Description Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
          {/* About Lasertag */}
          <div className="bg-white rounded-xl shadow-md p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-6">{t('lasertag.about.title')}</h3>
            <div className="space-y-4 text-gray-700">
              <p>{t('lasertag.about.paragraph1')}</p>
              <p>{t('lasertag.about.paragraph2')}</p>
              <p>{t('lasertag.about.paragraph3')}</p>
            </div>
          </div>

          {/* How it works */}
          <div className="bg-white rounded-xl shadow-md p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-6">{t('lasertag.how.title')}</h3>
            <div className="space-y-4 text-gray-700">
              <p>{t('lasertag.how.paragraph1')}</p>
              <p>{t('lasertag.how.paragraph2')}</p>
              <p>{t('lasertag.how.paragraph3')}</p>
            </div>
          </div>
        </div>

        {/* Conditions Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 mb-6 text-center">{t('lasertag.conditions.title')}</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl font-bold text-orange-600 mb-2">300.000 VND</div>
              <div className="text-sm text-gray-600">{t('lasertag.conditions.price_per_player')}</div>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl font-bold text-orange-600 mb-2">{t('lasertag.conditions.duration')}</div>
              <div className="text-sm text-gray-600">{t('lasertag.conditions.duration_label')}</div>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl font-bold text-orange-600 mb-2">6-14</div>
              <div className="text-sm text-gray-600">{t('lasertag.conditions.participants')}</div>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-lg">
              <div className="text-2xl font-bold text-orange-600 mb-2">5+ лет</div>
              <div className="text-sm text-gray-600">{t('lasertag.conditions.min_age')}</div>
            </div>
          </div>
          
          <div className="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                 <span className="text-gray-700">{t('lasertag.conditions.equipment')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('lasertag.conditions.scenarios')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('lasertag.conditions.referees')}</span>
              </div>
            </div>
            <div className="space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                 <span className="text-gray-700">{t('lasertag.conditions.safe_equipment')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('lasertag.conditions.safety_briefing')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('lasertag.conditions.rest_area')}</span>
              </div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center mb-12">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-8 text-white">
                         <h3 className="text-2xl font-bold mb-4">{t('lasertag.cta.title')}</h3>
             <p className="text-lg mb-6 opacity-90">
               {t('lasertag.cta.description')}
             </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                             <a
                 href="tel:+84349338758"
                 className="bg-white text-orange-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200"
               >
                 {t('lasertag.cta.call_button')}
               </a>
               <a
                 href="https://t.me/gamezone_vietnam/1725"
                 target="_blank"
                 rel="noopener noreferrer"
                 className="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors duration-200"
               >
                 {t('lasertag.cta.telegram_button')}
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

export default LasertagPage;
