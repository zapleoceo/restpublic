const axios = require('axios');

class PosterService {
  constructor() {
    this.baseURL = process.env.POSTER_API_URL || 'https://joinposter.com/api/v3';
    this.token = process.env.POSTER_API_TOKEN;
    
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
      console.log(`📡 Poster API Request: ${endpoint}`);

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
    const allCategories = await this.makeRequest('menu.getCategories');
    
    // Filter only visible categories
    const categories = allCategories.filter(category => {
      return category.category_hidden !== "1";
    });
    
    console.log(`📋 Retrieved ${categories.length} visible categories (filtered from ${allCategories.length} total)`);
    return categories;
  }

  // Get all products
  async getProducts() {
    const products = await this.makeRequest('menu.getProducts');
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
      
      // Check visibility in spots
      if (product.spots && Array.isArray(product.spots)) {
        const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
        if (!hasVisibleSpot) return false;
      }
      
      // Filter by category - use menu_category_id
      return String(product.menu_category_id) === String(categoryId);
    });
  }

  // Get popular products (top 5 by sales)
  async getPopularProducts(limit = 5) {
    try {
      // Get sales data for the last 30 days
      const today = new Date();
      const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
      
      const dateFrom = thirtyDaysAgo.toISOString().slice(0, 10).replace(/-/g, '');
      const dateTo = today.toISOString().slice(0, 10).replace(/-/g, '');
      
      const salesData = await this.makeRequest('dash.getProductsSales', {
        date_from: dateFrom,
        date_to: dateTo
      });
      
      // Get all products to match with sales data
      const allProducts = await this.getProducts();
      
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
      const sortedProducts = (Array.isArray(allProducts) ? allProducts : [])
        .filter(product => {
          if (product.hidden === "1") return false;
          if (product.spots && Array.isArray(product.spots)) {
            const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
            if (!hasVisibleSpot) return false;
          }
          return true;
        })
        .sort((a, b) => {
          const salesA = productSales[a.product_id] || 0;
          const salesB = productSales[b.product_id] || 0;
          return salesB - salesA;
        })
        .slice(0, limit);
      
      console.log(`📋 Retrieved ${sortedProducts.length} popular products`);
      return sortedProducts;
    } catch (error) {
      console.error('Error getting popular products:', error);
      // Fallback: return first 5 visible products
      const allProducts = await this.getProducts();
      const fallbackProducts = (Array.isArray(allProducts) ? allProducts : [])
        .filter(product => {
          if (product.hidden === "1") return false;
          if (product.spots && Array.isArray(product.spots)) {
            const hasVisibleSpot = product.spots.some(spot => spot.visible !== "0");
            if (!hasVisibleSpot) return false;
          }
          return true;
        })
        .slice(0, limit);
      
      console.log(`📋 Fallback: Retrieved ${fallbackProducts.length} products`);
      return fallbackProducts;
    }
  }

  // Get popular products by category
  async getPopularProductsByCategory(categoryId, limit = 5) {
    try {
      // Get sales data for the last 30 days
      const dateTo = new Date();
      const dateFrom = new Date();
      dateFrom.setDate(dateFrom.getDate() - 30);
      
      const salesData = await this.makeRequest('dash.getProductsSales', {
        date_from: dateFrom,
        date_to: dateTo
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
        .sort((a, b) => {
          const salesA = productSales[a.product_id] || 0;
          const salesB = productSales[b.product_id] || 0;
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


}

module.exports = new PosterService();
