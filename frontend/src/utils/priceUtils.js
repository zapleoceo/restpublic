// Утилита для форматирования цен (учитывая, что backend уже нормализует цены)
export const formatPrice = (price) => {
  if (!price && price !== 0) return 'Цена не указана';
  
  let numPrice;
  
  // Если price является объектом (например, из Poster API)
  if (typeof price === 'object' && price !== null) {
    // Берем основную цену из объекта
    numPrice = getMainPrice(price);
    if (!numPrice && numPrice !== 0) return 'Цена не указана';
    numPrice = parseFloat(numPrice);
  } else {
    // Обычное число или строка
    numPrice = typeof price === 'string' ? parseFloat(price) : price;
  }
  
  if (isNaN(numPrice)) return 'Цена не указана';
  
  // Форматируем с валютой
  return `${numPrice.toLocaleString('vi-VN')} ₫`;
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
