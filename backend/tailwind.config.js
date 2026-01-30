import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./packages/Admin/src/resources/views/**/*.blade.php",
        "./packages/Modules/**/src/Views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                // Ultra small
                xxxs: ["9px", "12px"], 
                xxs: ["10px", "14px"], 
                xs2: ["11px", "16px"],

                // Standard Polaris style
                'body-13': ["13px", "18px"],
                'body-14': ["14px", "20px"], 
                'body-15': ["15px", "22px"], 
                'body-16': ["16px", "24px"],

                // Titres plus marqués
                lg17: ["17px", "25px"],
                lg18: ["18px", "26px"],
                lg20: ["20px", "28px"],
                lg22: ["22px", "30px"],

                // Display
                display24: ["24px", "32px"],
                display30: ["30px", "38px"],
                display36: ["36px", "44px"],
            },
        },
    },

    plugins: [forms],
};
