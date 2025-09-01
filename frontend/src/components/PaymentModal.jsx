import React from 'react';
import { useTranslation } from 'react-i18next';
import { X, CreditCard } from 'lucide-react';

const PaymentModal = ({ isOpen, onClose, orderId, isMultipleOrders }) => {
  const { t } = useTranslation();

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
      <div className="bg-white rounded-lg max-w-md w-full mx-4">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-xl font-bold flex items-center">
              <CreditCard className="mr-2" size={20} />
              {t('payment.title')}
            </h2>
            <button
              onClick={onClose}
              className="text-gray-500 hover:text-gray-700"
            >
              <X size={20} />
            </button>
          </div>

          <div className="text-center py-8">
            <div className="mb-4">
              <CreditCard className="mx-auto text-gray-400" size={48} />
            </div>
            <h3 className="text-lg font-semibold mb-2 text-red-600">
              {t('payment.online_payment_development')}
            </h3>
            <p className="text-gray-600 mb-6">
              {t('payment.coming_soon')}
            </p>
            
            {orderId && (
              <div className="bg-gray-100 rounded-lg p-3 mb-4">
                <p className="text-sm text-gray-700">
                  Заказ №{orderId}
                </p>
              </div>
            )}
            
            {isMultipleOrders && (
              <div className="bg-gray-100 rounded-lg p-3 mb-4">
                <p className="text-sm text-gray-700">
                  Оплата всех неоплаченных заказов
                </p>
              </div>
            )}

            <button
              onClick={onClose}
              className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Закрыть
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentModal;
