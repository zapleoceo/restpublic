import React, { createContext, useContext, useState } from 'react';

const TableContext = createContext();

export const useTable = () => {
  const context = useContext(TableContext);
  if (!context) {
    throw new Error('useTable must be used within a TableProvider');
  }
  return context;
};

export const TableProvider = ({ children, tableId }) => {
  const [tableNumber, setTableNumber] = useState(tableId);

  const value = {
    tableNumber,
    setTableNumber,
    tableId: tableNumber // для обратной совместимости
  };

  return (
    <TableContext.Provider value={value}>
      {children}
    </TableContext.Provider>
  );
};

export default TableContext;
