import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { X, User, Phone, Calendar, UserCheck, MessageCircle } from 'lucide-react';
import { useCart } from '../contexts/CartContext';

const AuthModal = ({ isOpen, onClose, telegramData = null }) => {
  const { t } = useTranslation();
  const { setSession } = useCart();
  const [isLoginMode, setIsLoginMode] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  
  // Form data
  const [formData, setFormData] = useState({
    name: '',
    lastName: '',
    phone: '',
    birthday: '',
    gender: ''
  });

  // Reset form when modal opens or telegramData changes
  useEffect(() => {
    if (isOpen) {
      if (telegramData) {
        // Заполняем форму данными из Telegram
        setFormData({
          name: telegramData.name || '',
          lastName: telegramData.lastName || '',
          phone: telegramData.phone || '',
          birthday: telegramData.birthday || '',
          gender: telegramData.gender || ''
        });
        setIsLoginMode(false); // Режим завершения регистрации
      } else {
        // Обычная форма регистрации
        setFormData({
          name: '',
          lastName: '',
          phone: '',
          birthday: '',
          gender: ''
        });
        setIsLoginMode(false);
      }
      setError('');
    }
  }, [isOpen, telegramData]);

  if (!isOpen) return null;

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleTelegramAuth = () => {
    // Открываем Telegram бота
    const botUsername = 'RestPublic_bot'; // Правильное имя бота
    const currentUrl = encodeURIComponent(window.location.href);
    const telegramUrl = `https://t.me/${botUsername}?start=auth_${currentUrl}`;
    window.open(telegramUrl, '_blank');
    
    // Показываем инструкцию пользователю
    alert('Откроется Telegram бот. Поделитесь своим контактом для авторизации, затем вернитесь в приложение.');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      if (isLoginMode) {
        // Логика авторизации (пока не реализована)
        throw new Error('Авторизация пока не реализована');
      } else {
        // Логика регистрации
        const response = await fetch('/api/auth/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Ошибка при регистрации');
        }

        const result = await response.json();
        
        // Сохраняем сессию
        if (result.session) {
          setSession(result.session);
        }

        // Закрываем модальное окно
        onClose();
      }
    } catch (err) {
      console.error('Auth error:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

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
              <UserCheck className="w-5 h-5 text-orange-500" />
              <h2 className="text-lg font-semibold text-gray-900">
                {telegramData ? t('auth.complete_registration') : 
                 isLoginMode ? t('auth.login') : t('auth.register')}
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
            {/* Switch mode button */}
            {!telegramData && (
              <div className="mb-6 text-center">
                <button
                  onClick={() => setIsLoginMode(!isLoginMode)}
                  className="text-orange-600 hover:text-orange-700 text-sm font-medium"
                >
                  {isLoginMode ? t('auth.no_account_register') : t('auth.have_account_login')}
                </button>
              </div>
            )}

            {isLoginMode ? (
              /* Login Form */
              <div className="text-center py-8">
                <div className="mb-6">
                  <MessageCircle className="mx-auto text-gray-400" size={48} />
                </div>
                <h3 className="text-lg font-semibold mb-4">
                  {t('auth.login_via_telegram')}
                </h3>
                <p className="text-gray-600 mb-6">
                  {t('auth.telegram_login_description')}
                </p>
                <button
                  onClick={handleTelegramAuth}
                  className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                >
                  {t('auth.login_via_telegram_button')}
                </button>
              </div>
            ) : (
              /* Registration Form */
              <form onSubmit={handleSubmit} className="space-y-4">
                {/* Name */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('auth.name')} *
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

                {/* Last Name */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('auth.lastName')} *
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
                    {t('auth.birthday')} *
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
                    {t('auth.gender')} *
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
                      {t('auth.male')}
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
                      {t('auth.female')}
                    </label>
                  </div>
                </div>

                {/* Phone */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('auth.phone')} *
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleInputChange}
                    className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 ${
                      telegramData ? 'bg-gray-100 cursor-not-allowed' : ''
                    }`}
                    readOnly={!!telegramData}
                    required
                  />
                  {telegramData && (
                    <p className="text-xs text-gray-500 mt-1">
                      {t('auth.phone_from_telegram')}
                    </p>
                  )}
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
                  {loading ? t('auth.processing') : 
                   telegramData ? t('auth.confirm') : t('auth.register')}
                </button>
              </form>
            )}
          </div>
        </div>
      </div>
    </>
  );
};

export default AuthModal;
