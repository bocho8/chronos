/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{php,html,js}",
    "./public/**/*.{php,html,js}",
  ],
  theme: {
    extend: {
      colors: {
        navy: '#1f366d',
        bg: '#f5f6f8',
        card: '#d9d9d9',
        muted: '#475569',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
      },
    }
  },
  plugins: [],
}
