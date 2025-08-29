import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { CartProvider } from './contexts/CartContext';
import { useCart } from './contexts/CartContext';
import { useTranslation } from 'react-i18next';
import './i18n';

// Components
import Header from './components/Header';
import HomePage from './components/HomePage';
import MenuPage from './components/MenuPage';
import MenuPageWrapper from './components/MenuPageWrapper';
import LoadingSpinner from './components/LoadingSpinner';
import ErrorBoundary from './components/ErrorBoundary';
import LoginPage from './components/LoginPage';
import AdminPanel from './components/AdminPanel';
import ProtectedRoute from './components/ProtectedRoute';

// Page Components
import ArcherytagPage from './components/ArcherytagPage';
import BBQZonePage from './components/BBQZonePage';
import BoardgamesPage from './components/BoardgamesPage';
import CinemaPage from './components/CinemaPage';
import GuitarPage from './components/GuitarPage';
import LasertagPage from './components/LasertagPage';
import QuestsPage from './components/QuestsPage';
import YogaPage from './components/YogaPage';
import FastAccessPage from './components/FastAccessPage';

// CSS
import './App.css';

function AppContent() {
  const { t } = useTranslation();
  const { setSession } = useCart();

  // Обработка параметра session в URL (для авторизации через Telegram)
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionParam = urlParams.get('session');
    
    if (sessionParam) {
      try {
        const sessionData = JSON.parse(decodeURIComponent(sessionParam));
        console.log('🔐 Получены данные сессии из URL:', sessionData);
        
        // Сохраняем сессию
        setSession(sessionData);
        
        // Очищаем параметр из URL
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('session');
        window.history.replaceState({}, '', newUrl);
        
        // Показываем уведомление об успешной авторизации
        alert('✅ Авторизация через Telegram успешно завершена!');
      } catch (error) {
        console.error('❌ Ошибка обработки сессии из URL:', error);
      }
    }
  }, [setSession]);

  return (
    <Router>
      <div className="App">
        <ErrorBoundary>
          <Header />
          <main>
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/menu" element={<MenuPageWrapper />} />
              <Route path="/menu-view" element={<MenuPage />} />
              <Route path="/login" element={<LoginPage />} />
              <Route path="/admin" element={
                <ProtectedRoute>
                  <AdminPanel />
                </ProtectedRoute>
              } />
              
              {/* Activity Pages */}
              <Route path="/archerytag" element={<ArcherytagPage />} />
              <Route path="/bbq" element={<BBQZonePage />} />
              <Route path="/boardgames" element={<BoardgamesPage />} />
              <Route path="/cinema" element={<CinemaPage />} />
              <Route path="/guitar" element={<GuitarPage />} />
              <Route path="/lasertag" element={<LasertagPage />} />
              <Route path="/quests" element={<QuestsPage />} />
              <Route path="/yoga" element={<YogaPage />} />
              <Route path="/fast-access" element={<FastAccessPage />} />
              
              {/* Fallback route */}
              <Route path="*" element={<HomePage />} />
            </Routes>
          </main>
        </ErrorBoundary>
      </div>
    </Router>
  );
}

function App() {
  return (
    <CartProvider>
      <AppContent />
    </CartProvider>
  );
}

export default App;
