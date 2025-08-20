import React from 'react';

const EmptyState = ({ 
  icon = '🍽️', 
  title = 'Данные не найдены', 
  description = 'В данной категории пока нет данных',
  className = '' 
}) => {
  return (
    <div className={`text-center py-12 ${className}`}>
      <div className="text-gray-400 text-6xl mb-4">{icon}</div>
      <h3 className="text-xl font-semibold text-gray-900 mb-2">
        {title}
      </h3>
      <p className="text-gray-600">
        {description}
      </p>
    </div>
  );
};

export default EmptyState;
