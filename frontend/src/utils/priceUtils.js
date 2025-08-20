// Утилита для форматирования цен (учитывая, что backend уже нормализует цены)
export const formatPrice = (price) => {
  if (!price) return 'Цена не указана';
  // Backend уже нормализует цены (делит на 100), поэтому просто форматируем
  const numPrice = typeof price === 'string' ? parseFloat(price) : price;
  return `${numPrice.toLocaleString()}`;
};

// Получение основной цены из объекта цен Poster API
export const getMainPrice = (priceObject) => {
  if (!priceObject) return null;
  // Poster API возвращает цены в формате { "1": "1000", "2": "1200" }
  // Где "1" - основной прайс-лист
  return priceObject['1'] || Object.values(priceObject)[0];
};

// Нормализация цены (для случаев, когда нужно делить на 100)
export const normalizePrice = (value) => {
  if (value === undefined || value === null) return value;
  const num = Number.parseFloat(String(value));
  if (Number.isNaN(num)) return value;
  return Math.floor(num / 100);
};
