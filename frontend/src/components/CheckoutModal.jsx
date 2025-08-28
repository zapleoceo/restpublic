import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, User, Phone, Calendar, UserCheck, ShoppingBag } from 'lucide-react';
import { useCart } from '../contexts/CartContext';
import { formatPrice } from '../utils/priceUtils';

const CheckoutModal = ({ isOpen, onClose, onOrderSuccess, tableId }) => {
  const { t } = useTranslation();
  const { items, total } = useCart();
  const [orderType, setOrderType] = useState('guest'); // 'guest' or 'register'
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  
  // Form data
  const [formData, setFormData] = useState({
    name: '',
    lastName: '',
    phone: '',
    birthday: '',
    gender: '',
    comment: ''
  });

  // Reset form when modal opens
  useEffect(() => {
    if (isOpen) {
      setFormData({
        name: '',
        lastName: '',
        phone: '',
        birthday: '',
        gender: '',
        comment: ''
      });
      setOrderType('guest');
      setError('');
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Validate required fields
      if (!formData.name.trim()) {
        throw new Error(t('checkout.name') + ' обязательно');
      }

      if (orderType === 'guest' && !formData.phone.trim()) {
        throw new Error(t('checkout.phone') + ' обязателен');
      }

      // Регистрация временно отключена, все заказы создаются как гостевые
      if (!formData.phone.trim()) {
        throw new Error(t('checkout.phone') + ' обязателен');
      }

      // Prepare order data - все заказы создаются как гостевые
      const orderData = {
        items: items.map(item => ({
          product_id: item.product_id,
          product_name: item.product_name,
          price: item.price,
          quantity: item.quantity
        })),
        total,
        tableId,
        comment: formData.comment.trim(),
        orderType: 'guest', // Принудительно гостевой заказ
        customerData: {
          name: formData.name.trim(),
          phone: formData.phone.trim()
        }
      };

      // Determine API endpoint based on order type
      const endpoint = orderType === 'guest' 
        ? '/api/orders/create-guest' 
        : '/api/orders/create';

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Ошибка при создании заказа');
      }

      const result = await response.json();
      console.log('Order created successfully:', result);

      // Success - call success callback
      onOrderSuccess();
      
    } catch (err) {
      console.error('Order creation error:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const discountAmount = orderType === 'register' ? total * 0.2 : 0;
  const finalTotal = total - discountAmount;

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-50"
        onClick={onClose}
      />
      
      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-hidden shadow-2xl">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b">
            <div className="flex items-center space-x-2">
              <ShoppingBag className="w-5 h-5 text-orange-500" />
              <h2 className="text-lg font-semibold text-gray-900">
                {t('checkout.title')}
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
          <div className="p-4 max-h-[calc(90vh-120px)] overflow-y-auto">
            {/* Order Type Selection */}
            <div className="mb-6">
              <div className="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  onClick={() => setOrderType('guest')}
                  className={`p-3 rounded-lg border-2 transition-colors ${
                    orderType === 'guest'
                      ? 'border-orange-500 bg-orange-50 text-orange-700'
                      : 'border-gray-200 text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <User className="w-5 h-5 mx-auto mb-2" />
                  <div className="text-sm font-medium">{t('checkout.guest_order')}</div>
                </button>
                <button
                  type="button"
                  disabled
                  className="p-3 rounded-lg border-2 transition-colors opacity-50 cursor-not-allowed border-gray-200 text-gray-500"
                  title="Регистрация временно недоступна"
                >
                  <UserCheck className="w-5 h-5 mx-auto mb-2" />
                  <div className="text-sm font-medium">{t('checkout.register_discount')}</div>
                  <div className="text-xs text-gray-400 mt-1">Скоро</div>
                </button>
              </div>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Name */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('checkout.name')} *
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  required
                />
              </div>

              {/* Additional fields for registration */}
              {orderType === 'register' && (
                <>
                  {/* Last Name */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Фамилия *
                    </label>
                    <input
                      type="text"
                      name="lastName"
                      value={formData.lastName}
                      onChange={handleInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                      required
                    />
                  </div>

                  {/* Birthday */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Дата рождения *
                    </label>
                    <input
                      type="date"
                      name="birthday"
                      value={formData.birthday}
                      onChange={handleInputChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                      required
                    />
                  </div>

                  {/* Gender */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Пол *
                    </label>
                    <div className="flex space-x-4">
                      <label className="flex items-center">
                        <input
                          type="radio"
                          name="gender"
                          value="male"
                          checked={formData.gender === 'male'}
                          onChange={handleInputChange}
                          className="mr-2 text-orange-500 focus:ring-orange-500"
                        />
                        М
                      </label>
                      <label className="flex items-center">
                        <input
                          type="radio"
                          name="gender"
                          value="female"
                          checked={formData.gender === 'female'}
                          onChange={handleInputChange}
                          className="mr-2 text-orange-500 focus:ring-orange-500"
                        />
                        Ж
                      </label>
                    </div>
                  </div>
                </>
              )}

              {/* Phone */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('checkout.phone')} *
                </label>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="+84 XXX XXX XXX"
                  required
                />
                {orderType === 'register' && (
                  <p className="text-xs text-gray-500 mt-1">
                    Мы пришлем вам проверочный код в телеграм
                  </p>
                )}
              </div>

              {/* Comment */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('checkout.comment')}
                </label>
                <textarea
                  name="comment"
                  value={formData.comment}
                  onChange={handleInputChange}
                  rows="3"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  placeholder="Особые пожелания к заказу..."
                />
              </div>

              {/* Order Summary */}
              <div className="border-t pt-4 mt-6">
                <h3 className="font-medium text-gray-900 mb-3">{t('checkout.order_summary')}</h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span>Сумма заказа:</span>
                    <span>{formatPrice(total)}</span>
                  </div>
                  {orderType === 'register' && discountAmount > 0 && (
                    <div className="flex justify-between text-green-600">
                      <span>{t('checkout.first_order_discount')}:</span>
                      <span>-{formatPrice(discountAmount)}</span>
                    </div>
                  )}
                  <div className="flex justify-between font-semibold text-lg border-t pt-2">
                    <span>{t('cart.total')}:</span>
                    <span className="text-orange-600">{formatPrice(finalTotal)}</span>
                  </div>
                </div>
              </div>

              {/* Error message */}
              {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm">
                  {error}
                </div>
              )}

              {/* Submit button */}
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white py-3 px-4 rounded-lg font-medium transition-colors"
              >
                {loading ? 'Создание заказа...' : t('checkout.place_order')}
              </button>
            </form>
          </div>
        </div>
      </div>
    </>
  );
};

export default CheckoutModal;
