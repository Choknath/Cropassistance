/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/views/**/*.php",
    "./public/**/*.html",
  ],
  safelist: [
    // Green shades
    'bg-green-50','bg-green-100','bg-green-600','bg-green-700','bg-green-800','bg-green-900',
    'text-green-200','text-green-300','text-green-600','text-green-700','text-green-800','text-green-900',
    'border-green-100','border-green-200',
    // Yellow/soil shades
    'bg-yellow-50','bg-yellow-100','bg-yellow-200',
    'text-yellow-700','text-yellow-800',
    'border-yellow-100','border-yellow-200',
    // Red shades
    'bg-red-50','text-red-700','border-red-200',
    // Status colors
    'bg-blue-50','text-blue-800','border-blue-200',
    // Animations
    'animate-pulse','animate-bounce',
    // Gradient
    'from-green-700','to-green-600','bg-gradient-to-r',
  ],
  theme: {
    extend: {
      colors: {
        forest: {
          50:  '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16',
        },
        soil: {
          100: '#fdf8f0',
          200: '#f5e6c8',
          300: '#e8c98a',
          400: '#d4a853',
        }
      },
      fontFamily: {
        display: ['"Playfair Display"', 'Georgia', 'serif'],
        body:    ['"DM Sans"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}