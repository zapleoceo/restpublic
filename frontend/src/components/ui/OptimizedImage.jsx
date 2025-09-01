import React, { useState, useRef, useEffect } from 'react';

export const OptimizedImage = ({ 
  src, 
  alt, 
  className = '', 
  placeholder = '/img/placeholder.jpg',
  webp = true,
  sizes = '100vw',
  priority = false,
  ...props 
}) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [isInView, setIsInView] = useState(priority);
  const imgRef = useRef(null);

  useEffect(() => {
    if (priority) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsInView(true);
          observer.disconnect();
        }
      },
      {
        rootMargin: '50px 0px',
        threshold: 0.01
      }
    );

    if (imgRef.current) {
      observer.observe(imgRef.current);
    }

    return () => observer.disconnect();
  }, [priority]);

  const handleLoad = () => {
    setIsLoaded(true);
  };

  const handleError = () => {
    setHasError(true);
    setIsLoaded(true);
  };

  const getImageSrc = () => {
    if (hasError) return placeholder;
    if (!isInView) return placeholder;
    return src;
  };

  const getWebpSrc = () => {
    if (!webp || hasError || !isInView) return null;
    
    // Простая логика для WebP - можно расширить
    if (src.includes('.jpg') || src.includes('.jpeg')) {
      return src.replace(/\.(jpg|jpeg)$/i, '.webp');
    }
    if (src.includes('.png')) {
      return src.replace(/\.png$/i, '.webp');
    }
    return null;
  };

  return (
    <div 
      ref={imgRef}
      className={`optimized-image ${className}`}
      style={{ position: 'relative', overflow: 'hidden' }}
    >
      {/* Placeholder */}
      {!isLoaded && (
        <div 
          className="absolute inset-0 bg-neutral-200 animate-pulse"
          style={{ 
            backgroundImage: `url(${placeholder})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center'
          }}
        />
      )}

      {/* WebP изображение */}
      {getWebpSrc() && (
        <picture>
          <source 
            srcSet={getWebpSrc()} 
            type="image/webp" 
            sizes={sizes}
          />
          <img
            src={getImageSrc()}
            alt={alt}
            className={`w-full h-full object-cover transition-opacity duration-300 ${
              isLoaded ? 'opacity-100' : 'opacity-0'
            }`}
            onLoad={handleLoad}
            onError={handleError}
            loading={priority ? 'eager' : 'lazy'}
            {...props}
          />
        </picture>
      )}

      {/* Обычное изображение */}
      {!getWebpSrc() && (
        <img
          src={getImageSrc()}
          alt={alt}
          className={`w-full h-full object-cover transition-opacity duration-300 ${
            isLoaded ? 'opacity-100' : 'opacity-0'
          }`}
          onLoad={handleLoad}
          onError={handleError}
          loading={priority ? 'eager' : 'lazy'}
          {...props}
        />
      )}
    </div>
  );
};
