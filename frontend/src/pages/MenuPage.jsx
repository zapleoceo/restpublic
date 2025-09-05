import React from 'react';
import MenuHeader from '../components/MenuHeader';
import MenuContent from '../components/MenuContent';
import MenuFooter from '../components/MenuFooter';
import './MenuPage.css';

const MenuPage = () => {
  console.log('MenuPage rendering...');
  
  // Простые тестовые данные вместо API вызова
  const menuData = {
    categories: [
      { id: 1, name: 'Завтраки', products: [] },
      { id: 2, name: 'Обеды', products: [] },
      { id: 3, name: 'Ужины', products: [] }
    ]
  };

  return (
    <div className="menu-page">
      <MenuHeader />
      <MenuContent menuData={menuData} />
      <MenuFooter />
    </div>
  );
};

export default MenuPage;
