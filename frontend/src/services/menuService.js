import { apiService } from './apiService';

// Сервис для работы с меню
export const menuService = {
  // Получить все данные меню (категории + продукты)
  async getMenuData() {
    try {
      const data = await apiService.get('/api/menu');
      return data;
    } catch (error) {
      console.error('Error fetching menu data:', error);
      throw error;
    }
  },

  // Получить только категории
  async getCategories() {
    try {
      const data = await apiService.get('/api/poster/menu.getCategories');
      return data.response || [];
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  },

  // Получить продукты (опционально по категории)
  async getProducts(categoryId = null) {
    try {
      const params = categoryId ? { category_id: categoryId } : {};
      const data = await apiService.get('/api/poster/menu.getProducts', params);
      return data.response || [];
    } catch (error) {
      console.error('Error fetching products:', error);
      throw error;
    }
  },

  // Проверить состояние API
  async checkHealth() {
    try {
      const data = await apiService.get('/api/health');
      return data;
    } catch (error) {
      console.error('Error checking API health:', error);
      throw error;
    }
  }
};

export default menuService;
