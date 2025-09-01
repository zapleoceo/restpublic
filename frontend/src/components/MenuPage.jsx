import React, { useState, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useParams, useLocation } from 'react-router-dom';
import { ArrowLeft, CreditCard, LogOut, User } from 'lucide-react';
import { groupProductsByCategory, sortProducts } from '../utils/menuUtils';
import { validateTableId, formatTableNumber } from '../utils/tableUtils';
import { menuService } from '../services/menuService';
import ProductCard from './ProductCard';
import { Header, Footer } from './layout';
import { SEOHead } from './seo/SEOHead';
import SortSelector from './SortSelector';
import LoadingSpinner from './LoadingSpinner';
import CartButton from './CartButton';
import CartModal from './CartModal';
import MyOrdersModal from './MyOrdersModal';
import AuthModal from './AuthModal';
import { useCart } from '../contexts/CartContext';

const MenuPage = ({ menuData }) => {
  const { t } = useTranslation();
  const { session, getCurrentSession, setSession } = useCart();
  const [activeTab, setActiveTab] = useState(0);
  const [sortType, setSortType] = useState('popularity'); // По умолчанию по популярности
  const [popularityData, setPopularityData] = useState({});
  const [loadingPopularity, setLoadingPopularity] = useState(false);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [showMyOrders, setShowMyOrders] = useState(false);
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [showUserTooltip, setShowUserTooltip] = useState(false);
  const [tooltipTimeout, setTooltipTimeout] = useState(null);
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

  // Обработка URL параметров от Telegram бота
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionParam = urlParams.get('session');
    
    if (sessionParam) {
      try {
        const sessionData = JSON.parse(decodeURIComponent(sessionParam));
        console.log('🔗 Received session from URL:', sessionData);
        
        // Сохраняем сессию
        setSession(sessionData);
        
        // Показываем модальное окно завершения регистрации
        setShowAuthModal(true);
        
        // Очищаем URL параметры
        window.history.replaceState({}, document.title, window.location.pathname);
      } catch (error) {
        console.error('❌ Error parsing session from URL:', error);
      }
    }
  }, [setSession]);

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

  // Обработчики корзины
  const handleCartOpen = () => setIsCartOpen(true);

  const handleLogout = () => {
    setSession(null);
    setShowUserTooltip(false);
  };

  // Обработчики для всплывающей подсказки с задержкой
  const handleMouseEnter = () => {
    // Очищаем таймаут закрытия при наведении мыши
    if (tooltipTimeout) {
      clearTimeout(tooltipTimeout);
      setTooltipTimeout(null);
    }
    setShowUserTooltip(true);
  };

  const handleMouseLeave = () => {
    // Устанавливаем таймаут закрытия только при уходе мыши
    const timeout = setTimeout(() => {
      setShowUserTooltip(false);
    }, 100); // Минимальная задержка
    setTooltipTimeout(timeout);
  };

  // Обработчик для всего контейнера дропдауна
  const handleDropdownMouseEnter = () => {
    // Очищаем таймаут закрытия при наведении на дропдаун
    if (tooltipTimeout) {
      clearTimeout(tooltipTimeout);
      setTooltipTimeout(null);
    }
    setShowUserTooltip(true);
  };

  const handleDropdownMouseLeave = () => {
    // Закрываем дропдаун при уходе мыши с него
    const timeout = setTimeout(() => {
      setShowUserTooltip(false);
    }, 100);
    setTooltipTimeout(timeout);
  };

  // Закрываем всплывающую подсказку при клике вне её
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (showUserTooltip && !event.target.closest('.user-tooltip-container')) {
        setShowUserTooltip(false);
        if (tooltipTimeout) {
          clearTimeout(tooltipTimeout);
          setTooltipTimeout(null);
        }
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showUserTooltip, tooltipTimeout]);

  // Очищаем таймаут при размонтировании
  useEffect(() => {
    return () => {
      if (tooltipTimeout) {
        clearTimeout(tooltipTimeout);
      }
    };
  }, [tooltipTimeout]);
  const handleCartClose = () => setIsCartOpen(false);

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
    <div className="menu-page min-h-screen bg-neutral-50">
      <SEOHead 
        title="Меню - North Republic"
        description="Полное меню ресторана North Republic с блюдами и напитками"
        keywords="меню, блюда, напитки, ресторан, North Republic"
      />
      
      <Header />
      
      <main className="main-content pt-16">
        <div className="container mx-auto px-4 py-8">
          {/* Заголовок страницы */}
          <div className="mb-8">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center space-x-4">
                <Link 
                  to={isFastAccess ? `/fast/${currentTableId}` : '/'}
                  className="flex items-center space-x-2 text-primary-600 hover:text-primary-700 transition-colors"
                >
                  <ArrowLeft className="w-5 h-5" />
                  <span className="font-medium">
                    {isFastAccess ? t('menu.back_to_table') : t('menu.back_to_home')}
                  </span>
                </Link>
                
                {isFastAccess && currentTableId && (
                  <div className="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                    {t('menu.table')} {formatTableNumber(currentTableId)}
                  </div>
                )}
              </div>
              
              <div className="flex items-center space-x-4">
                <SortSelector 
                  value={sortType} 
                  onChange={setSortType} 
                />
                
                <CartButton onClick={() => setIsCartOpen(true)} />
              </div>
            </div>
            
            <h1 className="text-3xl font-serif font-bold text-primary-900">
              {t('menu.title')}
            </h1>
            <p className="text-neutral-600 mt-2">
              {t('menu.subtitle')}
            </p>
          </div>

          {/* Навигация по категориям */}
          {groupedCategories.length > 0 && (
            <div className="mb-8">
              <div className="flex flex-wrap gap-2">
                {groupedCategories.map((category, index) => (
                  <button
                    key={category.category_id}
                    onClick={() => setActiveTab(index)}
                    className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                      activeTab === index
                        ? 'bg-primary-500 text-white'
                        : 'bg-white text-neutral-700 hover:bg-neutral-100 border border-neutral-200'
                    }`}
                  >
                    {category.category_name}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Контент категории */}
          {loadingPopularity ? (
            <div className="flex justify-center py-12">
              <LoadingSpinner />
            </div>
          ) : groupedCategories.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {groupedCategories[activeTab]?.products.map((product) => (
                <ProductCard
                  key={product.product_id}
                  product={product}
                  tableId={currentTableId}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <div className="text-6xl mb-4">🍽️</div>
              <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
                {t('menu.no_products')}
              </h3>
              <p className="text-neutral-600">
                {t('menu.no_products_desc')}
              </p>
            </div>
          )}
        </div>
      </main>
      
      <Footer />

      {/* Модальные окна */}
      <CartModal 
        isOpen={isCartOpen} 
        onClose={() => setIsCartOpen(false)} 
      />
      
      <MyOrdersModal 
        isOpen={showMyOrders} 
        onClose={() => setShowMyOrders(false)} 
      />
      
      <AuthModal 
        isOpen={showAuthModal} 
        onClose={() => setShowAuthModal(false)} 
      />
    </div>
  );
};

export default MenuPage;
