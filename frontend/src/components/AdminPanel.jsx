import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

const AdminPanel = () => {
  const { t, i18n } = useTranslation();
  const [activeTab, setActiveTab] = useState('translations');
  const [translations, setTranslations] = useState({});
  const [configs, setConfigs] = useState({});
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [editingItem, setEditingItem] = useState(null);
  const [editData, setEditData] = useState('');
  const [versions, setVersions] = useState({});
  const [selectedConfig, setSelectedConfig] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      // Загружаем статистику
      const statsResponse = await fetch('/api/admin/stats');
      if (statsResponse.ok) {
        const statsData = await statsResponse.json();
        setStats(statsData.data);
      }

      // Загружаем переводы
      const translationsResponse = await fetch('/api/admin/translations');
      if (translationsResponse.ok) {
        const translationsData = await translationsResponse.json();
        setTranslations(translationsData.data);
      }

      // Загружаем конфигурации
      const configsResponse = await fetch('/api/admin/configs');
      if (configsResponse.ok) {
        const configsData = await configsResponse.json();
        setConfigs(configsData.data);
      }

      // Загружаем версии для всех конфигураций
      const versionsData = {};
      for (const configType of Object.keys(configsData?.data || {})) {
        const versionsResponse = await fetch(`/api/admin/configs/${configType}/versions`);
        if (versionsResponse.ok) {
          const versionData = await versionsResponse.json();
          versionsData[configType] = versionData.data;
        }
      }
      setVersions(versionsData);
    } catch (error) {
      console.error('Ошибка загрузки данных:', error);
      setMessage('Ошибка загрузки данных');
    } finally {
      setLoading(false);
    }
  };

  const updateTranslation = async (language, data) => {
    try {
      const response = await fetch(`/api/admin/translations/${language}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ data }),
      });

      if (response.ok) {
        setMessage(`Переводы для ${language} обновлены`);
        setEditingItem(null);
        setEditData('');
        loadData();
      } else {
        setMessage('Ошибка обновления переводов');
      }
    } catch (error) {
      console.error('Ошибка обновления:', error);
      setMessage('Ошибка обновления');
    }
  };

  const updateConfig = async (type, data) => {
    try {
      const response = await fetch(`/api/admin/configs/${type}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ data }),
      });

      if (response.ok) {
        setMessage(`Конфигурация ${type} обновлена`);
        setEditingItem(null);
        setEditData('');
        loadData();
      } else {
        setMessage('Ошибка обновления конфигурации');
      }
    } catch (error) {
      console.error('Ошибка обновления:', error);
      setMessage('Ошибка обновления');
    }
  };

  const startEditing = (type, key, data) => {
    setEditingItem({ type, key });
    setEditData(JSON.stringify(data, null, 2));
  };

  const saveEdit = () => {
    try {
      const parsed = JSON.parse(editData);
      if (editingItem.type === 'translation') {
        updateTranslation(editingItem.key, parsed);
      } else if (editingItem.type === 'config') {
        updateConfig(editingItem.key, parsed);
      }
    } catch (error) {
      setMessage('Неверный JSON формат');
    }
  };

  const cancelEdit = () => {
    setEditingItem(null);
    setEditData('');
  };

  const renderTranslationsTab = () => (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Управление переводами</h3>
      {Object.entries(translations).map(([language, data]) => (
        <div key={language} className="border rounded-lg p-4">
          <div className="flex justify-between items-center mb-2">
            <h4 className="font-medium">Язык: {language}</h4>
            {editingItem?.type === 'translation' && editingItem?.key === language ? (
              <div className="flex space-x-2">
                <button
                  onClick={saveEdit}
                  className="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600"
                >
                  Сохранить
                </button>
                <button
                  onClick={cancelEdit}
                  className="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600"
                >
                  Отмена
                </button>
              </div>
            ) : (
              <button
                onClick={() => startEditing('translation', language, data)}
                className="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
              >
                Редактировать
              </button>
            )}
          </div>
          <div className="text-sm text-gray-600 mb-2">
            Ключей: {Object.keys(data).length}
          </div>
          {editingItem?.type === 'translation' && editingItem?.key === language && (
            <div className="mt-4">
              <textarea
                value={editData}
                onChange={(e) => setEditData(e.target.value)}
                className="w-full h-64 p-2 border rounded font-mono text-sm"
                placeholder="Введите JSON данные..."
              />
            </div>
          )}
        </div>
      ))}
    </div>
  );

  const renderConfigsTab = () => (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Управление конфигурациями</h3>
      {Object.entries(configs).map(([type, data]) => (
        <div key={type} className="border rounded-lg p-4">
          <div className="flex justify-between items-center mb-2">
            <h4 className="font-medium">Тип: {type}</h4>
            <div className="flex space-x-2">
              {editingItem?.type === 'config' && editingItem?.key === type ? (
                <>
                  <button
                    onClick={saveEdit}
                    className="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600"
                  >
                    Сохранить
                  </button>
                  <button
                    onClick={cancelEdit}
                    className="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600"
                  >
                    Отмена
                  </button>
                </>
              ) : (
                <>
                  <button
                    onClick={() => startEditing('config', type, data)}
                    className="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600"
                  >
                    Редактировать
                  </button>
                  <button
                    onClick={() => setSelectedConfig(type)}
                    className="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
                  >
                    Версии
                  </button>
                </>
              )}
            </div>
          </div>
          <div className="text-sm text-gray-600 mb-2">
            Ключей: {Object.keys(data).length}
            {versions[type] && (
              <span className="ml-2 text-blue-600">
                • Версия: {versions[type].currentVersion || 1}
              </span>
            )}
          </div>
          {editingItem?.type === 'config' && editingItem?.key === type && (
            <div className="mt-4">
              <textarea
                value={editData}
                onChange={(e) => setEditData(e.target.value)}
                className="w-full h-64 p-2 border rounded font-mono text-sm"
                placeholder="Введите JSON данные..."
              />
            </div>
          )}
        </div>
      ))}
    </div>
  );

  const handleExport = async () => {
    try {
      const response = await fetch('/api/admin/export');
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `mongodb-export-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        setMessage('Экспорт завершен');
      } else {
        setMessage('Ошибка экспорта');
      }
    } catch (error) {
      console.error('Ошибка экспорта:', error);
      setMessage('Ошибка экспорта');
    }
  };

  const handleImport = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
      const text = await file.text();
      const data = JSON.parse(text);
      
      const response = await fetch('/api/admin/import', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
      });

      if (response.ok) {
        const result = await response.json();
        setMessage(result.message);
        loadData();
      } else {
        setMessage('Ошибка импорта');
      }
    } catch (error) {
      console.error('Ошибка импорта:', error);
      setMessage('Ошибка импорта: неверный формат файла');
    }
    
    // Очищаем input
    event.target.value = '';
  };

  const handleClear = async () => {
    if (!confirm('ВНИМАНИЕ! Это действие удалит ВСЕ данные из MongoDB. Продолжить?')) {
      return;
    }

    try {
      const response = await fetch('/api/admin/clear', {
        method: 'DELETE',
      });

      if (response.ok) {
        setMessage('Все данные очищены');
        loadData();
      } else {
        setMessage('Ошибка очистки');
      }
    } catch (error) {
      console.error('Ошибка очистки:', error);
      setMessage('Ошибка очистки');
    }
  };

  const handleRestoreVersion = async (configType, version) => {
    if (!confirm(`Восстановить конфигурацию ${configType} до версии ${version}?`)) {
      return;
    }

    try {
      const response = await fetch(`/api/admin/configs/${configType}/restore/${version}`, {
        method: 'POST',
      });

      if (response.ok) {
        setMessage(`Конфигурация ${configType} восстановлена до версии ${version}`);
        loadData();
      } else {
        setMessage('Ошибка восстановления версии');
      }
    } catch (error) {
      console.error('Ошибка восстановления:', error);
      setMessage('Ошибка восстановления версии');
    }
  };

  const renderStatsTab = () => (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Статистика MongoDB</h3>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-blue-100 p-4 rounded-lg">
          <div className="text-2xl font-bold text-blue-600">{stats.translations || 0}</div>
          <div className="text-sm text-gray-600">Переводов</div>
        </div>
        <div className="bg-green-100 p-4 rounded-lg">
          <div className="text-2xl font-bold text-green-600">{stats.configs || 0}</div>
          <div className="text-sm text-gray-600">Конфигураций</div>
        </div>
        <div className="bg-purple-100 p-4 rounded-lg">
          <div className="text-2xl font-bold text-purple-600">{stats.total || 0}</div>
          <div className="text-sm text-gray-600">Всего записей</div>
        </div>
      </div>
      
      <div className="mt-6 space-y-4">
        <div className="flex flex-wrap gap-4">
          <button
            onClick={loadData}
            className="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600"
          >
            Обновить данные
          </button>
          
          <button
            onClick={handleExport}
            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            📤 Экспорт данных
          </button>
          
          <label className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 cursor-pointer">
            📥 Импорт данных
            <input
              type="file"
              accept=".json"
              onChange={handleImport}
              className="hidden"
            />
          </label>
          
          <button
            onClick={handleClear}
            className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
          >
            🗑️ Очистить все
          </button>
        </div>
        
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h4 className="font-semibold text-yellow-800 mb-2">⚠️ Важная информация:</h4>
          <ul className="text-sm text-yellow-700 space-y-1">
            <li>• <strong>Экспорт</strong> - скачивает все данные в JSON файл</li>
            <li>• <strong>Импорт</strong> - загружает данные из JSON файла (перезаписывает существующие)</li>
            <li>• <strong>Очистка</strong> - удаляет ВСЕ данные из MongoDB (необратимо!)</li>
            <li>• Рекомендуется делать экспорт перед импортом или очисткой</li>
          </ul>
        </div>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow-lg">
          {/* Header */}
          <div className="border-b border-gray-200 px-6 py-4">
            <h1 className="text-2xl font-bold text-gray-900">Админ панель MongoDB</h1>
            <p className="text-gray-600 mt-1">Управление переводами и конфигурациями</p>
            </div>

          {/* Message */}
          {message && (
            <div className="mx-6 mt-4 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded">
              {message}
                </div>
              )}

          {/* Tabs */}
          <div className="border-b border-gray-200">
            <nav className="flex space-x-8 px-6">
              <button
                onClick={() => setActiveTab('translations')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'translations'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Переводы
              </button>
              <button
                onClick={() => setActiveTab('configs')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'configs'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Конфигурации
              </button>
              <button
                onClick={() => setActiveTab('stats')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'stats'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Статистика
              </button>
            </nav>
            </div>

          {/* Content */}
          <div className="p-6">
            {activeTab === 'translations' && renderTranslationsTab()}
            {activeTab === 'configs' && renderConfigsTab()}
            {activeTab === 'stats' && renderStatsTab()}
          </div>

          {/* Modal для версий */}
          {selectedConfig && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold">Версии конфигурации: {selectedConfig}</h3>
                  <button
                    onClick={() => setSelectedConfig(null)}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    ✕
                  </button>
        </div>

                {versions[selectedConfig]?.versions?.length > 0 ? (
                  <div className="space-y-3">
                    {versions[selectedConfig].versions.map((version, index) => (
                      <div key={version.version} className="border rounded-lg p-4">
                        <div className="flex justify-between items-center mb-2">
                          <div>
                            <span className="font-medium">Версия {version.version}</span>
                            {version.version === versions[selectedConfig].currentVersion && (
                              <span className="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                Текущая
                    </span>
                            )}
                  </div>
                          <div className="flex space-x-2">
                            <span className="text-sm text-gray-500">
                              {new Date(version.createdAt).toLocaleString()}
                            </span>
                            {version.version !== versions[selectedConfig].currentVersion && (
                <button
                                onClick={() => handleRestoreVersion(selectedConfig, version.version)}
                                className="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
                              >
                                Восстановить
                              </button>
                            )}
                          </div>
                        </div>
                        <div className="text-sm text-gray-600 mb-2">
                          {version.comment}
                        </div>
                        <div className="text-xs text-gray-500">
                          Ключей: {Object.keys(version.data).length}
                        </div>
              </div>
            ))}
          </div>
                ) : (
                  <div className="text-center text-gray-500 py-8">
                    Версии не найдены
        </div>
                )}
                  </div>
                </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminPanel;
