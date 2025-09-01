import { DESIGN_TOKENS } from '../constants/designTokens';

export const getColorClass = (color, shade = 500) => {
  return `text-${color}-${shade}`;
};

export const getSpacingClass = (size) => {
  const spacingMap = {
    xs: 'space-y-1',
    sm: 'space-y-2', 
    md: 'space-y-4',
    lg: 'space-y-6',
    xl: 'space-y-8'
  };
  return spacingMap[size] || spacingMap.md;
};

export const getResponsiveClass = (base, responsive) => {
  return `${base} ${Object.entries(responsive)
    .map(([breakpoint, value]) => `${breakpoint}:${value}`)
    .join(' ')}`;
};
