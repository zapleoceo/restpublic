import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import HomePage from './components/HomePage';
import MenuPage from './components/MenuPage';
import MenuPageWrapper from './components/MenuPageWrapper';
import FastAccessPage from './components/FastAccessPage';
import LoadingSpinner from './components/LoadingSpinner';
import ErrorBoundary from './components/ErrorBoundary';
import './App.css';

function App() {
  const { t } = useTranslation();
  const [menuData, setMenuData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchMenuData();
  }, []);

  const fetchMenuData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('🔄 Fetching menu data...');
      const response = await fetch('/api/menu');
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('✅ Menu data loaded:', { categories: data.categories.length, products: data.products.length });
      
      setMenuData(data);
    } catch (err) {
      console.error('❌ Error fetching menu data:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <LoadingSpinner />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-orange-400 text-6xl mb-4">⚠️</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-4">{t('error')}</h1>
          <p className="text-gray-600 mb-6">{error}</p>
          <button 
            onClick={fetchMenuData}
            className="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg transition-colors"
          >
            Попробовать снова
          </button>
        </div>
      </div>
    );
  }

  return (
    <ErrorBoundary>
      <Router>
        <div className="min-h-screen bg-gray-50">
          <main>
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/m" element={<MenuPage menuData={menuData} />} />
              <Route path="/fast/:tableId" element={<FastAccessPage />} />
              <Route path="/fast/:tableId/menu" element={<MenuPageWrapper menuData={menuData} />} />
              <Route path="/lt" element={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="text-center"><h1 className="text-2xl font-bold mb-4">Лазертаг</h1><p className="text-gray-600">Страница в разработке</p></div></div>} />
              <Route path="/bow" element={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="text-center"><h1 className="text-2xl font-bold mb-4">Стрельба из лука</h1><p className="text-gray-600">Страница в разработке</p></div></div>} />
              <Route path="/cinema" element={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="text-center"><h1 className="text-2xl font-bold mb-4">Кинотеатр</h1><p className="text-gray-600">Страница в разработке</p></div></div>} />
              <Route path="/rent" element={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="text-center"><h1 className="text-2xl font-bold mb-4">Аренда беседки</h1><p className="text-gray-600">Страница в разработке</p></div></div>} />
            </Routes>
          </main>
        </div>
      </Router>
    </ErrorBoundary>
  );
}

export default App;
