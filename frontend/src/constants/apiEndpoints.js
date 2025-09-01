export const API_ENDPOINTS = {
  // Основные эндпоинты
  health: '/api/health',
  sections: '/api/sections',
  events: '/api/events',
  menu: '/api/menu',
  
  // Админ панель
  admin: {
    sections: '/api/admin/sections',
    events: '/api/admin/events',
    translations: '/api/admin/translations',
    upload: '/api/admin/upload',
  },
  
  // Poster API прокси
  poster: {
    categories: '/api/poster/menu.getCategories',
    products: '/api/poster/menu.getProducts',
  }
};

export const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3002';
