import axios from 'axios';

// Создаем экземпляр axios для Poster API через backend прокси
const posterApi = axios.create({
  baseURL: '/api', // Используем backend прокси
  timeout: 10000,
});

// Интерцептор для обработки ошибок
posterApi.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('Poster API Error:', error);
    return Promise.reject(error);
  }
);

// Функции для работы с API
export const posterService = {
  // Получить все категории
  async getCategories() {
    try {
      const response = await posterApi.get('/menu.getCategories');
      return response.data.response;
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  },

  // Получить товары категории
  async getProducts(categoryId) {
    try {
      const params = categoryId ? { category_id: categoryId } : {};
      const response = await posterApi.get('/menu.getProducts', { params });
      return response.data.response;
    } catch (error) {
      console.error('Error fetching products:', error);
      throw error;
    }
  },

  // Получить изображение товара
  getProductImage(imageId, size = 'medium') {
    if (!imageId) return '';
    const token = import.meta.env.VITE_POSTER_API_TOKEN;
    return `https://joinposter.com/api/files.getFile?file_id=${imageId}&size=${size}&token=${token}`;
  }
};

export default posterService;
