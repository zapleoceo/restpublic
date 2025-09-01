import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { ArrowLeft, ShoppingCart, User, Plus, Minus, X, Phone, Mail, MapPin } from 'lucide-react';
import { groupProductsByCategory } from '../utils/menuUtils';
import ProductCard from './ProductCard';

const OrderMenuPage = ({ menuData }) => {
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState(0);
  const [cart, setCart] = useState([]);
  const [showCart, setShowCart] = useState(false);
  const [showOrderForm, setShowOrderForm] = useState(false);
  const [orderForm, setOrderForm] = useState({
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: ''
  });

  const categories = menuData?.categories || [];
  const products = menuData?.products || [];

  // Группируем продукты по категориям
  const groupedCategories = groupProductsByCategory(categories, products);

  // Добавить товар в корзину
  const addToCart = (product) => {
    setCart(prevCart => {
      const existingItem = prevCart.find(item => item.product_id === product.product_id);
      if (existingItem) {
        return prevCart.map(item =>
          item.product_id === product.product_id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      } else {
        return [...prevCart, { ...product, quantity: 1 }];
      }
    });
  };

  // Удалить товар из корзины
  const removeFromCart = (productId) => {
    setCart(prevCart => prevCart.filter(item => item.product_id !== productId));
  };

  // Изменить количество товара
  const updateQuantity = (productId, newQuantity) => {
    if (newQuantity <= 0) {
      removeFromCart(productId);
      return;
    }
    setCart(prevCart =>
      prevCart.map(item =>
        item.product_id === productId
          ? { ...item, quantity: newQuantity }
          : item
      )
    );
  };

  // Получить общую стоимость корзины
  const getCartTotal = () => {
    return cart.reduce((total, item) => {
      const price = item.product_price_normalized || item.price?.['1'] || 0;
      return total + (price * item.quantity);
    }, 0);
  };

  // Получить количество товаров в корзине
  const getCartItemCount = () => {
    return cart.reduce((total, item) => total + item.quantity, 0);
  };

  // Обработка отправки заказа
  const handleOrderSubmit = async (e) => {
    e.preventDefault();
    
    if (cart.length === 0) {
             alert(t('cart_empty'));
      return;
    }

    if (!orderForm.name || !orderForm.phone) {
             alert(t('order_form_fields.fill_name_phone'));
      return;
    }

    try {
      const orderData = {
        customer: orderForm,
        items: cart.map(item => ({
          product_id: item.product_id,
          product_name: item.product_name,
          quantity: item.quantity,
          price: item.product_price_normalized || item.price?.['1'] || 0
        })),
        total: getCartTotal(),
        timestamp: new Date().toISOString()
      };

      console.log('Отправка заказа:', orderData);

      // Здесь будет отправка заказа на сервер
      // const response = await fetch('/api/orders', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify(orderData)
      // });

      // Пока что просто показываем уведомление
      alert('Заказ успешно оформлен! Мы свяжемся с вами в ближайшее время.');
      
      // Очищаем корзину и форму
      setCart([]);
      setOrderForm({
        name: '',
        phone: '',
        email: '',
        address: '',
        notes: ''
      });
      setShowOrderForm(false);
      setShowCart(false);
    } catch (error) {
      console.error('Ошибка при отправке заказа:', error);
      alert('Произошла ошибка при оформлении заказа. Попробуйте еще раз.');
    }
  };

  // Если нет категорий, показываем сообщение
  if (groupedCategories.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">🍽️</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-4">{t('order_food')}</h1>
          <p className="text-gray-600 mb-6">{t('no_categories')}</p>
          <Link 
            to="/"
            className="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors"
          >
            <ArrowLeft className="mr-2 w-4 h-4" />
            {t('back')}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Кнопка назад */}
            <Link 
              to="/"
              className="inline-flex items-center px-3 py-2 text-gray-600 hover:text-orange-600 transition-colors"
            >
              <ArrowLeft className="mr-2 w-4 h-4" />
              {t('back')}
            </Link>

            {/* Заголовок */}
            <h1 className="text-xl font-semibold text-gray-900">{t('order_food')}</h1>

            {/* Корзина */}
            <button
              onClick={() => setShowCart(true)}
              className="relative inline-flex items-center px-3 py-2 text-gray-600 hover:text-orange-600 transition-colors"
            >
              <ShoppingCart className="w-5 h-5" />
              {getCartItemCount() > 0 && (
                <span className="absolute -top-1 -right-1 bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {getCartItemCount()}
                </span>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex space-x-8 overflow-x-auto">
            {groupedCategories.map((category, index) => (
              <button
                key={category.category_id}
                onClick={() => setActiveTab(index)}
                className={`py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors ${
                  activeTab === index
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {category.category_name}
                <span className="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                  {category.products.length}
                </span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {groupedCategories[activeTab] && (
          <div>
            {/* Category header */}
            <div className="mb-8">
              <h2 className="text-3xl font-bold text-gray-900 mb-2">
                {groupedCategories[activeTab].category_name}
              </h2>
              <p className="text-gray-600">
                {groupedCategories[activeTab].products.length} {t('menu.dishes')}
              </p>
            </div>

            {/* Products grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {groupedCategories[activeTab].products.map((product) => (
                <div key={product.product_id} className="relative">
                  <ProductCard product={product} />
                  <button
                    onClick={() => addToCart(product)}
                    className="absolute bottom-4 right-4 bg-orange-500 hover:bg-orange-600 text-white rounded-full p-2 shadow-lg transition-colors"
                  >
                    <Plus className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>

            {/* Empty state */}
            {groupedCategories[activeTab].products.length === 0 && (
              <div className="text-center py-12">
                <div className="text-6xl mb-4">🍽️</div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {t('no_dishes_in_category')}
                </h3>
                <p className="text-gray-600">
                  {t('new_dishes_coming_soon')}
                </p>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Cart Modal */}
      {showCart && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-md w-full max-h-[80vh] overflow-hidden">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">{t('cart')}</h3>
              <button
                onClick={() => setShowCart(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            
            <div className="p-4 overflow-y-auto max-h-96">
              {cart.length === 0 ? (
                <div className="text-center py-8">
                  <ShoppingCart className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                  <p className="text-gray-500">{t('cart_empty')}</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {cart.map((item) => (
                    <div key={item.product_id} className="flex items-center space-x-3">
                      <div className="flex-1">
                        <h4 className="font-medium text-sm">{item.product_name}</h4>
                        <p className="text-gray-500 text-sm">
                          {(item.product_price_normalized || item.price?.['1'] || 0).toLocaleString()} ₫
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                          className="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center"
                        >
                          <Minus className="w-3 h-3" />
                        </button>
                        <span className="w-8 text-center">{item.quantity}</span>
                        <button
                          onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                          className="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center"
                        >
                          <Plus className="w-3 h-3" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
            
            {cart.length > 0 && (
              <div className="p-4 border-t">
                <div className="flex justify-between items-center mb-4">
                  <span className="font-semibold">{t('total')}</span>
                  <span className="font-semibold text-lg">
                    {getCartTotal().toLocaleString()} ₫
                  </span>
                </div>
                <button
                  onClick={() => {
                    setShowCart(false);
                    setShowOrderForm(true);
                  }}
                  className="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-medium transition-colors"
                >
                  {t('place_order')}
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Order Form Modal */}
      {showOrderForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-md w-full max-h-[80vh] overflow-y-auto">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">{t('order_form')}</h3>
              <button
                onClick={() => setShowOrderForm(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            
            <form onSubmit={handleOrderSubmit} className="p-4 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Имя * <User className="inline w-4 h-4" />
                </label>
                <input
                  type="text"
                  required
                  value={orderForm.name}
                  onChange={(e) => setOrderForm({...orderForm, name: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder={t('order_form_fields.your_name')}
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('order_form_fields.phone')} * <Phone className="inline w-4 h-4" />
                </label>
                <input
                  type="tel"
                  required
                  value={orderForm.phone}
                  onChange={(e) => setOrderForm({...orderForm, phone: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder="+84 XXX XXX XXX"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('order_form_fields.email')} <Mail className="inline w-4 h-4" />
                </label>
                <input
                  type="email"
                  value={orderForm.email}
                  onChange={(e) => setOrderForm({...orderForm, email: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder="your@email.com"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('order_form_fields.delivery_address')} <MapPin className="inline w-4 h-4" />
                </label>
                <textarea
                  value={orderForm.address}
                  onChange={(e) => setOrderForm({...orderForm, address: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder={t('order_form_fields.delivery_address_placeholder')}
                  rows="2"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('order_form_fields.additional_notes')}
                </label>
                <textarea
                  value={orderForm.notes}
                  onChange={(e) => setOrderForm({...orderForm, notes: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                  placeholder={t('order_form_fields.notes_placeholder')}
                  rows="2"
                />
              </div>
              
              <div className="bg-gray-50 p-3 rounded-lg">
                <h4 className="font-medium mb-2">{t('order_form_fields.your_order')}</h4>
                <div className="space-y-1 text-sm">
                  {cart.map((item) => (
                    <div key={item.product_id} className="flex justify-between">
                      <span>{item.product_name} x{item.quantity}</span>
                      <span>{(item.product_price_normalized || item.price?.['1'] || 0) * item.quantity} ₫</span>
                    </div>
                  ))}
                  <div className="border-t pt-2 mt-2 font-medium">
                    <div className="flex justify-between">
                      <span>{t('total')}</span>
                      <span>{getCartTotal().toLocaleString()} ₫</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="flex space-x-3">
                <button
                  type="button"
                  onClick={() => setShowOrderForm(false)}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  {t('order_form_fields.cancel')}
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg font-medium transition-colors"
                >
                  Оформить заказ
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default OrderMenuPage;
