import React from 'react';
import './MenuItems.css';

const MenuItems = ({ items, selectedCategory }) => {
  if (!items || items.length === 0) {
    return (
      <div className="menu-items">
        <div className="menu-items__empty">
          <h3>Блюда не найдены</h3>
          <p>
            {selectedCategory 
              ? `В категории "${selectedCategory.name}" пока нет блюд`
              : 'В меню пока нет блюд'
            }
          </p>
        </div>
      </div>
    );
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(price);
  };

  return (
    <div className="menu-items">
      {selectedCategory && (
        <div className="menu-items__header">
          <h2 className="menu-items__category-title">
            {selectedCategory.name}
          </h2>
          <p className="menu-items__category-description">
            {selectedCategory.description || `Блюда из категории "${selectedCategory.name}"`}
          </p>
        </div>
      )}

      <div className="menu-items__grid">
        {items.map((item) => (
          <div key={item.id} className="menu-item">
            <div className="menu-item__image">
              {item.image ? (
                <img 
                  src={item.image} 
                  alt={item.name}
                  loading="lazy"
                />
              ) : (
                <div className="menu-item__placeholder">
                  <span>Фото</span>
                </div>
              )}
            </div>

            <div className="menu-item__content">
              <div className="menu-item__header">
                <h3 className="menu-item__name">{item.name}</h3>
                <span className="menu-item__price">
                  {formatPrice(item.price)}
                </span>
              </div>

              {item.description && (
                <p className="menu-item__description">
                  {item.description}
                </p>
              )}

              {item.ingredients && (
                <div className="menu-item__ingredients">
                  <span className="menu-item__ingredients-label">Состав:</span>
                  <span className="menu-item__ingredients-text">
                    {item.ingredients}
                  </span>
                </div>
              )}

              <div className="menu-item__actions">
                <button className="menu-item__add-btn">
                  Добавить в корзину
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default MenuItems;
