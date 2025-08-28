import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { X, Plus, Minus, Trash2, ShoppingCart } from 'lucide-react';
import { useCart } from '../contexts/CartContext';
import { formatPrice } from '../utils/priceUtils';
import { getImageUrl, isImageAvailable } from '../utils/imageUtils';
import CheckoutModal from './CheckoutModal';

const CartModal = ({ isOpen, onClose, tableId }) => {
  const { t } = useTranslation();
  const { items, total, updateQuantity, removeFromCart, clearCart } = useCart();
  const [showCheckout, setShowCheckout] = useState(false);

  if (!isOpen) return null;

  const handleQuantityChange = (productId, newQuantity) => {
    if (newQuantity <= 0) {
      removeFromCart(productId);
    } else {
      updateQuantity(productId, newQuantity);
    }
  };

  const handleCheckout = () => {
    setShowCheckout(true);
  };

  const handleCheckoutClose = () => {
    setShowCheckout(false);
  };

  const handleOrderSuccess = () => {
    clearCart();
    setShowCheckout(false);
    onClose();
  };

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-40"
        onClick={onClose}
      />
      
      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
        <div className="bg-white rounded-t-xl sm:rounded-xl w-full max-w-md max-h-[90vh] overflow-hidden shadow-2xl">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b">
            <div className="flex items-center space-x-2">
              <ShoppingCart className="w-5 h-5 text-orange-500" />
              <h2 className="text-lg font-semibold text-gray-900">
                {t('cart.title')}
              </h2>
            </div>
            <button
              onClick={onClose}
              className="p-1 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="flex flex-col max-h-[calc(90vh-120px)]">
            {items.length === 0 ? (
              /* Empty cart */
              <div className="flex-1 flex items-center justify-center p-8">
                <div className="text-center">
                  <ShoppingCart className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">
                    {t('cart.empty')}
                  </h3>
                  <p className="text-gray-500 mb-4">
                    {t('cart.add_items')}
                  </p>
                  <button
                    onClick={onClose}
                    className="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors"
                  >
                    {t('cart.continue_shopping')}
                  </button>
                </div>
              </div>
            ) : (
              <>
                {/* Cart items */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                  {items.map((item) => (
                    <div key={item.product_id} className="flex items-center space-x-3 bg-gray-50 rounded-lg p-3">
                      {/* Product image */}
                      <div className="w-12 h-12 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                        {isImageAvailable(item.photo) ? (
                          <img
                            src={getImageUrl(item.photo)}
                            alt={item.product_name}
                            className="w-full h-full object-cover"
                            onError={(e) => {
                              e.target.style.display = 'none';
                              e.target.nextSibling.style.display = 'flex';
                            }}
                          />
                        ) : null}
                        <div 
                          className="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs"
                          style={{ display: isImageAvailable(item.photo) ? 'none' : 'flex' }}
                        >
                          🍽️
                        </div>
                      </div>

                      {/* Product info */}
                      <div className="flex-1 min-w-0">
                        <h4 className="text-sm font-medium text-gray-900 truncate">
                          {item.product_name}
                        </h4>
                        <p className="text-sm text-orange-600 font-semibold">
                          {formatPrice(item.price)}
                        </p>
                      </div>

                      {/* Quantity controls */}
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => handleQuantityChange(item.product_id, item.quantity - 1)}
                          className="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded-full transition-colors"
                        >
                          <Minus className="w-4 h-4" />
                        </button>
                        <span className="w-8 text-center font-medium">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() => handleQuantityChange(item.product_id, item.quantity + 1)}
                          className="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded-full transition-colors"
                        >
                          <Plus className="w-4 h-4" />
                        </button>
                      </div>

                      {/* Remove button */}
                      <button
                        onClick={() => removeFromCart(item.product_id)}
                        className="p-1 text-red-400 hover:text-red-600 transition-colors"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>

                {/* Footer */}
                <div className="border-t p-4 space-y-4">
                  {/* Total */}
                  <div className="flex items-center justify-between">
                    <span className="text-lg font-semibold text-gray-900">
                      {t('cart.total')}:
                    </span>
                    <span className="text-xl font-bold text-orange-600">
                      {formatPrice(total)}
                    </span>
                  </div>

                  {/* Action buttons */}
                  <div className="flex space-x-3">
                    <button
                      onClick={clearCart}
                      className="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors"
                    >
                      {t('cart.clear')}
                    </button>
                    <button
                      onClick={handleCheckout}
                      className="flex-1 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors font-medium"
                    >
                      {t('cart.checkout')}
                    </button>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </div>

      {/* Checkout Modal */}
      {showCheckout && (
        <CheckoutModal
          isOpen={showCheckout}
          onClose={handleCheckoutClose}
          onOrderSuccess={handleOrderSuccess}
          tableId={tableId}
        />
      )}
    </>
  );
};

export default CartModal;
