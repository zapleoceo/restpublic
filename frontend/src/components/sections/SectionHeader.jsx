export const SectionHeader = ({ 
  number, 
  title, 
  className = '' 
}) => (
  <div className={`section-header ${className}`} data-num={number}>
    <h2 className="text-display-title">{title}</h2>
  </div>
);
