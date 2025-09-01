import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import LanguageSwitcher from './LanguageSwitcher';
import ContactSection from './ContactSection';

const YogaPage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  const images = [
    '/img/yoga/button.jpg?v=1'
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
            <h1 className="text-2xl font-bold text-gray-900">{t('yoga.title')}</h1>
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
          <div className="text-6xl mb-4">🕉️</div>
          <h2 className="text-4xl font-bold text-gray-900 mb-4">
            {t('yoga.title')}
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            {t('yoga.subtitle')}
          </p>
        </div>

        {/* Image Slider */}
        <div className="mb-12">
          <div className="relative bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="relative h-96 md:h-[500px]">
              <img
                src={images[currentImageIndex]}
                alt={t('yoga.title')}
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
          <div>
            <h2 className="text-3xl font-bold text-gray-900 mb-6">
              {t('yoga.about.title')}
            </h2>
            <p className="text-gray-700 text-lg leading-relaxed mb-6">
              {t('yoga.about.description')}
            </p>
            <div className="space-y-4">
              <div className="flex items-start">
                <div className="flex-shrink-0 w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center mr-3 mt-1">
                  <span className="text-orange-600 text-sm font-bold">1</span>
                </div>
                <p className="text-gray-700">{t('yoga.about.point1')}</p>
              </div>
              <div className="flex items-start">
                <div className="flex-shrink-0 w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center mr-3 mt-1">
                  <span className="text-orange-600 text-sm font-bold">2</span>
                </div>
                <p className="text-gray-700">{t('yoga.about.point2')}</p>
              </div>
              <div className="flex items-start">
                <div className="flex-shrink-0 w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center mr-3 mt-1">
                  <span className="text-orange-600 text-sm font-bold">3</span>
                </div>
                <p className="text-gray-700">{t('yoga.about.point3')}</p>
              </div>
            </div>
          </div>
          <div>
            <h2 className="text-3xl font-bold text-gray-900 mb-6">
              {t('yoga.how.title')}
            </h2>
            <div className="space-y-6">
              <div>
                <h3 className="text-xl font-semibold text-gray-900 mb-3">
                  🌅 {t('yoga.how.morning.title')}
                </h3>
                <p className="text-gray-700 mb-4">
                  {t('yoga.how.morning.description')}
                </p>
                <ul className="space-y-2 text-gray-700">
                  <li>• {t('yoga.how.morning.point1')}</li>
                  <li>• {t('yoga.how.morning.point2')}</li>
                  <li>• {t('yoga.how.morning.point3')}</li>
                  <li>• {t('yoga.how.morning.point4')}</li>
                  <li>• {t('yoga.how.morning.point5')}</li>
                  <li>• {t('yoga.how.morning.point6')}</li>
                  <li>• {t('yoga.how.morning.point7')}</li>
                </ul>
              </div>
              <div>
                <h3 className="text-xl font-semibold text-gray-900 mb-3">
                  🕐 {t('yoga.how.evening.title')}
                </h3>
                <p className="text-gray-700 mb-4">
                  {t('yoga.how.evening.description')}
                </p>
                <ul className="space-y-2 text-gray-700">
                  <li>• {t('yoga.how.evening.point1')}</li>
                  <li>• {t('yoga.how.evening.point2')}</li>
                  <li>• {t('yoga.how.evening.point3')}</li>
                  <li>• {t('yoga.how.evening.point4')}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        {/* Conditions Section */}
        <div className="bg-white rounded-xl shadow-md p-8 mb-12">
          <h2 className="text-3xl font-bold text-center text-gray-900 mb-12">
            {t('yoga.conditions.title')}
          </h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div className="bg-orange-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">💫</span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {t('yoga.conditions.for_who.title')}
              </h3>
              <p className="text-gray-700">
                {t('yoga.conditions.for_who.description')}
              </p>
            </div>
            <div className="bg-orange-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🎭</span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {t('yoga.conditions.additional.title')}
              </h3>
              <p className="text-gray-700">
                {t('yoga.conditions.additional.description')}
              </p>
            </div>
            <div className="bg-orange-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">📋</span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {t('yoga.conditions.what_to_bring.title')}
              </h3>
              <p className="text-gray-700">
                {t('yoga.conditions.what_to_bring.description')}
              </p>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center mb-12">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-8 text-white">
            <h2 className="text-3xl font-bold mb-4">
              {t('yoga.cta.title')}
            </h2>
            <p className="text-xl opacity-90 mb-8">
              {t('yoga.cta.description')}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="tel:+84349338758"
                className="bg-white text-orange-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors flex items-center justify-center"
              >
                📞 {t('home.phone')} +84 349 338 758
              </a>
              <a
                href={t('yoga.cta.telegram_link')}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-blue-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors flex items-center justify-center"
              >
                💬 {t('yoga.cta.telegram_button')}
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

export default YogaPage;
