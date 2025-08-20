import axios from 'axios';

// Создаем экземпляр axios для Poster API
const posterApi = axios.create({
  baseURL: 'https://joinposter.com/api',
  timeout: 10000,
});

// Интерцептор для добавления токена к запросам
posterApi.interceptors.request.use((config) => {
  const token = process.env.POSTER_API_TOKEN;
  if (token) {
    config.params = { ...config.params, token: token };
  }
  return config;
});

// Интерцептор для обработки ошибок
posterApi.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('Poster API Error:', error);
    return Promise.reject(error);
  }
);

// Типы данных в соответствии с реальным API
export interface Category {
  category_id: string;
  category_name: string;
  category_photo?: string;
  category_color?: string;
}

export interface Product {
  product_id: string;
  product_name: string;
  category_name: string;
  menu_category_id: string;
  price: { [key: string]: string };
  photo?: string;
  unit?: string;
  cost?: string;
  profit?: { [key: string]: string };
  ingredients?: any[];
}

// Функции для работы с API
export const posterService = {
  // Получить все категории
  async getCategories(): Promise<Category[]> {
    try {
      const response = await posterApi.get('/menu.getCategories');
      return response.data.response;
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  },

  // Получить товары категории
  async getProducts(categoryId?: number): Promise<Product[]> {
    try {
      const params = categoryId ? { category_id: categoryId } : {};
      const response = await posterApi.get('/menu.getProducts', { params });
      
      // Фильтруем только видимые товары (hidden !== "1")
      const visibleProducts = (response.data.response || []).filter((product: any) => 
        product.hidden !== "1"
      );
      
      return visibleProducts;
    } catch (error) {
      console.error('Error fetching products:', error);
      throw error;
    }
  },

  // Получить изображение товара
  getProductImage(imageId: string, size: string = 'medium'): string {
    if (!imageId) return '';
    return `https://joinposter.com/api/files.getFile?file_id=${imageId}&size=${size}`;
  }
};

export default posterService;
