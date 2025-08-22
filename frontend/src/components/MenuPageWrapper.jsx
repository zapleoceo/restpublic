import React from 'react';
import { useParams } from 'react-router-dom';
import { TableProvider } from '../contexts/TableContext';
import MenuPage from './MenuPage';

const MenuPageWrapper = ({ menuData }) => {
  const { tableId } = useParams();

  return (
    <TableProvider tableId={tableId}>
      <MenuPage menuData={menuData} />
    </TableProvider>
  );
};

export default MenuPageWrapper;
