import React from 'react';
import { Utensils } from 'lucide-react';

const LoadingSpinner = () => {
  return (
    <div className="text-center">
      <div className="inline-flex items-center space-x-2">
        <div className="animate-spin">
          <Utensils className="w-8 h-8 text-orange-500" />
        </div>
        <span className="text-lg font-medium text-gray-700">Загрузка меню...</span>
      </div>
    </div>
  );
};

export default LoadingSpinner;
