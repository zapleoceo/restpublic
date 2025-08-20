// Утилита для работы с меню в соответствии с API Poster
export const groupProductsByCategory = (categories = [], products = []) => {
  console.log('🔍 groupProductsByCategory input:', { categories: categories.length, products: products.length });
  
  const result = categories.map(category => {
    const categoryProducts = products.filter(product => 
      product && product.menu_category_id === category.category_id
    ) || [];
    
    console.log(`🔍 Category ${category.category_name}: ${categoryProducts.length} products`);
    
    return {
      ...category,
      products: categoryProducts
    };
  }).filter(category => category && category.products && category.products.length > 0);
  
  console.log('🔍 groupProductsByCategory result:', result.length, 'categories with products');
  return result;
};

// Получение продуктов для конкретной категории
export const getProductsForCategory = (categoryId, products = []) => {
  return products.filter(product => 
    product && product.menu_category_id === categoryId
  );
};

// Получение категории по ID
export const getCategoryById = (categoryId, categories = []) => {
  return categories.find(category => category.category_id === categoryId);
};

// Фильтрация видимых продуктов (hidden !== "1")
export const filterVisibleProducts = (products = []) => {
  return products.filter(product => product.hidden !== "1");
};

// Получение первых N категорий с продуктами
export const getFeaturedCategories = (categories = [], products = [], limit = 2) => {
  const groupedCategories = groupProductsByCategory(categories, products);
  return groupedCategories.slice(0, limit);
};
