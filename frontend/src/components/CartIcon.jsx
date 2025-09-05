import React, { useState } from 'react';
import './CartIcon.css';

const CartIcon = () => {
  const [itemCount, setItemCount] = useState(0); // Временно статичное значение

  return (
    <div className="cart-icon">
      <button className="cart-icon__button" aria-label="Корзина">
        <svg 
          className="cart-icon__svg" 
          viewBox="0 0 24 24" 
          fill="none" 
          stroke="currentColor" 
          strokeWidth="2"
        >
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        {itemCount > 0 && (
          <span className="cart-icon__badge">
            {itemCount}
          </span>
        )}
      </button>
    </div>
  );
};

export default CartIcon;
