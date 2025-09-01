import React from 'react';
import { useParams } from 'react-router-dom';
import { TableProvider } from '../contexts/TableContext';
import { CartProvider } from '../contexts/CartContext';
import MenuPage from './MenuPage';

const MenuPageWrapper = ({ menuData }) => {
  const { tableId } = useParams();

  return (
    <CartProvider>
      <TableProvider tableId={tableId}>
        <MenuPage menuData={menuData} />
      </TableProvider>
    </CartProvider>
  );
};

export default MenuPageWrapper;
