import React, { useState, useEffect } from 'react';
import { SectionEditor } from './SectionEditor';
import { apiService } from '../../services/apiService';

const sections = [
  { id: 'intro', name: 'Intro', icon: '🏠' },
  { id: 'about', name: 'About', icon: 'ℹ️' },
  { id: 'menu', name: 'Menu', icon: '🍽️' },
  { id: 'services', name: 'Services', icon: '🔧' },
  { id: 'events', name: 'Events', icon: '📅' },
  { id: 'testimonials', name: 'Testimonials', icon: '💬' }
];

export const NewAdminPanel = () => {
  const [activeSection, setActiveSection] = useState('intro');
  const [sectionsData, setSectionsData] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchSectionsData();
  }, []);

  const fetchSectionsData = async () => {
    try {
      setLoading(true);
      const data = await apiService.get('/api/sections');
      setSectionsData(data);
    } catch (err) {
      console.error('Ошибка загрузки данных секций:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (sectionId, data) => {
    try {
      await apiService.put(`/api/sections/${sectionId}`, data);
      
      // Обновляем локальное состояние
      setSectionsData(prev => ({
        ...prev,
        [sectionId]: data
      }));
      
      console.log(`Секция ${sectionId} успешно сохранена`);
    } catch (err) {
      console.error('Ошибка сохранения секции:', err);
      throw err;
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-neutral-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
          <p className="text-neutral-600">Загрузка админ панели...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-neutral-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">❌</div>
          <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
            Ошибка загрузки
          </h3>
          <p className="text-neutral-600 mb-4">{error}</p>
          <button
            onClick={fetchSectionsData}
            className="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Попробовать снова
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-panel min-h-screen bg-neutral-50">
      <div className="flex">
        {/* Боковая панель */}
        <div className="admin-sidebar w-64 bg-white shadow-lg min-h-screen">
          <div className="p-6">
            <h2 className="text-2xl font-serif font-bold text-primary-900 mb-6">
              Управление сайтом
            </h2>
            
            <nav className="admin-nav">
              <ul className="space-y-2">
                {sections.map(section => (
                  <li key={section.id}>
                    <button
                      onClick={() => setActiveSection(section.id)}
                      className={`w-full text-left p-3 rounded-lg transition-colors ${
                        activeSection === section.id
                          ? 'bg-primary-500 text-white'
                          : 'text-neutral-700 hover:bg-neutral-100'
                      }`}
                    >
                      <div className="flex items-center space-x-3">
                        <span className="text-lg">{section.icon}</span>
                        <span className="font-medium">{section.name}</span>
                      </div>
                    </button>
                  </li>
                ))}
              </ul>
            </nav>
          </div>
        </div>

        {/* Основной контент */}
        <div className="admin-content flex-1 p-8">
          <div className="max-w-4xl mx-auto">
            <div className="mb-8">
              <h1 className="text-3xl font-serif font-bold text-primary-900 mb-2">
                {sections.find(s => s.id === activeSection)?.name}
              </h1>
              <p className="text-neutral-600">
                Управление контентом и настройками секции
              </p>
            </div>

            <SectionEditor
              section={activeSection}
              data={sectionsData[activeSection] || {}}
              onSave={handleSave}
            />
          </div>
        </div>
      </div>
    </div>
  );
};
