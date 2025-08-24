import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ChevronLeft, ChevronRight, Phone, ArrowLeft } from 'lucide-react';
import { Link } from 'react-router-dom';
import ContactSection from './ContactSection';

const YogaPage = () => {
  const { t } = useTranslation();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  const images = [
    '/img/yoga/button.jpg'
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
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link 
              to="/" 
              className="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
            >
              <ArrowLeft className="h-5 w-5 mr-2" />
              {t('back')}
            </Link>
            <h1 className="text-xl font-semibold text-gray-900">
              {t('yoga.title')}
            </h1>
            <div className="w-20"></div>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="bg-gradient-to-r from-purple-500 to-purple-600 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-4xl font-bold mb-4">
            🕉️ {t('yoga.title')}
          </h1>
          <p className="text-xl opacity-90 max-w-3xl mx-auto">
            {t('yoga.subtitle')}
          </p>
        </div>
      </section>

      {/* Image Slider */}
      <section className="py-12 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="relative">
            <div className="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-lg">
              <img
                src={images[currentImageIndex]}
                alt={t('yoga.title')}
                className="w-full h-96 object-cover"
              />
            </div>
            
            {images.length > 1 && (
              <>
                <button
                  onClick={prevImage}
                  className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all"
                >
                  <ChevronLeft className="h-6 w-6 text-gray-700" />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all"
                >
                  <ChevronRight className="h-6 w-6 text-gray-700" />
                </button>
              </>
            )}
          </div>
          
          {images.length > 1 && (
            <div className="flex justify-center mt-4 space-x-2">
              {images.map((_, index) => (
                <button
                  key={index}
                  onClick={() => goToImage(index)}
                  className={`w-3 h-3 rounded-full transition-colors ${
                    index === currentImageIndex ? 'bg-purple-600' : 'bg-gray-300'
                  }`}
                />
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Description Section */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12">
            <div>
              <h2 className="text-3xl font-bold text-gray-900 mb-6">
                {t('yoga.about.title')}
              </h2>
              <p className="text-gray-700 text-lg leading-relaxed mb-6">
                {t('yoga.about.description')}
              </p>
              <div className="space-y-4">
                <div className="flex items-start">
                  <div className="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span className="text-purple-600 text-sm font-bold">1</span>
                  </div>
                  <p className="text-gray-700">{t('yoga.about.point1')}</p>
                </div>
                <div className="flex items-start">
                  <div className="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span className="text-purple-600 text-sm font-bold">2</span>
                  </div>
                  <p className="text-gray-700">{t('yoga.about.point2')}</p>
                </div>
                <div className="flex items-start">
                  <div className="flex-shrink-0 w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                    <span className="text-purple-600 text-sm font-bold">3</span>
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
        </div>
      </section>

      {/* Conditions Section */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-bold text-center text-gray-900 mb-12">
            {t('yoga.conditions.title')}
          </h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div className="bg-purple-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">💫</span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {t('yoga.conditions.for_who.title')}
              </h3>
              <p className="text-gray-700">
                {t('yoga.conditions.for_who.description')}
              </p>
            </div>
            <div className="bg-purple-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🎭</span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                {t('yoga.conditions.additional.title')}
              </h3>
              <p className="text-gray-700">
                {t('yoga.conditions.additional.description')}
              </p>
            </div>
            <div className="bg-purple-50 rounded-xl p-6 text-center">
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
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
      </section>

      {/* CTA Section */}
      <section className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-8 text-white">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold mb-4">
            {t('yoga.cta.title')}
          </h2>
          <p className="text-xl opacity-90 mb-8">
            {t('yoga.cta.description')}
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a
              href={`tel:${t('home.phone')}`}
              className="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors flex items-center justify-center"
            >
              <Phone className="h-5 w-5 mr-2" />
              {t('home.phone')}
            </a>
            <a
              href={t('yoga.cta.telegram_link')}
              target="_blank"
              rel="noopener noreferrer"
              className="bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-800 transition-colors flex items-center justify-center"
            >
              💬 {t('yoga.cta.telegram_button')}
            </a>
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <ContactSection />
    </div>
  );
};

export default YogaPage;
