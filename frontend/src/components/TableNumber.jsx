import React from 'react';
import { formatTableNumber } from '../utils/tableUtils';

const TableNumber = ({ tableId, className = '' }) => {
  const formattedNumber = formatTableNumber(tableId);

  return (
    <div className={`text-center mb-8 ${className}`}>
      <div className="inline-block bg-white rounded-full px-8 py-4 shadow-lg">
        <h1 className="text-4xl font-bold text-gray-900">
          {formattedNumber}
        </h1>
        <p className="text-sm text-gray-600 mt-1">
          Ваш столик
        </p>
      </div>
    </div>
  );
};

export default TableNumber;
