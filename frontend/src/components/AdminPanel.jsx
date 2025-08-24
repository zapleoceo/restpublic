import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

const AdminPanel = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [config, setConfig] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [updating, setUpdating] = useState({});
  const [user, setUser] = useState(null);

  useEffect(() => {
    checkAuthAndLoadConfig();
  }, []);

  const checkAuthAndLoadConfig = async () => {
    try {
      setLoading(true);
      
      // Проверяем статус авторизации
      const authResponse = await fetch('/api/auth/status');
      if (!authResponse.ok) {
        navigate('/admin/login');
        return;
      }
      
      const authData = await authResponse.json();
      setUser(authData.user);
      
      // Загружаем конфигурацию
      const configResponse = await fetch('/api/admin/config');
      if (!configResponse.ok) {
        throw new Error('Ошибка загрузки конфигурации');
      }
      const configData = await configResponse.json();
      setConfig(configData);
    } catch (err) {
      if (err.message.includes('401')) {
        navigate('/admin/login');
      } else {
        setError(err.message);
      }
    } finally {
      setLoading(false);
    }
  };

  const updateSection = async (sectionKey, enabled) => {
    try {
      setUpdating(prev => ({ ...prev, [sectionKey]: true }));
      const response = await fetch(`/api/admin/section/${sectionKey}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ enabled }),
      });

      if (!response.ok) {
        throw new Error('Ошибка обновления секции');
      }

      // Обновляем локальное состояние
      setConfig(prev => ({
        ...prev,
        sections: {
          ...prev.sections,
          [sectionKey]: {
            ...prev.sections[sectionKey],
            enabled
          }
        }
      }));

             // Перезагружаем конфигурацию для обновления lastUpdated
       await checkAuthAndLoadConfig();
     } catch (err) {
       setError(err.message);
     } finally {
       setUpdating(prev => ({ ...prev, [sectionKey]: false }));
     }
   };

    const updatePage = async (pagePath, enabled) => {
    try {
      const pathKey = pagePath.replace('/', '');
      setUpdating(prev => ({ ...prev, [pathKey]: true }));
      
      const response = await fetch(`/api/admin/page/${pathKey}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ enabled }),
      });

      if (!response.ok) {
        throw new Error('Ошибка обновления страницы');
      }

      // Обновляем локальное состояние
      setConfig(prev => ({
        ...prev,
        pages: {
          ...prev.pages,
          [pagePath]: {
            ...prev.pages[pagePath],
            enabled
          }
        }
      }));

      // Перезагружаем конфигурацию для обновления lastUpdated
      await checkAuthAndLoadConfig();
    } catch (err) {
      setError(err.message);
    } finally {
      const pathKey = pagePath.replace('/', '');
      setUpdating(prev => ({ ...prev, [pathKey]: false }));
    }
  };

  const handleLogout = async () => {
    try {
      await fetch('/api/auth/logout', { method: 'POST' });
      navigate('/admin/login');
    } catch (err) {
      console.error('Ошибка выхода:', err);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-100 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Загрузка админки...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-100 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-600 text-xl mb-4">❌ Ошибка</div>
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={loadConfig}
            className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Попробовать снова
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100 py-8">
      <div className="max-w-6xl mx-auto px-4">
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">Панель администратора</h1>
              <p className="text-gray-600">
                Управление видимостью кнопок и страниц
              </p>
            </div>
            <div className="text-right">
              {user && (
                <div className="mb-2">
                  <p className="text-sm text-gray-600">Пользователь: <span className="font-semibold">{user.username}</span></p>
                  <p className="text-xs text-gray-500">Роль: {user.role}</p>
                </div>
              )}
              <button
                onClick={handleLogout}
                className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm"
              >
                Выйти
              </button>
            </div>
          </div>
          {config?.lastUpdated && (
            <p className="text-sm text-gray-500">
              Последнее обновление: {new Date(config.lastUpdated).toLocaleString('ru-RU')}
            </p>
          )}
        </div>

        {/* Секции */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Секции (кнопки на главной)</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {config?.sections && Object.entries(config.sections).map(([key, section]) => (
              <div key={key} className="border rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-gray-900">{section.title}</h3>
                  <div className="flex items-center">
                    <span className={`px-2 py-1 rounded text-xs font-medium ${
                      section.enabled 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {section.enabled ? 'Активна' : 'Неактивна'}
                    </span>
                  </div>
                </div>
                <p className="text-sm text-gray-600 mb-3">{section.description}</p>
                <button
                  onClick={() => updateSection(key, !section.enabled)}
                  disabled={updating[key]}
                  className={`w-full px-4 py-2 rounded font-medium transition-colors ${
                    section.enabled
                      ? 'bg-red-600 hover:bg-red-700 text-white'
                      : 'bg-green-600 hover:bg-green-700 text-white'
                  } disabled:opacity-50`}
                >
                  {updating[key] ? (
                    <span className="flex items-center justify-center">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                      Обновление...
                    </span>
                  ) : (
                    section.enabled ? 'Деактивировать' : 'Активировать'
                  )}
                </button>
              </div>
            ))}
          </div>
        </div>

        {/* Страницы */}
        <div className="bg-white rounded-lg shadow-lg p-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Страницы</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {config?.pages && Object.entries(config.pages).map(([path, page]) => (
              <div key={path} className="border rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-gray-900">{page.title}</h3>
                  <div className="flex items-center">
                    <span className={`px-2 py-1 rounded text-xs font-medium ${
                      page.enabled 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {page.enabled ? 'Доступна' : 'Недоступна'}
                    </span>
                  </div>
                </div>
                <p className="text-sm text-gray-600 mb-3">Путь: {path}</p>
                <button
                  onClick={() => updatePage(path, !page.enabled)}
                  disabled={updating[path.replace('/', '')]}
                  className={`w-full px-4 py-2 rounded font-medium transition-colors ${
                    page.enabled
                      ? 'bg-red-600 hover:bg-red-700 text-white'
                      : 'bg-green-600 hover:bg-green-700 text-white'
                  } disabled:opacity-50`}
                >
                  {updating[path.replace('/', '')] ? (
                    <span className="flex items-center justify-center">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                      Обновление...
                    </span>
                  ) : (
                    page.enabled ? 'Скрыть' : 'Показать'
                  )}
                </button>
              </div>
            ))}
          </div>
        </div>

        {/* Информация */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
          <h3 className="font-semibold text-blue-900 mb-2">Как это работает:</h3>
          <ul className="text-sm text-blue-800 space-y-1">
            <li>• <strong>Секции:</strong> Управляют видимостью кнопок на главной страницы</li>
            <li>• <strong>Страницы:</strong> Управляют доступностью страниц по прямым ссылкам</li>
            <li>• При деактивации секции кнопка исчезает с главной страницы</li>
            <li>• При деактивации страницы она становится недоступной по прямой ссылке</li>
            <li>• Изменения применяются мгновенно</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default AdminPanel;
