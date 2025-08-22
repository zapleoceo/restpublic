import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const BBQZonePage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  // Массив изображений для слайдера (из папки bbq)
  const images = [
    '/img/bbq/1.jpg',
    '/img/bbq/2.jpg',
    '/img/bbq/3.jpg',
    '/img/bbq/4.jpg',
    '/img/bbq/5.jpg',
    '/img/bbq/6.jpg',
    '/img/bbq/7.jpg',
    '/img/bbq/8.jpg',
    '/img/bbq/9.jpg',
    '/img/bbq/10.jpg'
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
    <div className="min-h-screen bg-gradient-to-br from-green-50 to-green-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
                         <div className="flex-1">
               <Link to="/" className="text-green-600 hover:text-green-700 font-medium">
                 ← {t('back')}
               </Link>
             </div>
             <h1 className="text-2xl font-bold text-gray-900">{t('bbq_zone.title')}</h1>
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
          <div className="text-6xl mb-4">🏕️</div>
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            {t('bbq_zone.title')}
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            {t('bbq_zone.subtitle')}
          </p>
        </div>

        {/* Image Slider */}
        <div className="mb-12">
          <div className="relative bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="relative h-96 md:h-[500px]">
              <img
                src={images[currentImageIndex]}
                alt={`BBQ Zone ${currentImageIndex + 1}`}
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

        {/* About Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 mb-6">{t('bbq_zone.about.title')}</h3>
          <div className="space-y-4 text-gray-700">
            <p>{t('bbq_zone.about.paragraph1')}</p>
            <p>{t('bbq_zone.about.paragraph2')}</p>
            <p>{t('bbq_zone.about.paragraph3')}</p>
            <p>{t('bbq_zone.about.paragraph4')}</p>
          </div>
        </div>

        {/* Conditions Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 mb-6 text-center">{t('bbq_zone.conditions.title')}</h3>
          
          {/* Pricing Tables */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {/* Weekdays */}
            <div className="bg-green-50 rounded-lg p-6">
              <h4 className="text-xl font-semibold text-gray-900 mb-4">{t('bbq_zone.conditions.weekdays')}</h4>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-700">09:00 - 17:00</span>
                  <span className="font-semibold text-green-600">200.000 VND/час</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-700">17:00 - 22:00</span>
                  <span className="font-semibold text-green-600">300.000 VND/час</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-700">22:00 - 02:00</span>
                  <span className="font-semibold text-green-600">350.000 VND/час</span>
                </div>
              </div>
            </div>

            {/* Weekends */}
            <div className="bg-green-50 rounded-lg p-6">
              <h4 className="text-xl font-semibold text-gray-900 mb-4">{t('bbq_zone.conditions.weekends')}</h4>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-700">09:00 - 17:00</span>
                  <span className="font-semibold text-green-600">250.000 VND/час</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-700">17:00 - 22:00</span>
                  <span className="font-semibold text-green-600">350.000 VND/час</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-700">22:00 - 02:00</span>
                  <span className="font-semibold text-green-600">400.000 VND/час</span>
                </div>
              </div>
            </div>
          </div>

          {/* Features */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                <span className="text-gray-700">{t('bbq_zone.conditions.capacity')}: <strong>до 35 человек</strong></span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                <span className="text-gray-700">{t('bbq_zone.conditions.barbecue_zone')}</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                <span className="text-gray-700">{t('bbq_zone.conditions.grill')}</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                <span className="text-gray-700">{t('bbq_zone.conditions.sink')}</span>
              </div>
            </div>
            <div className="space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                <span className="text-gray-700">{t('bbq_zone.conditions.charcoal')} - {t('bbq_zone.conditions.utensils')}</span>
              </div>
                             <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('bbq_zone.features.hourly_rental')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('bbq_zone.features.combine_with_games')}</span>
               </div>
               <div className="flex items-center">
                 <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                 <span className="text-gray-700">{t('bbq_zone.features.green_nature_zone')}</span>
               </div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center mb-12">
          <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white">
            <h3 className="text-2xl font-bold mb-4">{t('bbq_zone.cta.title')}</h3>
            <p className="text-lg mb-6 opacity-90">
              {t('bbq_zone.cta.description')}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="tel:+84349338758"
                className="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200"
              >
                {t('bbq_zone.cta.call_button')}
              </a>
              <a
                href="https://t.me/gamezone_vietnam/1729"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-blue-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors duration-200"
              >
                {t('bbq_zone.cta.telegram_button')}
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

export default BBQZonePage;
