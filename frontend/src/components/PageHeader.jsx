import React from 'react';

const PageHeader = ({ title, subtitle, className = '' }) => {
  return (
    <header className={`text-center py-6 ${className}`}>
      <h1 className="text-4xl font-bold text-gray-900">{title}</h1>
      {subtitle && (
        <p className="text-lg text-gray-600 mt-2">{subtitle}</p>
      )}
    </header>
  );
};

export default PageHeader;
