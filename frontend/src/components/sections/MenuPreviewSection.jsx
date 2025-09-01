import React, { useState } from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useMenuData } from '../../hooks/useMenuData';
import { formatPrice } from '../../utils/formatters';
import { Link } from 'react-router-dom';

export const MenuPreviewSection = () => {
  const { t } = useTranslation();
  const { menuData, loading } = useMenuData();
  const [activeTab, setActiveTab] = useState(0);

  if (loading || !menuData) {
    return (
      <section id="menu" className="container s-menu target-section">
        <div className="text-center">
          <div className="loading-spinner"></div>
        </div>
      </section>
    );
  }

  // Получаем 5 самых популярных категорий
  const popularCategories = menuData.categories?.slice(0, 5).map(category => {
    // Заменяем "Signature Blends" на "Популярное"
    if (category.name === 'Signature Blends') {
      return { ...category, name: 'Популярное' };
    }
    return category;
  }) || [];
  
  // Для каждой категории берем 5 самых популярных блюд
  const getPopularProducts = (categoryId) => {
    const categoryProducts = menuData.products?.filter(p => p.category_id === categoryId) || [];
    return categoryProducts.slice(0, 5);
  };

  return (
    <section id="menu" className="container s-menu target-section">
      <div className="row s-menu__content">
        <div className="column xl-4 lg-5 md-12 s-menu__content-start">
          <div className="section-header" data-num="02">
            <h2 className="text-display-title">{t('menu.title') || "Меню ресторана"}</h2>
          </div>
          
          <nav className="tab-nav">
            <ul className="tab-nav__list">
              {popularCategories.map((category, index) => (
                <li key={category.id}>
                  <a 
                    href={`#tab-${category.id}`}
                    onClick={(e) => {
                      e.preventDefault();
                      setActiveTab(index);
                    }}
                    className={activeTab === index ? 'active' : ''}
                  >
                    <span>{category.name}</span>
                    <svg>
                      <path d="M9 5l7 7-7 7" />
                    </svg>
                  </a>
                </li>
              ))}
              <li>
                <Link to="/menu" className="view-all-link">
                  <span>{t('menu.view_all') || "Полное меню"}</span>
                  <svg>
                    <path d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </li>
            </ul>
          </nav>
        </div>
        
        <div className="column xl-6 lg-6 md-12 s-menu__content-end">
          <div className="tab-content menu-block">
            {popularCategories.map((category, index) => (
              <div 
                key={category.id} 
                id={`tab-${category.id}`} 
                className={`menu-block__group tab-content__item ${
                  activeTab === index ? 'active' : ''
                }`}
              >
                <h6 className="menu-block__cat-name">{category.name}</h6>
                <ul className="menu-list">
                  {getPopularProducts(category.id).map(product => (
                    <li key={product.id} className="menu-list__item">
                      <div className="menu-list__item-desc">
                        <h4>{product.name}</h4>
                      </div>
                      <div className="menu-list__item-price">
                        <span>₫</span>{formatPrice(product.price)}
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
