import React from 'react';

const FastActionCard = ({ 
  icon, 
  title, 
  description, 
  onClick, 
  href, 
  className = '' 
}) => {
  const handleClick = () => {
    if (onClick) {
      onClick();
    }
  };

  const Component = href ? 'a' : 'button';
  const props = href ? { href, target: '_blank', rel: 'noopener noreferrer' } : {};

  return (
    <Component
      {...props}
      onClick={handleClick}
      className={`
        w-full bg-white rounded-xl shadow-lg hover:shadow-xl 
        transition-all duration-200 transform hover:scale-105 
        p-6 text-center group cursor-pointer
        ${className}
      `}
    >
      <div className="text-6xl mb-4 group-hover:scale-110 transition-transform duration-200">
        {icon}
      </div>
      <h3 className="text-xl font-semibold text-gray-900 mb-2">
        {title}
      </h3>
      <p className="text-gray-600 text-sm">
        {description}
      </p>
    </Component>
  );
};

export default FastActionCard;
