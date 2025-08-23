import React from 'react';

const ProductName = ({ name, className = '' }) => {
  return (
    <h3 className={`text-xs font-semibold text-gray-900 mb-1 line-clamp-2 ${className}`}>
      {name || 'Название не указано'}
    </h3>
  );
};

export default ProductName;
