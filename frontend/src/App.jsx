import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import HomePage from './components/HomePage';
import MenuPage from './components/MenuPage';
import MenuPageWrapper from './components/MenuPageWrapper';
import FastAccessPage from './components/FastAccessPage';
import LasertagPage from './components/LasertagPage';
import ArcherytagPage from './components/ArcherytagPage';
import BBQZonePage from './components/BBQZonePage';
import QuestsPage from './components/QuestsPage';
import GuitarPage from './components/GuitarPage';
import BoardgamesPage from './components/BoardgamesPage';
import LoadingSpinner from './components/LoadingSpinner';
import ErrorBoundary from './components/ErrorBoundary';
import AdminPanel from './components/AdminPanel';
import LoginPage from './components/LoginPage';
import ProtectedRoute from './components/ProtectedRoute';
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
             {t('try_again')}
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
              <Route path="/" element={<ProtectedRoute><HomePage /></ProtectedRoute>} />
              <Route path="/m" element={<ProtectedRoute><MenuPage menuData={menuData} /></ProtectedRoute>} />
              <Route path="/fast/:tableId" element={<ProtectedRoute><FastAccessPage /></ProtectedRoute>} />
              <Route path="/fast/:tableId/menu" element={<ProtectedRoute><MenuPageWrapper menuData={menuData} /></ProtectedRoute>} />
              <Route path="/lasertag" element={<ProtectedRoute><LasertagPage /></ProtectedRoute>} />
              <Route path="/archerytag" element={<ProtectedRoute><ArcherytagPage /></ProtectedRoute>} />
              <Route path="/bbq_zone" element={<ProtectedRoute><BBQZonePage /></ProtectedRoute>} />
              <Route path="/quests" element={<ProtectedRoute><QuestsPage /></ProtectedRoute>} />
              <Route path="/guitar" element={<ProtectedRoute><GuitarPage /></ProtectedRoute>} />
              <Route path="/boardgames" element={<ProtectedRoute><BoardgamesPage /></ProtectedRoute>} />
              <Route path="/cinema" element={<ProtectedRoute><div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="text-center"><h1 className="text-2xl font-bold mb-4">Кинотеатр</h1><p className="text-gray-600">Страница в разработке</p></div></div></ProtectedRoute>} />
              <Route path="/admin" element={<AdminPanel />} />
              <Route path="/admin/login" element={<LoginPage />} />
            </Routes>
          </main>
        </div>
      </Router>
    </ErrorBoundary>
  );
}

export default App;
