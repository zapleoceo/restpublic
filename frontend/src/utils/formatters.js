import { formatPrice, getMainPrice } from './priceUtils';

export { formatPrice, getMainPrice };

export const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU');
};

export const formatEventDate = (date) => {
  const eventDate = new Date(date);
  const now = new Date();
  const diffTime = eventDate - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays < 0) {
    return eventDate.toLocaleDateString('ru-RU', { 
      day: 'numeric', 
      month: 'long', 
      year: 'numeric' 
    });
  } else if (diffDays === 0) {
    return 'Сегодня';
  } else if (diffDays === 1) {
    return 'Завтра';
  } else if (diffDays <= 7) {
    return `Через ${diffDays} дней`;
  } else {
    return eventDate.toLocaleDateString('ru-RU', { 
      day: 'numeric', 
      month: 'long' 
    });
  }
};

export const formatPhone = (phone) => {
  return phone.replace(/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/, '+$1 ($2) $3-$4-$5');
};
