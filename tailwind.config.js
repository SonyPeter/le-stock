/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./page/**/*.php",
    "./includes/**/*.php",
    "./controller/**/*.php",
    "./src/**/*.css",
    //"./models/**/*.php",
    //"."
  ],
  theme: {
    extend: {
      colors: {
        // Vos couleurs personnalisées (optionnel)
        brand: '#3B82F6',
      }
    },
  },
  plugins: [],
}

