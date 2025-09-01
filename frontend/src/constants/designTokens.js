// 🎨 Система дизайн-токенов из шаблона Lounge
export const DESIGN_TOKENS = {
  colors: {
    primary: {
      50: '#f4f9f7',
      100: '#daede5',
      200: '#b5dbc9',
      300: '#8ac8ad',
      400: '#5fb591',
      500: '#468672',
      600: '#366b5b',
      700: '#2a5244',
      800: '#1e3a2d',
      900: '#253c35',
    },
    secondary: {
      50: '#f9f7f3',
      100: '#f2ede2',
      200: '#e6dbc5',
      300: '#d9c9a8',
      400: '#cdb78b',
      500: '#b1885e',
      600: '#a47652',
      700: '#876246',
      800: '#6a4e3a',
      900: '#5a4134',
    },
    neutral: {
      50: '#efefef',
      100: '#e0e0e0',
      200: '#c2c2c2',
      300: '#a3a3a3',
      400: '#858585',
      500: '#5f6362',
      600: '#4c4f4e',
      700: '#393b3a',
      800: '#262726',
      900: '#131414',
      950: '#090a0a',
    }
  },
  typography: {
    fonts: {
      sans: '"Roboto Flex", sans-serif',
      serif: '"Playfair Display", serif',
      mono: 'Consolas, monospace',
    },
    sizes: {
      xs: '0.75rem',      // 12px
      sm: '0.875rem',     // 14px
      base: '1rem',       // 16px
      lg: '1.125rem',     // 18px
      xl: '1.25rem',      // 20px
      '2xl': '1.5rem',    // 24px
      '3xl': '1.875rem',  // 30px
      '4xl': '2.25rem',   // 36px
      '5xl': '3rem',      // 48px
      '6xl': '3.75rem',   // 60px
    }
  },
  spacing: {
    xs: '0.25rem',   // 4px
    sm: '0.5rem',    // 8px
    md: '1rem',      // 16px
    lg: '1.5rem',    // 24px
    xl: '2rem',      // 32px
    '2xl': '3rem',   // 48px
    '3xl': '4rem',   // 64px
    '4xl': '5rem',   // 80px
    '5xl': '6rem',   // 96px
  },
  breakpoints: {
    sm: '640px',
    md: '768px',
    lg: '1024px',
    xl: '1280px',
    '2xl': '1536px',
  }
};

// Утилиты для работы с токенами
export const getColor = (color, shade = 500) => DESIGN_TOKENS.colors[color]?.[shade];
export const getFont = (type) => DESIGN_TOKENS.typography.fonts[type];
export const getSize = (size) => DESIGN_TOKENS.typography.sizes[size];
export const getSpacing = (size) => DESIGN_TOKENS.spacing[size];