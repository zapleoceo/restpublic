import axios from 'axios';

// Создаем экземпляр axios для нашего backend
const backendApi = axios.create({
  baseURL: 'http://localhost:3001/api',
  timeout: 10000,
});

// Интерцептор для обработки ошибок
backendApi.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('Backend API Error:', error);
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
  // Получить все категории и продукты
  async getMenuData(): Promise<{ categories: Category[], products: Product[] }> {
    try {
      const response = await backendApi.get('/menu');
      return {
        categories: response.data.categories || [],
        products: response.data.products || []
      };
    } catch (error) {
      console.error('Error fetching menu data:', error);
      throw error;
    }
  },

  // Получить все категории
  async getCategories(): Promise<Category[]> {
    try {
      const menuData = await this.getMenuData();
      return menuData.categories;
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  },

  // Получить товары категории
  async getProducts(categoryId?: number): Promise<Product[]> {
    try {
      const menuData = await this.getMenuData();
      if (categoryId) {
        // Фильтруем продукты по категории
        return menuData.products.filter((product: any) => 
          product.menu_category_id === categoryId.toString()
        );
      }
      return menuData.products;
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
