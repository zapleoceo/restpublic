import React from 'react';
import ProductGrid from './ProductGrid';

const CategoryColumn = ({ 
  category, 
  index, 
  className = '' 
}) => {
  const categoryNames = ['Еда', 'Бар'];
  const categoryName = categoryNames[index] || category.category_name;

  return (
    <div className={`flex-1 ${index === 0 ? 'bg-white' : 'bg-gray-50'} ${className}`}>
      {/* Category Header */}
      <div className="text-center py-6 border-b border-gray-200">
        <h2 className="text-2xl font-bold text-gray-900">
          {categoryName}
        </h2>
      </div>

      {/* Products Grid */}
      <div className="p-6">
        <ProductGrid products={category.products} />
      </div>
    </div>
  );
};

export default CategoryColumn;
