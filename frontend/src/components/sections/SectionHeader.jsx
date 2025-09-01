export const SectionHeader = ({ number, title, className = '' }) => {
  return (
    <div className={`text-center mb-12 ${className}`}>
      <div className="inline-flex items-center justify-center w-12 h-12 bg-primary-100 text-primary-600 rounded-full text-lg font-semibold mb-4">
        {number}
      </div>
      <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        {title}
      </h2>
    </div>
  );
};
