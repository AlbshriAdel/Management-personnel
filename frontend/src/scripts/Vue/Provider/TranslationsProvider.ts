import {createI18n, I18n}  from "vue-i18n";
import EnvReader           from "@/scripts/Core/System/EnvReader";
import LocalStorageService from "@/scripts/Core/Services/Storage/LocalStorageService";

const LOCALE_STORAGE_KEY = 'pms_locale';
const SUPPORTED_LOCALES = ['en-US', 'ar-SA'] as const;

/**
 * @description will handle the translations loading / providing
 */
export default class TranslationsProvider
{
    /**
     * @description fallback message in case of something being wrong with translations handling
     */
    static readonly FALLBACK_SAFETY_MESSAGE = "Internal server error";

    /**
     * @description Get the initial locale from localStorage or env default
     */
    public static getInitialLocale(): string {
        try {
            const stored = LocalStorageService.get(LOCALE_STORAGE_KEY);
            if (stored && SUPPORTED_LOCALES.includes(stored as typeof SUPPORTED_LOCALES[number])) {
                return stored;
            }
        } catch {
            // ignore
        }
        return EnvReader.getAppDefaultLanguage();
    }

    /**
     * @description Set and persist the user's locale preference
     */
    public static setLocale(locale: string): void {
        if (SUPPORTED_LOCALES.includes(locale as typeof SUPPORTED_LOCALES[number])) {
            LocalStorageService.set(LOCALE_STORAGE_KEY, locale);
        }
    }

    /**
     * @description will build and return the VueI18n instance which is then being mounted into the vue
     *              to use the translations globally
     */
    public buildVueI18nInstance(): Promise<I18n<Record<string, unknown>>>
    {
        const defaultLocale = EnvReader.getAppDefaultLanguage();
        const initialLocale = TranslationsProvider.getInitialLocale();

        const vueI18n = createI18n({
            legacy: true,
            locale: initialLocale,
            fallbackLocale: defaultLocale,
            messages: {},
        });

        return this.loadAllTranslations(vueI18n);
    }

    /**
     * @description Load translations for all locales (en-US, ar-SA) and merge by locale
     */
    private async loadAllTranslations(vueI18n: I18n<Record<string, unknown>>): Promise<I18n<Record<string, unknown>>>
    {
        const modules = import.meta.globEager('/src/translations/**/**/**/**/**/**/*.json');
        const fileNames = Object.keys(modules);

        for (const fileName of fileNames) {
            const locale = fileName.includes('/ar-SA/') ? 'ar-SA' : 'en-US';
            const module = modules[fileName];
            vueI18n.global.mergeLocaleMessage(locale, module.default);
        }

        return vueI18n;
    }

}