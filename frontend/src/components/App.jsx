import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import HomePage from './components/HomePage';
import MenuPage from './components/MenuPage';
import LoadingDisplay from './components/LoadingDisplay';
import ErrorBoundary from './components/ErrorBoundary';
import ErrorDisplay from './components/ErrorDisplay';
import PageContainer from './components/PageContainer';
import { useMenuData } from './hooks/useMenuData';
import './App.css';

function App() {
  const { t } = useTranslation();
  const { menuData, loading, error, refetch } = useMenuData();

  if (loading) {
    return <LoadingDisplay />;
  }

  if (error) {
    return <ErrorDisplay error={error} onRetry={refetch} />;
  }

  return (
    <ErrorBoundary>
      <Router>
        <PageContainer>
          <main>
            <Routes>
              <Route path="/" element={<HomePage menuData={menuData} />} />
              <Route path="/menu/:categoryId?" element={<MenuPage menuData={menuData} />} />
            </Routes>
          </main>
        </PageContainer>
      </Router>
    </ErrorBoundary>
  );
}

export default App;
