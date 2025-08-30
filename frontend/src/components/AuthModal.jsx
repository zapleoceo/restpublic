import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, MessageCircle } from 'lucide-react';
import { useCart } from '../contexts/CartContext';

const AuthModal = ({ isOpen, onClose }) => {
  const { t } = useTranslation();
  const { setSession } = useCart();

  if (!isOpen) return null;

  const handleTelegramAuth = () => {
    // Открываем Telegram бота
    const botUsername = 'RestPublic_bot';
    const currentUrl = encodeURIComponent(window.location.href);
    const telegramUrl = `https://t.me/${botUsername}?start=auth_${currentUrl}`;
    window.open(telegramUrl, '_blank');
    
    // Показываем инструкцию пользователю
    alert('Откроется Telegram бот. Поделитесь своим контактом для авторизации, затем вернитесь в приложение.');
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-md w-full p-6">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-xl font-bold text-gray-900">Войти в приложение</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X size={24} />
          </button>
        </div>

        <div className="space-y-4">
          {/* Кнопка Telegram */}
          <button
            onClick={handleTelegramAuth}
            className="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded-lg flex items-center justify-center space-x-3 transition-colors"
          >
            <MessageCircle size={20} />
            <span>Войти через Telegram</span>
          </button>

          {/* Кнопка Max (неактивная) */}
          <button
            disabled
            className="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg flex items-center justify-center space-x-3 cursor-not-allowed"
          >
            <span>Войти через Max</span>
            <span className="text-xs bg-gray-400 text-white px-2 py-1 rounded">Скоро будет</span>
          </button>
        </div>

        <div className="mt-6 text-center text-sm text-gray-600">
          Выберите способ авторизации для продолжения
        </div>
      </div>
    </div>
  );
};

export default AuthModal;
