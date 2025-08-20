import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';

const HomePage = () => {
  const { t } = useTranslation();

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center">
      <div className="text-center max-w-2xl mx-auto px-6">
        {/* Логотип или иконка */}
        <div className="mb-8">
          <div className="text-8xl mb-4">🍽️</div>
        </div>

        {/* Заголовок */}
        <h1 className="text-5xl font-bold text-gray-900 mb-6">
          GoodZone
        </h1>

        {/* Подзаголовок */}
        <p className="text-xl text-gray-600 mb-12 leading-relaxed">
          {t('welcome.subtitle')}
        </p>

        {/* Кнопка */}
        <div className="flex justify-center">
          <Link 
            to="/m"
            className="inline-flex items-center px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white text-lg font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl"
          >
            <span>Открыть меню</span>
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>
        </div>

        {/* Дополнительная информация */}
        <div className="mt-16 text-sm text-gray-500">
          <p>Уютная атмосфера и изысканные блюда</p>
        </div>
      </div>
    </div>
  );
};

export default HomePage;
