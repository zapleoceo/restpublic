/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Цвета из шаблона Lounge
        primary: {
          50: '#f4f9f7',
          100: '#daede5',
          500: '#468672',
          600: '#366b5b',
          900: '#253c35',
        },
        secondary: {
          50: '#f9f7f3',
          100: '#f2ede2',
          500: '#b1885e',
          600: '#a47652',
          900: '#5a4134',
        },
        neutral: {
          50: '#efefef',
          500: '#5f6362',
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
