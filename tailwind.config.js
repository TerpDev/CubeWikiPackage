// tailwind.config.js of the Filament app
module.exports = {
    content: [
        // Local package views and JS
        "./resources/views/**/*.blade.php",
        "./resources/**/*.js",
        "./src/**/*.php",

        // When installed as a dependency in another app, also scan the vendor path
        "./vendor/terpdev/cubewikipackage/resources/views/**/*.blade.php",
        "./vendor/terpdev/cubewikipackage/src/**/*.php",

        // Filament core
        "./vendor/filament/**/*.blade.php",
    ],
    theme: {
        extend: {},
    },
    safelist: [
        "border-l-3",
    ],
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
