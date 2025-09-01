export const API_ENDPOINTS = {
  health: '/api/health',
  sections: '/api/sections',
  events: '/api/events',
  menu: '/api/menu',
  admin: '/api/admin',
  poster: '/api/poster',
};

// Определяем базовый URL в зависимости от окружения
const getBaseUrl = () => {
  // В production всегда используем https://northrepublic.me
  if (window.location.hostname === 'northrepublic.me' || window.location.hostname === 'www.northrepublic.me') {
    return 'https://northrepublic.me';
  }
  // В development используем localhost
  return 'http://localhost:3002';
};

export const BASE_URL = getBaseUrl();
