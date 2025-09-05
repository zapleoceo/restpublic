import React, { useState, useMemo } from 'react';
import MenuCategories from './MenuCategories';
import MenuItems from './MenuItems';
import MenuSorting from './MenuSorting';
import './MenuContent.css';

const MenuContent = ({ menuData }) => {
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [sortBy, setSortBy] = useState('popularity'); // popularity, price, name
  const [sortOrder, setSortOrder] = useState('asc'); // asc, desc

  // Получаем все категории
  const categories = useMemo(() => {
    if (!menuData?.categories) return [];
    return menuData.categories;
  }, [menuData]);

  // Получаем все блюда из выбранной категории или все блюда
  const items = useMemo(() => {
    if (!menuData?.items) return [];
    
    let filteredItems = menuData.items;
    
    // Фильтруем по категории
    if (selectedCategory) {
      filteredItems = filteredItems.filter(item => 
        item.category_id === selectedCategory.id
      );
    }

    // Сортируем
    filteredItems.sort((a, b) => {
      let comparison = 0;
      
      switch (sortBy) {
        case 'price':
          comparison = a.price - b.price;
          break;
        case 'name':
          comparison = a.name.localeCompare(b.name);
          break;
        case 'popularity':
        default:
          comparison = (b.popularity || 0) - (a.popularity || 0);
          break;
      }
      
      return sortOrder === 'desc' ? -comparison : comparison;
    });

    return filteredItems;
  }, [menuData, selectedCategory, sortBy, sortOrder]);

  const handleCategorySelect = (category) => {
    setSelectedCategory(category);
  };

  const handleSortChange = (newSortBy, newSortOrder) => {
    setSortBy(newSortBy);
    setSortOrder(newSortOrder);
  };

  return (
    <main className="menu-content">
      <div className="container">
        <div className="menu-content__header">
          <h1 className="menu-content__title">Полное меню</h1>
          <p className="menu-content__subtitle">
            Выберите категорию и наслаждайтесь нашими блюдами
          </p>
        </div>

        <div className="menu-content__body">
          {/* Сортировка */}
          <div className="menu-content__sorting">
            <MenuSorting 
              sortBy={sortBy}
              sortOrder={sortOrder}
              onSortChange={handleSortChange}
            />
          </div>

          <div className="menu-content__grid">
            {/* Категории */}
            <aside className="menu-content__categories">
              <MenuCategories 
                categories={categories}
                selectedCategory={selectedCategory}
                onCategorySelect={handleCategorySelect}
              />
            </aside>

            {/* Блюда */}
            <section className="menu-content__items">
              <MenuItems 
                items={items}
                selectedCategory={selectedCategory}
              />
            </section>
          </div>
        </div>
      </div>
    </main>
  );
};

export default MenuContent;
