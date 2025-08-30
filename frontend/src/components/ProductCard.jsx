import React, { useState } from 'react';
import { Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import Card from './Card';
import ProductImage from './ProductImage';
import ProductName from './ProductName';
import ProductPrice from './ProductPrice';
import { useCart } from '../contexts/CartContext';
import ModificatorsModal from './ModificatorsModal';

const ProductCard = ({ product }) => {
  const { t } = useTranslation();
  const { addToCart, getItemQuantity } = useCart();
  const quantity = getItemQuantity(product.product_id);
  
  // Состояние для модалки модификаторов
  const [showModificatorsModal, setShowModificatorsModal] = useState(false);

  const handleAddToCart = async () => {
    // Проверяем, есть ли у товара модификаторы
    try {
      const response = await fetch(`/api/products/${product.product_id}/modificators`);
      if (response.ok) {
        const data = await response.json();
        if (data.modificators && data.modificators.length > 0) {
          // Если есть модификаторы, показываем модалку
          setShowModificatorsModal(true);
          return;
        }
      }
    } catch (error) {
      console.error('Ошибка при проверке модификаторов:', error);
    }
    
    // Если модификаторов нет или произошла ошибка, добавляем товар как обычно
    addToCart(product);
  };

  const handleModificatorsConfirm = (productWithModificators) => {
    setShowModificatorsModal(false);
    addToCart(productWithModificators);
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

      {/* Модалка для выбора модификаторов */}
      <ModificatorsModal
        isOpen={showModificatorsModal}
        onClose={() => setShowModificatorsModal(false)}
        product={product}
        onConfirm={handleModificatorsConfirm}
      />
    </>
  );
};

export default ProductCard;
