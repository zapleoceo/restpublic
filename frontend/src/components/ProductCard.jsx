import React from 'react';
import Card from './Card';
import ProductImage from './ProductImage';
import ProductName from './ProductName';
import ProductPrice from './ProductPrice';

const ProductCard = ({ product }) => {
  return (
    <Card>
      {/* Product Image */}
      <ProductImage 
        photo={product.photo}
        alt={product.product_name || 'Товар'}
      />

      {/* Product Name */}
      <div className="p-2 text-center border-t border-gray-100">
        <ProductName name={product.product_name} />
      </div>

      {/* Price */}
      <div className="p-2 text-center border-t border-gray-100">
        <ProductPrice price={product.price} />
      </div>
    </Card>
  );
};

export default ProductCard;
