import React from 'react';

const LoadingSpinner = ({ size = 'default', text = 'Загрузка...', compact = false }) => {
  if (compact) {
    return (
      <div className="loading-spinner">
        <div></div>
      </div>
    );
  }

  return (
    <div className="text-center">
      <div className="loading-spinner" style={{ marginBottom: '1rem' }}>
        <div></div>
      </div>
      {text && (
        <span 
          style={{ 
            fontSize: '1.4rem',
            color: 'var(--color-text)'
          }}
        >
          {text}
        </span>
      )}
    </div>
  );
};

export default LoadingSpinner;
