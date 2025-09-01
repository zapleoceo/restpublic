import { DESIGN_TOKENS } from '../constants/designTokens';

export const getColorClass = (color, shade = 500) => {
  return `text-${color}-${shade}`;
};

export const getSpacingClass = (size) => {
  return `p-${size}`;
};

export const getResponsiveClass = (base, responsive) => {
  return `${base} ${Object.entries(responsive)
    .map(([breakpoint, value]) => `${breakpoint}:${value}`)
    .join(' ')}`;
};

export const cn = (...classes) => {
  return classes.filter(Boolean).join(' ');
};
