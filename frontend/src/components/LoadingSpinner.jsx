import React from 'react';
import { Utensils } from 'lucide-react';

const LoadingSpinner = ({ size = 'default', text = 'Загрузка...', compact = false }) => {
  const sizeClasses = {
    sm: 'w-4 h-4',
    default: 'w-8 h-8',
    lg: 'w-12 h-12'
  };

  const textClasses = {
    sm: 'text-sm',
    default: 'text-lg',
    lg: 'text-xl'
  };

  if (compact) {
    return (
      <div className="animate-spin">
        <Utensils className={`${sizeClasses[size]} text-orange-500`} />
      </div>
    );
  }

  return (
    <div className="text-center">
      <div className="inline-flex items-center space-x-2">
        <div className="animate-spin">
          <Utensils className={`${sizeClasses[size]} text-orange-500`} />
        </div>
        <span className={`${textClasses[size]} font-medium text-gray-700`}>{text}</span>
      </div>
    </div>
  );
};

export default LoadingSpinner;
