import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { groupProductsByCategory } from '../utils/menuUtils';
import ProductCard from './ProductCard';
import LanguageSwitcher from './LanguageSwitcher';

const MenuPage = ({ menuData }) => {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState(0);

  const categories = menuData?.categories || [];
  const products = menuData?.products || [];

  console.log('🔍 MenuPage debug:', {
    categories: categories.length,
    products: products.length,
    categoriesData: categories,
    productsSample: products.slice(0, 2)
  });

  // Группируем продукты по категориям
  const groupedCategories = groupProductsByCategory(categories, products);

  console.log('🔍 Grouped categories:', groupedCategories);

  // Если нет категорий, показываем сообщение
  if (groupedCategories.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">🍽️</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Меню</h1>
          <p className="text-gray-600 mb-6">Категории не найдены</p>
          <Link 
            to="/"
            className="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors"
          >
            <ArrowLeft className="mr-2 w-4 h-4" />
            Назад
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Кнопка назад */}
            <Link 
              to="/"
              className="inline-flex items-center px-3 py-2 text-gray-600 hover:text-orange-600 transition-colors"
            >
              <ArrowLeft className="mr-2 w-4 h-4" />
              {t('back')}
            </Link>

            {/* Заголовок */}
            <h1 className="text-xl font-semibold text-gray-900">{t('menu.title')}</h1>

            {/* Переключатель языка */}
            <LanguageSwitcher />
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex space-x-8 overflow-x-auto">
            {groupedCategories.map((category, index) => (
              <button
                key={category.category_id}
                onClick={() => setActiveTab(index)}
                className={`py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors ${
                  activeTab === index
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {category.category_name}
                <span className="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                  {category.products.length}
                </span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {groupedCategories[activeTab] && (
          <div>
            {/* Category header */}
            <div className="mb-8">
              <h2 className="text-3xl font-bold text-gray-900 mb-2">
                {groupedCategories[activeTab].category_name}
              </h2>
              <p className="text-gray-600">
                {groupedCategories[activeTab].products.length} блюд
              </p>
            </div>

            {/* Products grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {groupedCategories[activeTab].products.map((product) => (
                <ProductCard key={product.product_id} product={product} />
              ))}
            </div>

            {/* Empty state */}
            {groupedCategories[activeTab].products.length === 0 && (
              <div className="text-center py-12">
                <div className="text-6xl mb-4">🍽️</div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  В этой категории пока нет блюд
                </h3>
                <p className="text-gray-600">
                  Скоро здесь появятся новые блюда
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default MenuPage;
