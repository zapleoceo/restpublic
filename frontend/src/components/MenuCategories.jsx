import React from 'react';
import './MenuCategories.css';

const MenuCategories = ({ categories, selectedCategory, onCategorySelect }) => {
  const handleCategoryClick = (category) => {
    onCategorySelect(category);
  };

  const handleShowAll = () => {
    onCategorySelect(null);
  };

  return (
    <div className="menu-categories">
      <h3 className="menu-categories__title">Категории</h3>
      
      <ul className="menu-categories__list">
        <li className="menu-categories__item">
          <button 
            className={`menu-categories__button ${!selectedCategory ? 'menu-categories__button--active' : ''}`}
            onClick={handleShowAll}
          >
            Все блюда
          </button>
        </li>
        
        {categories.map((category) => (
          <li key={category.id} className="menu-categories__item">
            <button 
              className={`menu-categories__button ${selectedCategory?.id === category.id ? 'menu-categories__button--active' : ''}`}
              onClick={() => handleCategoryClick(category)}
            >
              {category.name}
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default MenuCategories;
