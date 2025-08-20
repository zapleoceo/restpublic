import React from 'react';

const ProductName = ({ name, className = '' }) => {
  return (
    <h3 className={`text-sm font-semibold text-gray-900 mb-2 line-clamp-2 ${className}`}>
      {name || 'Название не указано'}
    </h3>
  );
};

export default ProductName;
