import { createI18n } from 'vue-i18n';

import en from './locales/en.json';
import ru from './locales/ru.json';

const savedLocale = localStorage.getItem('trade-core:locale');

const locale = savedLocale === 'en' || savedLocale === 'ru' ? savedLocale : 'ru';

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale,
    fallbackLocale: 'en',
    messages: {
        en,
        ru,
    },
});

export default i18n;
