import React, { useState, useEffect } from 'react';
import menuService from '../services/menuService';

const DynamicMenu = () => {
  const [categories, setCategories] = useState([]);
  const [products, setProducts] = useState({});
  const [popularProducts, setPopularProducts] = useState([]);
  const [activeCategory, setActiveCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadMenuData();
  }, []);

  const loadMenuData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Загружаем категории
      const categoriesData = await menuService.getCategories();
      setCategories(categoriesData);

      // Загружаем популярные продукты
      const popularData = await menuService.getPopularProducts(5);
      setPopularProducts(popularData);

      // Загружаем продукты для каждой категории
      const productsData = {};
      for (const category of categoriesData) {
        const categoryProducts = await menuService.getProductsByCategory(category.category_id);
        productsData[category.category_id] = categoryProducts;
      }
      setProducts(productsData);

      // Устанавливаем первую категорию как активную
      if (categoriesData.length > 0) {
        setActiveCategory(categoriesData[0].category_id);
      }

    } catch (err) {
      console.error('Error loading menu data:', err);
      setError('Failed to load menu data');
    } finally {
      setLoading(false);
    }
  };

  const handleCategoryClick = (categoryId) => {
    setActiveCategory(categoryId);
  };

  if (loading) {
    return (
      <section id="menu" className="container s-menu target-section">
        <div className="row s-menu__content">
          <div className="column xl-12">
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Loading menu...</p>
            </div>
          </div>
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section id="menu" className="container s-menu target-section">
        <div className="row s-menu__content">
          <div className="column xl-12">
            <div className="error-message">
              <p>Error: {error}</p>
              <button onClick={loadMenuData} className="btn btn--primary">
                Try Again
              </button>
            </div>
          </div>
        </div>
      </section>
    );
  }

  if (categories.length === 0) {
    return (
      <section id="menu" className="container s-menu target-section">
        <div className="row s-menu__content">
          <div className="column xl-12">
            <div className="empty-menu">
              <p>No menu categories available</p>
            </div>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section id="menu" className="container s-menu target-section">
      <div className="row s-menu__content">
        <div className="column xl-4 lg-5 md-12 s-menu__content-start">
          <div className="section-header" data-num="02">
            <h2 className="text-display-title">Our Menu</h2>
          </div>

          <nav className="tab-nav">
            <ul className="tab-nav__list">
              {categories.map((category) => (
                <li key={category.category_id}>
                  <a 
                    href={`#tab-${category.category_id}`}
                    onClick={(e) => {
                      e.preventDefault();
                      handleCategoryClick(category.category_id);
                    }}
                    className={activeCategory === category.category_id ? 'active' : ''}
                  >
                    <span>{category.category_name}</span>
                    <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
                    </svg>
                  </a>
                </li>
              ))}
            </ul>
          </nav>
        </div>

        <div className="column xl-6 lg-6 md-12 s-menu__content-end">
          <div className="tab-content menu-block">
            {/* Популярные продукты - показываем в начале */}
            <div className="menu-block__group tab-content__item active">
              <h6 className="menu-block__cat-name">Most Popular</h6>
              {popularProducts.length > 0 ? (
                <ul className="menu-list">
                  {popularProducts.map((product) => (
                    <li key={product.product_id} className="menu-list__item">
                      <div className="menu-list__item-desc">
                        <h4>{product.product_name}</h4>
                        {product.product_production_description && (
                          <p>{product.product_production_description}</p>
                        )}
                      </div>
                      <div className="menu-list__item-price">
                        <span>$</span>
                        {product.price_formatted || menuService.formatPrice(product.price)}
                      </div>
                    </li>
                  ))}
                </ul>
              ) : (
                <div className="empty-category">
                  <p>No popular products available</p>
                </div>
              )}
            </div>

            {/* Категории продуктов */}
            {categories.map((category) => {
              const categoryProducts = products[category.category_id] || [];
              const isActive = activeCategory === category.category_id;
              
              return (
                <div 
                  key={category.category_id}
                  id={`tab-${category.category_id}`} 
                  className={`menu-block__group tab-content__item ${isActive ? 'active' : ''}`}
                >
                  <h6 className="menu-block__cat-name">
                    {category.category_name}
                  </h6>
                  
                  {categoryProducts.length > 0 ? (
                    <ul className="menu-list">
                      {categoryProducts.map((product) => (
                        <li key={product.product_id} className="menu-list__item">
                          <div className="menu-list__item-desc">
                                                    <h4>{product.product_name}</h4>
                        {product.product_production_description && (
                          <p>{product.product_production_description}</p>
                        )}
                      </div>
                      <div className="menu-list__item-price">
                        <span>$</span>
                        {product.price_formatted || menuService.formatPrice(product.price)}
                      </div>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <div className="empty-category">
                      <p>No products in this category</p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};

export default DynamicMenu;
