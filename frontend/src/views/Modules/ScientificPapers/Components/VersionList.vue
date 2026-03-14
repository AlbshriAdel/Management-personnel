<template>
  <div>
    <div v-for="version in versions" :key="version.id" class="py-2 border-b dark:border-gray-700 last:border-0 flex justify-between items-center">
      <router-link :to="versionPath(version)"
                  class="text-blue-600 hover:underline dark:text-blue-400 font-medium"
      >
        {{ version.name }}
      </router-link>
      <span class="text-sm text-gray-500">{{ formatDate(version.createdAt) }}</span>
      <button type="button"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeVersion(version)"
      >
        ×
      </button>
    </div>
    <form @submit.prevent="addVersion" class="mt-3 flex gap-2">
      <input v-model="newVersionName"
             type="text"
             class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
             placeholder="e.g. v1.0, Submission"
      />
      <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
        {{ $t('scientificPapers.detail.addVersion') }}
      </button>
    </form>
  </div>
</template>

<script lang="ts">
import AppAxios from "@/scripts/Core/Services/Request/AppAxios";
import SymfonyRoutes from "@/router/SymfonyRoutes";

export default {
  props: {
    paperId: {
      type: Number,
      required: true,
    },
    versions: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["updated"],
  data() {
    return {
      newVersionName: "",
    };
  },
  methods: {
    getBaseUrl(): string {
      return SymfonyRoutes.buildUrl(`/module/scientific-papers/${this.paperId}/versions`);
    },
    versionPath(version: Record): Record {
      return {
        name: "scientificPapersVersionDetail",
        params: { id: String(this.paperId), versionId: String(version.id) },
      };
    },
    formatDate(dateStr: string): string {
      if (!dateStr) return "";
      return new Date(dateStr).toLocaleDateString();
    },
    async addVersion(): Promise<void> {
      if (!this.newVersionName.trim()) return;
      await new AppAxios().post(this.getBaseUrl(), { name: this.newVersionName.trim() });
      this.newVersionName = "";
      this.$emit("updated");
    },
    async removeVersion(version: Record): Promise<void> {
      await new AppAxios().delete(`${this.getBaseUrl()}/${version.id}`);
      this.$emit("updated");
    },
  },
};
</script>
