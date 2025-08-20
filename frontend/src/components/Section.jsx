import React from 'react';

const Section = ({ 
  children, 
  className = '', 
  container = true,
  maxWidth = '4xl'
}) => {
  const sectionClasses = `py-12 ${className}`;
  const containerClasses = `max-w-${maxWidth} mx-auto px-4`;
  
  if (container) {
    return (
      <section className={sectionClasses}>
        <div className={containerClasses}>
          {children}
        </div>
      </section>
    );
  }
  
  return (
    <section className={sectionClasses}>
      {children}
    </section>
  );
};

export default Section;
