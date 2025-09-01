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
