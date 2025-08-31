export const SectionHeader = ({ 
  number, 
  title, 
  className = '' 
}) => (
  <div className={`section-header mb-8 ${className}`} data-num={number}>
    <h2 className="text-3xl md:text-4xl font-serif font-bold text-primary-900 mb-2">
      {title}
    </h2>
    {number && (
      <div className="text-sm text-primary-500 font-medium">
        {number.toString().padStart(2, '0')}
      </div>
    )}
  </div>
);
