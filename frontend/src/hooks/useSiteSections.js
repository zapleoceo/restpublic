import { useState, useEffect } from 'react';

export const useSiteSections = () => {
  const [sections, setSections] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadSections();
  }, []);

  const loadSections = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch('/api/config/sections');
      
      if (response.ok) {
        const data = await response.json();
        setSections(data);
        console.log('✅ Конфигурация секций загружена из API');
      } else {
        throw new Error('Ошибка загрузки конфигурации секций');
      }
    } catch (err) {
      console.error('❌ Ошибка загрузки секций:', err);
      setError(err.message);
      
      // Fallback на статическую конфигурацию
      setSections({
        menu: {
          id: 'menu',
          icon: '/img/menu/icon.png',
          logo: '/img/menu/big.jpg',
          link: '/menu',
          enabled: true
        },
        lasertag: {
          id: 'lasertag',
          icon: '/img/lazertag/icon.png',
          logo: '/img/lazertag/logo.png',
          link: '/lasertag',
          enabled: true
        },
        bow: {
          id: 'bow',
          icon: '/img/archery/icon.png',
          logo: '/img/archery/logo.png',
          link: '/archerytag',
          enabled: true
        },
        cinema: {
          id: 'cinema',
          icon: '/img/cinema/icon.png',
          logo: '/img/cinema/big.jpg',
          link: '/cinema',
          enabled: true
        },
        rent: {
          id: 'rent',
          icon: '/img/bbq/icon.png',
          logo: '/img/bbq/buttton.png',
          link: '/bbq_zone',
          enabled: true
        },
        quests: {
          id: 'quests',
          icon: '/img/quests/icon.png',
          logo: '/img/quests/big.jpg',
          link: '/quests',
          enabled: true
        },
        guitar: {
          id: 'guitar',
          icon: '/img/guitar/icon.png',
          logo: '/img/guitar/button.jpg',
          link: '/guitar',
          enabled: true
        },
        boardgames: {
          id: 'boardgames',
          icon: '/img/boardgames/icon.png',
          logo: '/img/boardgames/button.jpg',
          link: '/boardgames',
          enabled: true
        },
        yoga: {
          id: 'yoga',
          icon: '/img/yoga/icon.png',
          logo: '/img/yoga/button.jpg?v=1',
          link: '/yoga',
          enabled: true
        }
      });
    } finally {
      setLoading(false);
    }
  };

  const getEnabledSections = () => {
    if (!sections) return {};
    
    const enabled = {};
    Object.entries(sections).forEach(([key, section]) => {
      if (section.enabled) {
        enabled[key] = section;
      }
    });
    return enabled;
  };

  const isSectionEnabled = (sectionId) => {
    return sections?.[sectionId]?.enabled || false;
  };

  return {
    sections,
    loading,
    error,
    getEnabledSections,
    isSectionEnabled,
    reload: loadSections
  };
};

export default useSiteSections;
