/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./pages/**/*.php",
    "./includes/**/*.php",
    "./controller/**/*.php",
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

