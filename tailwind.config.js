/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.blade.php",
        "./resources/**/*.js",
        "./src/**/*.php",
    ],
    theme: {
        extend: {},
    },
    safelist: [
        // classes you generate dynamically or mentioned only in PHP
        "border-l-3",
    ],
};

