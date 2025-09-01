import React from 'react';

const Grid = ({ 
  children, 
  cols = 1, 
  mdCols = 2, 
  lgCols = 4, 
  gap = 4, 
  className = '' 
}) => {
  const gridClasses = `grid grid-cols-${cols} md:grid-cols-${mdCols} lg:grid-cols-${lgCols} gap-${gap} ${className}`;
  
  return (
    <div className={gridClasses}>
      {children}
    </div>
  );
};

export default Grid;
