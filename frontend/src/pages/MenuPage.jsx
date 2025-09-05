import React, { useState, useEffect } from 'react';
import MenuHeader from '../components/MenuHeader';
import MenuContent from '../components/MenuContent';
import MenuFooter from '../components/MenuFooter';
import './MenuPage.css';

const MenuPage = () => {
  const [menuData, setMenuData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Загружаем данные меню
    const fetchMenuData = async () => {
      try {
        setLoading(true);
        const response = await fetch('/api/menu');
        if (!response.ok) {
          throw new Error('Failed to fetch menu data');
        }
        const data = await response.json();
        setMenuData(data);
      } catch (err) {
        setError(err.message);
        console.error('Error fetching menu:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchMenuData();
  }, []);

  if (loading) {
    return (
      <div className="menu-page">
        <MenuHeader />
        <div className="menu-loading">
          <div className="loader">
            <div></div>
            <div></div>
            <div></div>
          </div>
          <p>Загрузка меню...</p>
        </div>
        <MenuFooter />
      </div>
    );
  }

  if (error) {
    return (
      <div className="menu-page">
        <MenuHeader />
        <div className="menu-error">
          <h2>Ошибка загрузки меню</h2>
          <p>{error}</p>
          <button onClick={() => window.location.reload()}>
            Попробовать снова
          </button>
        </div>
        <MenuFooter />
      </div>
    );
  }

  return (
    <div className="menu-page">
      <MenuHeader />
      <MenuContent menuData={menuData} />
      <MenuFooter />
    </div>
  );
};

export default MenuPage;
