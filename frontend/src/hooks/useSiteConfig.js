import { useState, useEffect } from 'react';

export const useSiteConfig = () => {
  const [config, setConfig] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadSiteConfig();
  }, []);

  const loadSiteConfig = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch('/api/config/site-config');
      
      if (response.ok) {
        const data = await response.json();
        setConfig(data);
        console.log('✅ Конфигурация сайта загружена из API');
      } else {
        throw new Error('Ошибка загрузки конфигурации сайта');
      }
    } catch (err) {
      console.error('❌ Ошибка загрузки конфигурации:', err);
      setError(err.message);
      
      // Fallback на статическую конфигурацию
      setConfig({
        SITE_NAME: {
          ru: 'Республика Север',
          en: 'North Republic',
          vi: 'Cộng hòa Bắc'
        },
        SITE_DESCRIPTION: {
          ru: 'Развлекательный комплекс',
          en: 'Entertainment Complex',
          vi: 'Khu giải trí'
        }
      });
    } finally {
      setLoading(false);
    }
  };

  const getSiteName = (language = 'ru') => {
    return config?.SITE_NAME?.[language] || 'Республика Север';
  };

  const getSiteDescription = (language = 'ru') => {
    return config?.SITE_DESCRIPTION?.[language] || 'Развлекательный комплекс';
  };

  return {
    config,
    loading,
    error,
    getSiteName,
    getSiteDescription,
    reload: loadSiteConfig
  };
};

export default useSiteConfig;
