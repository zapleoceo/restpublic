// Утилиты для работы с номерами столиков

// Валидация номера столика
export const validateTableId = (tableId) => {
  if (!tableId) return false;
  const num = parseInt(tableId);
  return !isNaN(num) && num > 0 && num <= 100; // Максимум 100 столиков
};

// Форматирование номера столика для отображения
export const formatTableNumber = (tableId) => {
  if (!validateTableId(tableId)) return 'Неизвестный столик';
  return `№${tableId}`;
};

// Создание URL для Telegram бота с номером столика
export const createBotUrl = (tableId) => {
  if (!validateTableId(tableId)) return 'https://t.me/goodzone_vn';
  return `https://t.me/goodzone_vn?start=table_${tableId}`;
};

// Создание URL для меню с номером столика
export const createMenuUrl = (tableId) => {
  if (!validateTableId(tableId)) return '/m';
  return `/fast/${tableId}/menu`;
};

// Создание URL для главной страницы
export const createHomeUrl = () => {
  return '/';
};

// Извлечение номера столика из URL
export const extractTableIdFromUrl = (pathname) => {
  const match = pathname.match(/\/fast\/(\d+)/);
  return match ? match[1] : null;
};
