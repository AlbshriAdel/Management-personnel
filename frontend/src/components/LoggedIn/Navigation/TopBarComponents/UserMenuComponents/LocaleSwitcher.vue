<template>
  <li class="flex items-center justify-between gap-2 px-4 py-2 border-b dark:border-gray-700 list-none">
    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $t('locale.label') }}:</span>
    <div class="flex gap-1">
      <button v-for="loc in locales"
              :key="loc.value"
              :class="[
                'px-2 py-1 text-sm rounded',
                currentLocale === loc.value
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]"
              @click="setLocale(loc.value)"
      >
        {{ loc.label }}
      </button>
    </div>
  </li>
</template>

<script lang="ts">
import TranslationsProvider from "@/scripts/Vue/Provider/TranslationsProvider";

export default {
  data() {
    return {
      locales: [
        { value: 'en-US', label: 'English' },
        { value: 'ar-SA', label: 'العربية' },
      ],
    };
  },
  computed: {
    currentLocale(): string {
      return this.$i18n?.locale ?? 'en-US';
    },
  },
  mounted() {
    this.updateDocumentDirection();
  },
  methods: {
    setLocale(locale: string): void {
      TranslationsProvider.setLocale(locale);
      this.$i18n.locale = locale;
      this.updateDocumentDirection();
    },
    updateDocumentDirection(): void {
      const isRtl = this.$i18n?.locale === 'ar-SA';
      document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
      document.documentElement.setAttribute('lang', isRtl ? 'ar' : 'en');
    },
  },
  watch: {
    currentLocale() {
      this.updateDocumentDirection();
    },
  },
};
</script>
