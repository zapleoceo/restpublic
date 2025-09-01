import React, { useEffect } from 'react';

export const Preloader = ({ onLoaded }) => {
  useEffect(() => {
    // Добавляем класс для preloader
    document.documentElement.classList.add('ss-preload');
    document.documentElement.classList.remove('no-js');
    document.documentElement.classList.add('js');

    const handleLoad = () => {
      setTimeout(() => {
        document.documentElement.classList.remove('ss-preload');
        document.documentElement.classList.add('ss-loaded');
        
        setTimeout(() => {
          document.body.classList.add('ss-show');
          if (onLoaded) onLoaded();
        }, 600);
      }, 300);
    };

    if (document.readyState === 'complete') {
      handleLoad();
    } else {
      window.addEventListener('load', handleLoad);
    }

    return () => {
      window.removeEventListener('load', handleLoad);
    };
  }, [onLoaded]);

  return (
    <div id="preloader">
      <div id="loader" className="dots-fade">
        <div></div>
        <div></div>
        <div></div>
      </div>
    </div>
  );
};
