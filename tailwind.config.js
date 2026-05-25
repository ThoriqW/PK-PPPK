/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#0b5394',
                    50:  '#e8f0fa',
                    100: '#c5d8f0',
                    500: '#1a73c4',
                    600: '#0b5394',
                    700: '#083f70',
                    900: '#04223d',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
