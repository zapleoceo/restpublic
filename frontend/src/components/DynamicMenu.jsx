import React, { useState, useEffect, useRef } from 'react';
import menuService from '../services/menuService';

const DynamicMenu = () => {
  const [categories, setCategories] = useState([]);
  const [products, setProducts] = useState({});
  const [categoryPopularProducts, setCategoryPopularProducts] = useState({});
  const [activeCategory, setActiveCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const menuItemsRef = useRef([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [selectedCategoryProducts, setSelectedCategoryProducts] = useState([]);

  useEffect(() => {
    loadMenuData();
  }, []);

  // Функция для анимации элементов меню
  const animateMenuItems = () => {
    const menuItems = document.querySelectorAll('.menu-list__item');
    menuItems.forEach((item, index) => {
      setTimeout(() => {
        item.classList.add('animate-in');
      }, index * 100); // Задержка 100ms между элементами
    });
  };

  // Анимация при смене категории
  useEffect(() => {
    if (activeCategory && !loading) {
      // Сначала убираем анимацию со всех элементов
      const menuItems = document.querySelectorAll('.menu-list__item');
      menuItems.forEach(item => {
        item.classList.remove('animate-in');
      });
      
      // Затем запускаем анимацию для активной категории
      setTimeout(() => {
        animateMenuItems();
      }, 50);
    }
  }, [activeCategory, loading]);

  const loadMenuData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Загружаем категории
      const categoriesData = await menuService.getCategories();
      console.log('Loaded categories:', categoriesData);
      setCategories(categoriesData);

      // Загружаем продукты для каждой категории
      const productsData = {};
      const popularData = {};
      
      for (const category of categoriesData) {
        // Загружаем все продукты категории
        const categoryProducts = await menuService.getProductsByCategory(category.category_id);
        productsData[category.category_id] = categoryProducts;
        
        // Загружаем популярные продукты категории
        const categoryPopular = await menuService.getPopularProductsByCategory(category.category_id, 5);
        console.log(`Popular products for category ${category.category_id}:`, categoryPopular);
        popularData[category.category_id] = categoryPopular;
      }
      
      setProducts(productsData);
      setCategoryPopularProducts(popularData);
      
      console.log('Final products data:', productsData);
      console.log('Final popular data:', popularData);

      // Устанавливаем первую категорию как активную
      if (categoriesData.length > 0) {
        setActiveCategory(categoriesData[0].category_id);
        setSelectedCategory(categoriesData[0]);
        setSelectedCategoryProducts(productsData[categoriesData[0].category_id] || []);
        console.log('Active category set to:', categoriesData[0].category_id);
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
    const category = categories.find(cat => cat.category_id === categoryId);
    if (category) {
      setSelectedCategory(category);
      // Показываем все продукты из выбранной категории
      setSelectedCategoryProducts(products[categoryId] || []);
    }
    
    // Интеграция с Template JS - активируем соответствующий таб
    setTimeout(() => {
      const tabElement = document.getElementById(`tab-${categoryId}`);
      if (tabElement) {
        // Активируем таб через Template JS логику
        const tabLinks = document.querySelectorAll('.tab-nav__list a');
        const tabPanels = document.querySelectorAll('.tab-content__item');
        
        // Сбрасываем все табы
        tabLinks.forEach(link => {
          link.setAttribute('tabindex', '-1');
          link.setAttribute('aria-selected', 'false');
          link.parentNode.removeAttribute('data-tab-active');
          link.removeAttribute('data-tab-active');
        });
        
        // Активируем нужный таб
        const activeLink = document.querySelector(`a[href="#tab-${categoryId}"]`);
        if (activeLink) {
          activeLink.setAttribute('tabindex', '0');
          activeLink.setAttribute('aria-selected', 'true');
          activeLink.parentNode.setAttribute('data-tab-active', '');
          activeLink.setAttribute('data-tab-active', '');
        }
        
        // Активируем соответствующий панель
        tabPanels.forEach(panel => {
          if (panel.id === `tab-${categoryId}`) {
            panel.setAttribute('aria-hidden', 'false');
            panel.setAttribute('data-tab-active', '');
          } else {
            panel.setAttribute('aria-hidden', 'true');
            panel.removeAttribute('data-tab-active');
          }
        });
      }
    }, 50);
  };

  if (loading) {
    return (
      <section id="menu" className="container s-menu target-section">
        <div className="row s-menu__content">
          <div className="column xl-4 lg-5 md-12 s-menu__content-start">
            <div className="section-header" data-num="02">
              <div className="menu-loader__skeleton menu-loader__header"></div>
            </div>
            <nav className="tab-nav">
              <ul className="tab-nav__list">
                {[1, 2, 3, 4].map((i) => (
                  <li key={i}>
                    <div className="menu-loader__skeleton menu-loader__nav-item"></div>
                  </li>
                ))}
              </ul>
            </nav>
          </div>
          <div className="column xl-6 lg-6 md-12 s-menu__content-end">
            <div className="tab-content menu-block">
              <div className="menu-loader__category">
                <div className="menu-loader__skeleton menu-loader__category-title"></div>
                <div className="menu-loader__items">
                  {[1, 2, 3, 4, 5].map((i) => (
                    <div key={i} className="menu-loader__item">
                      <div className="menu-loader__skeleton menu-loader__item-name"></div>
                      <div className="menu-loader__skeleton menu-loader__item-price"></div>
                    </div>
                  ))}
                </div>
              </div>
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
    console.log('No categories found, showing empty state');
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

  console.log('Rendering DynamicMenu with:', {
    categoriesCount: categories.length,
    activeCategory,
    selectedCategory: selectedCategory?.category_name,
    selectedCategoryProductsCount: selectedCategoryProducts.length,
    popularProductsKeys: Object.keys(categoryPopularProducts),
    popularProductsCounts: Object.entries(categoryPopularProducts).map(([id, products]) => [id, products.length])
  });

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
            {/* Создаем элементы для каждой категории с нужными ID */}
            {categories.map((category) => (
              <div 
                key={category.category_id}
                id={`tab-${category.category_id}`}
                className={`menu-block__group tab-content__item ${activeCategory === category.category_id ? 'active' : ''}`}
                aria-hidden={activeCategory === category.category_id ? 'false' : 'true'}
                data-tab-active={activeCategory === category.category_id ? '' : undefined}
              >
                <h6 className="menu-block__cat-name">
                  {category.category_name} top 5 positions
                </h6>
                
                {categoryPopularProducts[category.category_id] && categoryPopularProducts[category.category_id].length > 0 ? (
                  <ul className="menu-list">
                    {categoryPopularProducts[category.category_id].slice(0, 5).map((product) => (
                      <li key={product.product_id} className="menu-list__item">
                        <div className="menu-list__item-desc">
                          <h4>{product.product_name}</h4>
                          {product.product_production_description && (
                            <p>{product.product_production_description}</p>
                          )}
                        </div>
                        <div className="menu-list__item-price">
                          {product.price && product.price['1'] ? (product.price['1'] / 100).toFixed(0) : '0'}
                        </div>
                      </li>
                    ))}
                  </ul>
                ) : (
                  <div className="empty-category">
                    <p>No popular products in this category</p>
                  </div>
                )}
                
              </div>
            ))}
          </div>
        </div>
        
        {/* Full Menu Button - внизу row s-menu__content */}
        <div className="column xl-12" style={{ textAlign: 'center', marginTop: '-3rem', marginBottom: '6rem' }}>
          <a href="/menu" className="btn btn--primary">
            Full Menu
          </a>
        </div>
      </div>
    </section>
  );
};

export default DynamicMenu;
