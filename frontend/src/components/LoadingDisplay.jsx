import React from 'react';
import LoadingSpinner from './LoadingSpinner';

const LoadingDisplay = ({ className = '' }) => {
  return (
    <div className={`min-h-screen bg-gray-50 flex items-center justify-center ${className}`}>
      <LoadingSpinner />
    </div>
  );
};

export default LoadingDisplay;
