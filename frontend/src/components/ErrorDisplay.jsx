import React from 'react';
import { useTranslation } from 'react-i18next';
import Button from './Button';

const ErrorDisplay = ({ error, onRetry, className = '' }) => {
  const { t } = useTranslation();

  return (
    <div className={`min-h-screen bg-gray-50 flex items-center justify-center ${className}`}>
      <div className="text-center">
        <div className="text-orange-400 text-6xl mb-4">⚠️</div>
        <h1 className="text-2xl font-bold text-gray-900 mb-4">{t('error')}</h1>
        <p className="text-gray-600 mb-6">{error}</p>
        {onRetry && (
          <Button onClick={onRetry} size="lg">
            {t('try_again')}
          </Button>
        )}
      </div>
    </div>
  );
};

export default ErrorDisplay;
