// Утилита для форматирования цен в боте
// Backend уже нормализует цены, поэтому просто форматируем
export const formatPrice = (price: string): string => {
  const numPrice = parseFloat(price);
  return numPrice.toLocaleString('ru-RU');
};

// Получение основной цены из объекта цен Poster API
export const getMainPrice = (priceObject: { [key: string]: string }): string => {
  if (!priceObject) return '0';
  // Poster API возвращает цены в формате { "1": "1000", "2": "1200" }
  // Где "1" - основной прайс-лист
  return priceObject['1'] || Object.values(priceObject)[0] || '0';
};
