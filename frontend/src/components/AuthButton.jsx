import React, { useState } from 'react';
import './AuthButton.css';

const AuthButton = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [userName, setUserName] = useState('');

  const handleTelegramAuth = () => {
    // TODO: Реализовать авторизацию через Telegram
    console.log('Telegram auth clicked');
    
    // Временная заглушка
    setIsAuthenticated(true);
    setUserName('Пользователь');
  };

  const handleLogout = () => {
    setIsAuthenticated(false);
    setUserName('');
  };

  if (isAuthenticated) {
    return (
      <div className="auth-button">
        <div className="auth-button__user">
          <span className="auth-button__name">{userName}</span>
          <button 
            className="auth-button__logout"
            onClick={handleLogout}
            title="Выйти"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16,17 21,12 16,7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="auth-button">
      <button 
        className="auth-button__login"
        onClick={handleTelegramAuth}
      >
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.61 7.59c-.12.56-.44.7-.89.44l-2.46-1.81-1.19 1.15c-.13.13-.24.24-.49.24l.18-2.56 4.57-4.13c.2-.18-.04-.28-.31-.1l-5.64 3.55-2.43-.76c-.53-.16-.54-.53.11-.79l9.57-3.69c.44-.16.83.1.69.79z"/>
        </svg>
        Войти через Telegram
      </button>
    </div>
  );
};

export default AuthButton;
