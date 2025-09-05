import React from 'react';
import './MenuSorting.css';

const MenuSorting = ({ sortBy, sortOrder, onSortChange }) => {
  const sortOptions = [
    { value: 'popularity', label: 'По популярности' },
    { value: 'price', label: 'По цене' },
    { value: 'name', label: 'По алфавиту' }
  ];

  const handleSortChange = (newSortBy) => {
    if (newSortBy === sortBy) {
      // Если та же сортировка, меняем порядок
      onSortChange(newSortBy, sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      // Новая сортировка, начинаем с возрастающего порядка
      onSortChange(newSortBy, 'asc');
    }
  };

  const getSortIcon = (optionValue) => {
    if (sortBy !== optionValue) {
      return <span className="sort-icon">↕</span>;
    }
    
    return sortOrder === 'asc' ? 
      <span className="sort-icon">↑</span> : 
      <span className="sort-icon">↓</span>;
  };

  return (
    <div className="menu-sorting">
      <span className="menu-sorting__label">Сортировка:</span>
      
      <div className="menu-sorting__options">
        {sortOptions.map((option) => (
          <button
            key={option.value}
            className={`menu-sorting__button ${sortBy === option.value ? 'menu-sorting__button--active' : ''}`}
            onClick={() => handleSortChange(option.value)}
          >
            {option.label}
            {getSortIcon(option.value)}
          </button>
        ))}
      </div>
    </div>
  );
};

export default MenuSorting;
