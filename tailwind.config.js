import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const withAlpha = (cssVar) => `rgb(var(${cssVar}) / <alpha-value>)`;

const semanticPalette = (token) => ({
    50:  withAlpha(`--color-${token}-50`),
    100: withAlpha(`--color-${token}-100`),
    200: withAlpha(`--color-${token}-200`),
    300: withAlpha(`--color-${token}-300`),
    400: withAlpha(`--color-${token}-400`),
    500: withAlpha(`--color-${token}-500`),
    600: withAlpha(`--color-${token}-600`),
    700: withAlpha(`--color-${token}-700`),
    800: withAlpha(`--color-${token}-800`),
    900: withAlpha(`--color-${token}-900`),
    950: withAlpha(`--color-${token}-950`),
});

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                primary: semanticPalette('primary'),
                accent:  semanticPalette('accent'),
                danger:  semanticPalette('danger'),
                info:    semanticPalette('info'),
            },
        },
    },

    plugins: [forms],
};
