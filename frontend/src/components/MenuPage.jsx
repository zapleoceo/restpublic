import React, { useState, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useParams, useLocation } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { groupProductsByCategory, sortProducts } from '../utils/menuUtils';
import { validateTableId, formatTableNumber } from '../utils/tableUtils';
import { menuService } from '../services/menuService';
import ProductCard from './ProductCard';
import LanguageSwitcher from './LanguageSwitcher';
import SortSelector from './SortSelector';
import LoadingSpinner from './LoadingSpinner';

const MenuPage = ({ menuData }) => {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState(0);
  const [sortType, setSortType] = useState('popularity'); // По умолчанию по популярности
  const [popularityData, setPopularityData] = useState({});
  const [loadingPopularity, setLoadingPopularity] = useState(false);
  const { tableId } = useParams();
  const location = useLocation();
  
  // Определяем, открыта ли страница через fast access
  const isFastAccess = location.pathname.includes('/fast/');
  const currentTableId = tableId;

  const categories = menuData?.categories || [];
  const products = menuData?.products || [];

  console.log('🔍 MenuPage debug:', {
    categories: categories.length,
    products: products.length,
    categoriesData: categories,
    productsSample: products.slice(0, 2)
  });

  // Загружаем данные о популярности при монтировании компонента
  useEffect(() => {
    const loadPopularityData = async () => {
      setLoadingPopularity(true);
      try {
        const data = await menuService.getPopularityData();
        setPopularityData(data.productPopularity || {});
        console.log('📊 Popularity data loaded:', data);
      } catch (error) {
        console.error('❌ Error loading popularity data:', error);
        setPopularityData({});
      } finally {
        setLoadingPopularity(false);
      }
    };

    loadPopularityData();
  }, []);

  // Группируем продукты по категориям и применяем сортировку
  const groupedCategories = useMemo(() => {
    console.log('🔄 Recalculating groupedCategories with sortType:', sortType);
    const result = groupProductsByCategory(categories, products).map(category => {
      const sortedProducts = sortProducts(category.products, sortType, popularityData);
      console.log(`📦 Category "${category.category_name}": ${sortedProducts.length} products sorted by ${sortType}`);
      if (sortedProducts.length > 0) {
        console.log(`   First product: ${sortedProducts[0].product_name}`);
        console.log(`   Last product: ${sortedProducts[sortedProducts.length - 1].product_name}`);
      }
      return {
        ...category,
        products: sortedProducts
      };
    });
    console.log('✅ GroupedCategories recalculated');
    return result;
  }, [categories, products, sortType, popularityData]);

  console.log('🔍 Grouped categories with sorting:', groupedCategories);

  // Обработчик изменения сортировки
  const handleSortChange = (newSortType) => {
    console.log('🔄 Changing sort type from', sortType, 'to', newSortType);
    setSortType(newSortType);
  };

  // Если нет категорий, показываем сообщение
  if (groupedCategories.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">🍽️</div>
                      <h1 className="text-2xl font-bold text-gray-900 mb-4">{t('menu.title')}</h1>
          <p className="text-gray-600 mb-6">{t('no_categories')}</p>
          <Link 
            to={isFastAccess ? `/fast/${currentTableId}` : "/"}
            className="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors"
          >
            <ArrowLeft className="mr-2 w-4 h-4" />
            {t('back')}
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
              to={isFastAccess ? `/fast/${currentTableId}` : "/"}
              className="inline-flex items-center px-3 py-2 text-gray-600 hover:text-orange-600 transition-colors"
            >
              <ArrowLeft className="mr-2 w-4 h-4" />
              {t('back')}
            </Link>

            {/* Заголовок */}
            <div className="text-center">
              <h1 className="text-xl font-semibold text-gray-900">{t('menu.title')}</h1>
              {isFastAccess && currentTableId && (
                <p className="text-sm text-orange-600 font-medium">
                  {formatTableNumber(currentTableId)}
                </p>
              )}
            </div>

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
            {/* Category header with sort selector */}
            <div className="mb-8">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h2 className="text-3xl font-bold text-gray-900 mb-2">
                    {groupedCategories[activeTab].category_name}
                  </h2>
                  <p className="text-gray-600">
                    {groupedCategories[activeTab].products.length} {t('menu.dishes')}
                  </p>
                </div>
                <div className="flex items-center space-x-4">
                  {loadingPopularity && (
                    <div className="flex items-center space-x-2 text-sm text-gray-500">
                      <LoadingSpinner size="sm" compact={true} />
                      <span>{t('loading_popularity')}</span>
                    </div>
                  )}
                  <SortSelector 
                    sortType={sortType} 
                    onSortChange={handleSortChange} 
                  />
                </div>
              </div>
            </div>

            {/* Products grid */}
            <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
              {groupedCategories[activeTab].products.map((product) => (
                <ProductCard key={product.product_id} product={product} />
              ))}
            </div>

            {/* Empty state */}
            {groupedCategories[activeTab].products.length === 0 && (
              <div className="text-center py-12">
                <div className="text-6xl mb-4">🍽️</div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {t('no_dishes_in_category')}
                </h3>
                <p className="text-gray-600">
                  {t('new_dishes_coming_soon')}
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
