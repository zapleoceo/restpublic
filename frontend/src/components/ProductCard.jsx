import React, { useState } from 'react';
import { Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import Card from './Card';
import ProductImage from './ProductImage';
import ProductName from './ProductName';
import ProductPrice from './ProductPrice';
import { useCart } from '../contexts/CartContext';

const ProductCard = ({ product }) => {
  const { t } = useTranslation();
  const { addToCart, getItemQuantity } = useCart();
  const quantity = getItemQuantity(product.product_id);

  const handleAddToCart = () => {
    addToCart(product);
  };

  return (
    <>
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

        {/* Add to Cart Button */}
        <div className="p-2 border-t border-gray-100">
          <button
            onClick={handleAddToCart}
            className="w-full flex items-center justify-center space-x-2 bg-orange-500 hover:bg-orange-600 text-white py-2 px-3 rounded-lg transition-colors text-sm font-medium"
          >
            <Plus className="w-4 h-4" />
            <span>
              {quantity > 0 ? `${t('cart.in_cart')} (${quantity})` : t('cart.add')}
            </span>
          </button>
        </div>
      </Card>

    </>
  );
};

export default ProductCard;
