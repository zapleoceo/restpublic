import React, { useState } from 'react';
import { SectionWrapper } from '../ui/SectionWrapper';
import { SectionHeader } from '../ui/SectionHeader';
import { BaseButton } from '../ui/BaseButton';
import { useTranslation } from '../../hooks/useTranslation';
import { useMenuData } from '../../hooks/useMenuData';
import { formatPrice } from '../../utils/formatters';
import { Link } from 'react-router-dom';

export const MenuPreviewSection = () => {
  const { t } = useTranslation();
  const { menuData, loading } = useMenuData();
  const [activeCategory, setActiveCategory] = useState(0);

  if (loading || !menuData) {
    return (
      <SectionWrapper id="menu" className="s-menu">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto"></div>
        </div>
      </SectionWrapper>
    );
  }

  // Получаем 5 самых популярных категорий
  const popularCategories = menuData.categories?.slice(0, 5) || [];
  
  // Для каждой категории берем 5 самых популярных блюд
  const getPopularProducts = (categoryId) => {
    const categoryProducts = menuData.products?.filter(p => p.category_id === categoryId) || [];
    return categoryProducts.slice(0, 5);
  };

  return (
    <SectionWrapper id="menu" className="s-menu">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div className="s-menu__content-start">
          <SectionHeader number="02" title={t('menu.title') || "Меню"} />
          
          <nav className="tab-nav">
            <ul className="tab-nav__list space-y-2">
              {popularCategories.map((category, index) => (
                <li key={category.id}>
                  <button
                    onClick={() => setActiveCategory(index)}
                    className={`w-full text-left p-4 rounded-lg transition-colors ${
                      activeCategory === index 
                        ? 'bg-primary-500 text-white' 
                        : 'bg-neutral-50 hover:bg-neutral-100 text-neutral-700'
                    }`}
                  >
                    <span className="font-medium">{category.name}</span>
                    <svg className="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </li>
              ))}
              <li>
                <Link 
                  to="/menu" 
                  className="w-full text-left p-4 rounded-lg transition-colors bg-secondary-500 hover:bg-secondary-600 text-white font-medium block"
                >
                  <span>{t('menu.view_all') || "Полное меню"}</span>
                  <svg className="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </li>
            </ul>
          </nav>
        </div>
        
        <div className="s-menu__content-end">
          <div className="tab-content menu-block">
            {popularCategories.map((category, index) => (
              <div 
                key={category.id} 
                className={`menu-block__group tab-content__item ${
                  activeCategory === index ? 'block' : 'hidden'
                }`}
              >
                <h6 className="menu-block__cat-name text-xl font-serif font-bold text-primary-900 mb-6">
                  {category.name}
                </h6>
                <ul className="menu-list space-y-4">
                  {getPopularProducts(category.id).map(product => (
                    <li key={product.id} className="menu-list__item flex justify-between items-center p-4 bg-white rounded-lg shadow-sm">
                      <div className="menu-list__item-desc">
                        <h4 className="font-medium text-neutral-900">{product.name}</h4>
                        {product.description && (
                          <p className="text-sm text-neutral-600 mt-1">{product.description}</p>
                        )}
                      </div>
                      <div className="menu-list__item-price text-lg font-bold text-primary-600">
                        {formatPrice(product.price)}
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </div>
    </SectionWrapper>
  );
};
