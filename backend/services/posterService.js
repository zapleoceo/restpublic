const axios = require('axios');

class PosterService {
  constructor() {
    this.baseURL = process.env.POSTER_API_BASE_URL || 'https://joinposter.com/api';
    this.token = process.env.POSTER_API_TOKEN;
    
    console.log('🔧 PosterService constructor - Environment variables:');
    console.log('  POSTER_API_TOKEN:', this.token ? '***configured***' : 'NOT CONFIGURED');
    console.log('  POSTER_API_URL:', this.baseURL || 'NOT CONFIGURED');
    console.log('  All env vars:', Object.keys(process.env).filter(key => key.includes('POSTER')));
    
    if (!this.token) {
      console.warn('⚠️ POSTER_API_TOKEN not configured');
    }
    
    // Axios instance with default config
    this.api = axios.create({
      baseURL: this.baseURL,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }

  // Helper method to make API requests
  async makeRequest(endpoint, params = {}) {
    try {
      if (!this.token) {
        throw new Error('Poster API token not configured');
      }

      const queryParams = new URLSearchParams({
        token: this.token,
        ...params
      });

      const url = `${endpoint}?${queryParams.toString()}`;
      console.log(`📡 Poster API Request: ${this.baseURL}/${endpoint}`);
      console.log(`🔗 Full URL: ${this.baseURL}/${url}`);
      console.log(`🔑 Token: ${this.token.substring(0, 10)}...`);

      const response = await this.api.get(url);
      
      if (response.data && response.data.response) {
        return response.data.response;
      }
      
      return response.data;
    } catch (error) {
      console.error(`❌ Poster API Error (${endpoint}):`, error.message);
      throw new Error(`Poster API request failed: ${error.message}`);
    }
  }

  // Get menu categories
  async getCategories() {
    console.log(`🔍 getCategories() called`);
    const allCategories = await this.makeRequest('menu.getCategories');
    console.log(`📥 Raw categories from Poster API:`, allCategories);
    
    // Filter only visible categories according to Poster API documentation
    const categories = allCategories.filter(category => {
      // Check if category is hidden
      if (category.category_hidden === "1") {
        return false;
      }
      
      // Check if category is visible in any spot
      if (category.visible && Array.isArray(category.visible)) {
        return category.visible.some(spot => spot.visible === "1" || spot.visible === 1);
      }
      
      // If no visibility info, assume visible
      return true;
    });
    
    console.log(`📋 Retrieved ${categories.length} visible categories (filtered from ${allCategories.length} total)`);
    console.log(`📋 Filtered categories:`, categories.map(c => ({ id: c.category_id, name: c.category_name })));
    return categories;
  }

  // Get all products
  async getProducts() {
    console.log(`🔍 getProducts() called`);
    const products = await this.makeRequest('menu.getProducts');
    console.log(`📥 Raw products from Poster API:`, products);
    console.log(`📋 Retrieved ${products.length} products`);
    console.log('Sample products:', products.slice(0, 3));
    return products;
  }

  // Get products by category
  async getProductsByCategory(categoryId) {
    const products = await this.getProducts();
    return products.filter(product => {
      // Check if product is not hidden
      if (product.hidden === "1") return false;
      
      // Check visibility in spots according to Poster API documentation
      if (product.spots && Array.isArray(product.spots)) {
        const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
        if (!hasVisibleSpot) return false;
      }
      
      // Check if product belongs to the specified category
      return product.menu_category_id === categoryId;
    });
  }

  // Get popular products by category (using sales data)
  async getPopularProductsByCategory(categoryId, limit = 5) {
    try {
      // Get sales data for the last 30 days
      const dateTo = new Date();
      const dateFrom = new Date();
      dateFrom.setDate(dateFrom.getDate() - 30);
      
      // Format dates as YYYYMMDD according to Poster API documentation
      const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}${month}${day}`;
      };
      
      const salesData = await this.makeRequest('dash.getProductsSales', {
        date_from: formatDate(dateFrom),
        date_to: formatDate(dateTo)
      });
      
      // Get products from specific category
      const categoryProducts = await this.getProductsByCategory(categoryId);
      
      // Create a map of product sales
      const productSales = {};
      if (salesData && Array.isArray(salesData)) {
        salesData.forEach(sale => {
          if (sale.product_id && sale.count) {
            productSales[sale.product_id] = (productSales[sale.product_id] || 0) + parseInt(sale.count);
          }
        });
      }
      
      // Sort products by sales and filter visible ones
      const sortedProducts = (Array.isArray(categoryProducts) ? categoryProducts : [])
        .filter(product => {
          if (product.hidden === "1") return false;
          if (product.spots && Array.isArray(product.spots)) {
            const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
            if (!hasVisibleSpot) return false;
          }
          return true;
        })
        .map(product => {
          // Add sales_count to each product
          const salesCount = productSales[product.product_id] || 0;
          return {
            ...product,
            sales_count: salesCount
          };
        })
        .sort((a, b) => {
          const salesA = a.sales_count || 0;
          const salesB = b.sales_count || 0;
          return salesB - salesA;
        })
        .slice(0, limit);
      
      console.log(`📋 Retrieved ${sortedProducts.length} popular products for category ${categoryId}`);
      return sortedProducts;
    } catch (error) {
      console.error('Error getting popular products by category:', error);
      // Fallback: return first 5 visible products from category
      const categoryProducts = await this.getProductsByCategory(categoryId);
      const fallbackProducts = (Array.isArray(categoryProducts) ? categoryProducts : [])
        .filter(product => {
          if (product.hidden === "1") return false;
          if (product.spots && Array.isArray(product.spots)) {
            const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
            if (!hasVisibleSpot) return false;
          }
          return true;
        })
        .map(product => ({
          ...product,
          sales_count: 0 // No sales data available
        }))
        .slice(0, limit);
      
      console.log(`📋 Fallback: Retrieved ${fallbackProducts.length} products for category ${categoryId}`);
      return fallbackProducts;
    }
  }

  // Normalize price (divide by 100 to convert from minor units)
  normalizePrice(price) {
    if (!price) return 0;
    
    // Handle price object with spot keys
    if (typeof price === 'object' && price !== null) {
      // Get first available price
      const firstPrice = Object.values(price)[0];
      return firstPrice ? parseFloat(firstPrice) / 100 : 0;
    }
    
    // Handle string/number price
    return parseFloat(price) / 100;
  }

  // Format price for display
  formatPrice(price) {
    const normalizedPrice = this.normalizePrice(price);
    return normalizedPrice.toFixed(2);
  }

  // Get product image URL
  getProductImage(imageId, size = 'medium') {
    if (!imageId) return null;
    
    const sizes = {
      small: '100x100',
      medium: '300x300',
      large: '600x600'
    };
    
    return `https://joinposter.com/api/image?image_id=${imageId}&size=${sizes[size] || sizes.medium}`;
  }

  // Get tables list
  async getTables() {
    console.log(`🔍 getTables() called`);
    try {
      const tables = await this.makeRequest('spots.getTableHallTables');
      console.log(`📥 Raw tables from Poster API:`, tables);
      console.log(`📋 Retrieved ${tables.length} tables`);
      return tables;
    } catch (error) {
      console.error('Error getting tables:', error);
      // Return empty array if tables API is not available
      return [];
    }
  }

  // Create incoming order
  async createIncomingOrder(orderData) {
    console.log(`🔍 createIncomingOrder() called with data:`, orderData);
    
    try {
      if (!this.token) {
        throw new Error('Poster API token not configured');
      }

      const url = `${this.baseURL}/incomingOrders.createIncomingOrder?token=${this.token}`;
      
      // Валидация обязательных полей
      if (!orderData.spot_id) {
        throw new Error('spot_id is required');
      }
      if (!orderData.phone && !orderData.client_id) {
        throw new Error('phone or client_id is required');
      }
      if (!orderData.products || orderData.products.length === 0) {
        throw new Error('products array is required');
      }

      // Process order data - prices should already be in minor units from frontend
      const processedOrderData = {
        spot_id: parseInt(orderData.spot_id),
        phone: orderData.phone,
        service_mode: orderData.service_mode || 1, // 1 - в заведении, 2 - навынос, 3 - доставка
        products: orderData.products.map(product => ({
          product_id: parseInt(product.product_id),
          count: parseInt(product.count),
          price: Math.round(product.price) // Ensure price is integer
        }))
      };

      // Добавляем опциональные поля если они есть
      if (orderData.comment) {
        processedOrderData.comment = orderData.comment;
      }
      if (orderData.client_id) {
        processedOrderData.client_id = parseInt(orderData.client_id);
      }
      
      // Для заказов на вынос/доставку добавляем адрес
      if (orderData.service_mode === 2 || orderData.service_mode === 3) {
        if (orderData.client_address) {
          processedOrderData.client_address = orderData.client_address;
        }
      }

      console.log(`📡 Poster API Request: ${url}`);
      console.log(`📦 Order data:`, processedOrderData);

      const response = await this.api.post(url, processedOrderData, {
        headers: {
          'Content-Type': 'application/json'
        }
      });
      
      console.log(`📥 Poster API Response:`, response.data);
      
      // Проверяем, есть ли ошибка в ответе Poster API
      if (response.data.error) {
        console.error(`❌ Poster API returned error:`, response.data.error);
        throw new Error(`Poster API error: ${response.data.error.message || 'Unknown error'}`);
      }
      
      console.log(`✅ Order created successfully:`, response.data);
      return response.data;
    } catch (error) {
      console.error(`❌ Poster API Error (createIncomingOrder):`, error.message);
      throw new Error(`Failed to create order: ${error.message}`);
    }
  }
}

module.exports = new PosterService();
