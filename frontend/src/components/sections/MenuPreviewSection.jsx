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
                <li key={category.id} {...(activeTab === index ? { 'data-tab-active': true } : {})}>
                  <a 
                    href={`#tab-${category.id}`}
                    onClick={(e) => {
                      e.preventDefault();
                      setActiveTab(index);
                    }}
                  >
                    <span>{category.name}</span>
                    <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
                    </svg>
                  </a>
                </li>
              ))}
              <li>
                <Link to="/menu" className="view-all-link">
                  <span>{t('menu.view_all') || "Полное меню"}</span>
                  <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
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
                className="menu-block__group tab-content__item"
                {...(activeTab === index ? { 'data-tab-active': true } : {})}
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
