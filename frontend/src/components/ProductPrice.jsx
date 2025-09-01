import React from 'react';
import { formatPrice, getMainPrice } from '../utils/priceUtils';

const ProductPrice = ({ price, className = '' }) => {
  const mainPrice = getMainPrice(price);

  return (
    <div className={`text-sm font-bold text-orange-600 ${className}`}>
      {formatPrice(mainPrice)} ₫
    </div>
  );
};

export default ProductPrice;
