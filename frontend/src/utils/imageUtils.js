// Утилита для работы с изображениями
export const getImageUrl = (photo) => {
  if (!photo) return null;
  return `https://joinposter.com${photo}`;
};

// Проверка доступности изображения
export const isImageAvailable = (photo) => {
  return photo && photo.trim() !== '';
};
