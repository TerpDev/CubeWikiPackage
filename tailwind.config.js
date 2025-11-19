/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './src/**/*.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {},
    },

    // Disable Tailwind's preflight (base/reset) to avoid clobbering Filament's own base styles
    corePlugins: {
        preflight: false,
    },

    safelist: [
        'border-l-3',
        'prose',
        'prose-invert',
        'dark:prose-invert',
        'max-w-none',
        'wk-doc',
    ],

    plugins: [
        // require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
