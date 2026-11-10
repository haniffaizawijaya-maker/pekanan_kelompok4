import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: '#191919',
                cream: '#FBF3DC',
                parchment: '#F3EAD3',
                beige: '#E9DFC9',
                mustard: '#F2CE1B',
                accent: '#E8622C',
                accentdark: '#C94E1F',
                muted: '#6B6257',
                card: '#FFFDF5',
                errordark: '#A63A1A',
            },
            // Satu-satunya keluarga font seluruh UI: Courier Prime
            // (monospace gaya mesin tik, sesuai gambar referensi).
            // Fallback tetap monospace agar bila font gagal muat
            // tampilannya tetap mesin tik, bukan sans/serif.
            fontFamily: {
                sans: ['"Courier Prime"', 'ui-monospace', '"Courier New"', 'monospace'],
                serif: ['"Courier Prime"', 'ui-monospace', '"Courier New"', 'monospace'],
                mono: ['"Courier Prime"', 'ui-monospace', '"Courier New"', 'monospace'],
            },
        },
    },

    plugins: [forms],
};
