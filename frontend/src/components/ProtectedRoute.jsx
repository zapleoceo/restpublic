import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';

const ProtectedRoute = ({ children }) => {
  const [isEnabled, setIsEnabled] = useState(true);
  const [loading, setLoading] = useState(true);
  const location = useLocation();

  useEffect(() => {
    checkPageAccess();
  }, [location.pathname]);

  const checkPageAccess = async () => {
    try {
      setLoading(true);
      const path = location.pathname === '/' ? '' : location.pathname.substring(1);
      const response = await fetch(`/api/admin/page/${path}/status`);
      
      if (response.ok) {
        const data = await response.json();
        setIsEnabled(data.enabled);
      } else {
        // Если API недоступен, считаем страницу доступной
        setIsEnabled(true);
      }
    } catch (error) {
      console.error('Ошибка проверки доступа к странице:', error);
      // При ошибке считаем страницу доступной
      setIsEnabled(true);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Проверка доступа...</p>
        </div>
      </div>
    );
  }

  if (!isEnabled) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-500 text-6xl mb-4">🚫</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Страница недоступна</h1>
          <p className="text-gray-600 mb-6">
            Эта страница временно отключена администратором.
          </p>
          <a
            href="/"
            className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors"
          >
            Вернуться на главную
          </a>
        </div>
      </div>
    );
  }

  return children;
};

export default ProtectedRoute;
