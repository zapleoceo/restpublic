import React from 'react';
import { getImageUrl, isImageAvailable } from '../utils/imageUtils';

const ProductImage = ({ photo, alt = 'Товар', className = '' }) => {
  const imageUrl = getImageUrl(photo);
  const hasImage = isImageAvailable(photo);

  return (
    <div className={`aspect-square bg-gray-100 overflow-hidden ${className}`}>
      {hasImage ? (
        <img
          src={imageUrl}
          alt={alt}
          className="w-full h-full object-cover"
          onError={(e) => {
            e.target.style.display = 'none';
            e.target.nextSibling.style.display = 'flex';
          }}
        />
      ) : null}
      <div 
        className="w-full h-full flex items-center justify-center text-gray-400"
        style={{ display: hasImage ? 'none' : 'flex' }}
      >
        <span className="text-4xl">🍽️</span>
      </div>
    </div>
  );
};

export default ProductImage;
