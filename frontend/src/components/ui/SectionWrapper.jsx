export const SectionWrapper = ({ 
  id, 
  className = '', 
  children 
}) => (
  <section 
    id={id} 
    className={`container mx-auto px-4 py-16 target-section ${className}`}
  >
    {children}
  </section>
);
