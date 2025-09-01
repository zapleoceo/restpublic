import React from 'react';

// Компонент-обертка для секций согласно шаблону Lounge
export const SectionWrapper = ({ 
  id, 
  className = '', 
  children 
}) => (
  <section 
    id={id} 
    className={`container target-section ${className}`}
  >
    {children}
  </section>
);