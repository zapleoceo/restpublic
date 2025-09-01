/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Полная палитра из шаблона Lounge
        primary: {
          50: '#f4f9f7',
          100: '#daede5',
          200: '#b5dacb',
          300: '#89bfac',
          400: '#65a48f',
          500: '#468672',
          600: '#366b5b',
          700: '#2e574a',
          800: '#29463f',
          900: '#253c35',
          950: '#11221d',
        },
        secondary: {
          50: '#f9f7f3',
          100: '#f2ede2',
          200: '#e3d8c5',
          300: '#d2be9f',
          400: '#c2a480',
          500: '#b1885e',
          600: '#a47652',
          700: '#885f46',
          800: '#6f4f3d',
          900: '#5a4134',
          950: '#30211a',
        },
        neutral: {
          50: '#efefef',
          100: '#dfe0e0',
          150: '#cfd0d0',
          200: '#bfc1c0',
          300: '#9fa1a1',
          400: '#7f8281',
          500: '#5f6362',
          600: '#4c4f4e',
          700: '#393b3b',
          800: '#262827',
          850: '#1c1e1d',
          900: '#131414',
          950: '#090a0a',
        }
      },
      fontFamily: {
        'sans': ['"Roboto Flex"', 'sans-serif'],
        'serif': ['"Playfair Display"', 'serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ],
}
