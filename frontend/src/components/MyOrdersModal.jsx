import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, CreditCard, Calendar, Hash, DollarSign, Clock } from 'lucide-react';
import PaymentModal from './PaymentModal';

const MyOrdersModal = ({ isOpen, onClose, userId }) => {
  const { t } = useTranslation();
  const [orders, setOrders] = useState([]);
  const [pastOrders, setPastOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [loadingPast, setLoadingPast] = useState(false);
  const [hasMorePast, setHasMorePast] = useState(true);
  const [pastOffset, setPastOffset] = useState(0);
  const [showPastOrders, setShowPastOrders] = useState(false);
  const [showPayment, setShowPayment] = useState(false);
  const [selectedOrderId, setSelectedOrderId] = useState(null);
  const [isMultipleOrders, setIsMultipleOrders] = useState(false);

  useEffect(() => {
    if (isOpen && userId) {
      fetchOrders();
    }
  }, [isOpen, userId]);

  const fetchOrders = async () => {
    if (!userId) return;
    
    setLoading(true);
    try {
      const response = await fetch(`/api/orders/user/${userId}`);
      if (response.ok) {
        const data = await response.json();
        setOrders(data.orders || []);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchPastOrders = async () => {
    if (!userId || !hasMorePast) return;
    
    setLoadingPast(true);
    try {
      const response = await fetch(`/api/orders/user/${userId}/past?limit=10&offset=${pastOffset}`);
      if (response.ok) {
        const data = await response.json();
        const newPastOrders = data.orders || [];
        setPastOrders(prev => [...prev, ...newPastOrders]);
        setPastOffset(prev => prev + 10);
        setHasMorePast(newPastOrders.length === 10);
      }
    } catch (error) {
      console.error('Error fetching past orders:', error);
    } finally {
      setLoadingPast(false);
    }
  };

  const handleLoadPastOrders = () => {
    if (!showPastOrders) {
      setShowPastOrders(true);
    }
    fetchPastOrders();
  };

  const handlePayOrder = (orderId) => {
    setSelectedOrderId(orderId);
    setIsMultipleOrders(false);
    setShowPayment(true);
  };

  const handlePayAllOrders = () => {
    setSelectedOrderId(null);
    setIsMultipleOrders(true);
    setShowPayment(true);
  };

  const handlePaymentClose = () => {
    setShowPayment(false);
    setSelectedOrderId(null);
    setIsMultipleOrders(false);
    // Обновляем список заказов после оплаты
    fetchOrders();
  };

  const formatDate = (dateString) => {
    // Если это timestamp в миллисекундах
    if (typeof dateString === 'string' && dateString.length > 10) {
      return new Date(parseInt(dateString)).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
    // Если это обычная дата
    return new Date(dateString).toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatPrice = (price) => {
    // Если цена в копейках, делим на 100
    const priceInRubles = typeof price === 'string' ? parseInt(price) / 100 : price / 100;
    return new Intl.NumberFormat('ru-RU').format(priceInRubles);
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold">{t('my_orders.title')}</h2>
            <button
              onClick={onClose}
              className="text-gray-500 hover:text-gray-700"
            >
              <X size={24} />
            </button>
          </div>

          {loading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-2 text-gray-600">Загрузка заказов...</p>
            </div>
          ) : (
            <>
              {/* Неоплаченные заказы */}
              <div className="mb-8">
                <h3 className="text-lg font-semibold mb-4 flex items-center">
                  <Clock className="mr-2" size={20} />
                  {t('my_orders.unpaid_orders')}
                </h3>
                
                {orders.length === 0 ? (
                  <p className="text-gray-500 text-center py-4">{t('my_orders.no_orders')}</p>
                ) : (
                  <div className="space-y-4">
                    {orders.map((order) => (
                      <div key={order.transaction_id} className="border rounded-lg p-4 bg-gray-50">
                        <div className="flex justify-between items-start">
                          <div className="flex-1">
                            <div className="flex items-center mb-2">
                              <Hash className="mr-2" size={16} />
                              <span className="font-medium">
                                {t('my_orders.order_number')}{order.transaction_id}
                              </span>
                            </div>
                            <div className="flex items-center mb-2">
                              <Calendar className="mr-2" size={16} />
                              <span className="text-sm text-gray-600">
                                {formatDate(order.date_start)}
                              </span>
                            </div>
                            <div className="flex items-center mb-2">
                              <DollarSign className="mr-2" size={16} />
                              <span className="font-semibold">
                                {formatPrice(order.sum)} ₽
                              </span>
                            </div>
                            <div className="text-sm text-gray-600">
                              {order.transaction_comment && `Комментарий: ${order.transaction_comment}`}
                            </div>
                          </div>
                          <button
                            onClick={() => handlePayOrder(order.transaction_id)}
                            className="flex items-center bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                          >
                            <CreditCard className="mr-1" size={16} />
                            {t('my_orders.pay_order')}
                          </button>
                        </div>
                      </div>
                    ))}
                    
                    {orders.length > 1 && (
                      <div className="text-center pt-4">
                        <button
                          onClick={handlePayAllOrders}
                          className="flex items-center justify-center bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors mx-auto"
                        >
                          <CreditCard className="mr-2" size={20} />
                          {t('my_orders.pay_all_orders')}
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>

              {/* Прошлые заказы */}
              <div className="border-t pt-6">
                <button
                  onClick={handleLoadPastOrders}
                  disabled={!hasMorePast && showPastOrders}
                  className={`text-sm ${
                    !hasMorePast && showPastOrders
                      ? 'text-gray-400 cursor-not-allowed'
                      : 'text-gray-600 hover:text-gray-800'
                  }`}
                >
                  {!hasMorePast && showPastOrders
                    ? t('my_orders.all_orders_loaded')
                    : t('my_orders.past_orders')
                  }
                </button>

                {showPastOrders && pastOrders.length > 0 && (
                  <div className="mt-4 space-y-3">
                    {pastOrders.map((order) => (
                      <div key={order.transaction_id} className="border rounded-lg p-3 bg-white">
                        <div className="flex justify-between items-center">
                          <div>
                            <div className="flex items-center mb-1">
                              <Hash className="mr-2" size={14} />
                              <span className="font-medium text-sm">
                                {t('my_orders.order_number')}{order.transaction_id}
                              </span>
                            </div>
                            <div className="flex items-center mb-1">
                              <Calendar className="mr-2" size={14} />
                              <span className="text-xs text-gray-600">
                                {formatDate(order.date_start)}
                              </span>
                            </div>
                            <div className="flex items-center">
                              <DollarSign className="mr-2" size={14} />
                              <span className="font-semibold text-sm">
                                {formatPrice(order.sum)} ₽
                              </span>
                            </div>
                          </div>
                          <div className="text-right">
                            <span className={`text-xs px-2 py-1 rounded ${
                              order.status === '2' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                            }`}>
                              {order.status === '2' ? t('my_orders.status_paid') : t('my_orders.status_unpaid')}
                            </span>
                          </div>
                        </div>
                      </div>
                    ))}
                    
                    {hasMorePast && (
                      <div className="text-center pt-2">
                        <button
                          onClick={fetchPastOrders}
                          disabled={loadingPast}
                          className="text-sm text-blue-600 hover:text-blue-800 disabled:text-gray-400"
                        >
                          {loadingPast ? 'Загрузка...' : t('my_orders.load_more_orders')}
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </>
          )}
        </div>
      </div>

      <PaymentModal
        isOpen={showPayment}
        onClose={handlePaymentClose}
        orderId={selectedOrderId}
        isMultipleOrders={isMultipleOrders}
      />
    </div>
  );
};

export default MyOrdersModal;
