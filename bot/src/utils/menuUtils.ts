import { Category, Product } from '../services/posterService';

// Утилита для работы с меню в боте
export const groupProductsByCategory = (categories: Category[], products: Product[]) => {
  return categories.map(category => {
    const categoryProducts = products.filter(product => 
      product.menu_category_id === category.category_id
    );
    
    return {
      ...category,
      products: categoryProducts
    };
  }).filter(category => category.products.length > 0);
};

// Получение продуктов для конкретной категории
export const getProductsForCategory = (categoryId: string, products: Product[]) => {
  return products.filter(product => 
    product.menu_category_id === categoryId
  );
};

// Получение категории по ID
export const getCategoryById = (categoryId: string, categories: Category[]) => {
  return categories.find(category => category.category_id === categoryId);
};
