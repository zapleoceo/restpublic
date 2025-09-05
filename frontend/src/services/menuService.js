import { API_ENDPOINTS, BASE_URL } from '../constants/apiEndpoints';

class MenuService {
  constructor() {
    this.baseURL = BASE_URL;
    this.cache = {
      menu: null,
      timestamp: null,
      cacheTime: 5 * 60 * 1000 // 5 минут
    };
  }

  // Проверка актуальности кэша
  isCacheValid() {
    if (!this.cache.menu || !this.cache.timestamp) {
      return false;
    }
    return Date.now() - this.cache.timestamp < this.cache.cacheTime;
  }

  // Получение полного меню с кэшированием
  async getMenuData() {
    if (this.isCacheValid()) {
      return this.cache.menu;
    }

    try {
      const response = await fetch(`${this.baseURL}${API_ENDPOINTS.menu}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      // Обновляем кэш
      this.cache.menu = data;
      this.cache.timestamp = Date.now();
      
      return data;
    } catch (error) {
      console.error('Error fetching menu data:', error);
      throw error;
    }
  }

  // Получение только категорий
  async getCategories() {
    const menuData = await this.getMenuData();
    return menuData.categories || [];
  }

  // Получение продуктов по ID категории
  async getProductsByCategory(categoryId) {
    const menuData = await this.getMenuData();
    const products = menuData.products || [];
    
    return products.filter(product => {
      // Проверяем, что продукт не скрыт
      if (product.hidden === "1") return false;
      
      // Проверяем видимость в точках продаж
      if (product.spots && Array.isArray(product.spots)) {
        const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
        if (!hasVisibleSpot) return false;
      }
      
      // Фильтруем по категории - используем menu_category_id
      return String(product.menu_category_id) === String(categoryId);
    });
  }

  // Получение всех продуктов
  async getAllProducts() {
    const menuData = await this.getMenuData();
    const products = menuData.products || [];
    
    return products.filter(product => {
      // Проверяем, что продукт не скрыт
      if (product.hidden === "1") return false;
      
      // Проверяем видимость в точках продаж
      if (product.spots && Array.isArray(product.spots)) {
        const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
        if (!hasVisibleSpot) return false;
      }
      
      return true;
    });
  }

  // Получение популярных продуктов
  async getPopularProducts(limit = 5) {
    try {
      const response = await fetch(`${this.baseURL}/api/menu/popular?limit=${limit}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      return data.products || [];
    } catch (error) {
      console.error('Error fetching popular products:', error);
      // Fallback: возвращаем первые 5 видимых продуктов
      const allProducts = await this.getAllProducts();
      return allProducts.slice(0, limit);
    }
  }

  // Получение популярных продуктов по категории
  async getPopularProductsByCategory(categoryId, limit = 5) {
    try {
      const response = await fetch(`${this.baseURL}/api/menu/categories/${categoryId}/popular?limit=${limit}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      return data.popular_products || [];
    } catch (error) {
      console.error('Error fetching popular products by category:', error);
      // Fallback: возвращаем первые 5 видимых продуктов из категории
      const categoryProducts = await this.getProductsByCategory(categoryId);
      return categoryProducts.slice(0, limit);
    }
  }

  // Нормализация цены (деление на 100)
  normalizePrice(price) {
    return price ? parseFloat(price) / 100 : 0;
  }

  // Форматирование цены для отображения
  formatPrice(price) {
    if (!price) return '0';
    return (price / 100).toFixed(0);
  }

  // Получение изображения продукта
  getProductImage(imageId, size = 'medium') {
    if (!imageId) return null;
    
    const sizes = {
      small: '100x100',
      medium: '300x300',
      large: '600x600'
    };
    
    return `https://joinposter.com/api/image?image_id=${imageId}&size=${sizes[size] || sizes.medium}`;
  }

  // Очистка кэша
  clearCache() {
    this.cache.menu = null;
    this.cache.timestamp = null;
  }
}

export default new MenuService();
