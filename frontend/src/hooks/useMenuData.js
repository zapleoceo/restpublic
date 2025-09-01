import { useState, useEffect } from 'react';
import { menuService } from '../services/menuService';

export const useMenuData = () => {
  const [menuData, setMenuData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchMenuData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('🔄 Fetching menu data...');
      const data = await menuService.getMenuData();
      console.log('✅ Menu data loaded:', { categories: data.categories.length, products: data.products.length });
      
      setMenuData(data);
    } catch (err) {
      console.error('❌ Error fetching menu data:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMenuData();
  }, []);

  return {
    menuData,
    loading,
    error,
    refetch: fetchMenuData
  };
};
