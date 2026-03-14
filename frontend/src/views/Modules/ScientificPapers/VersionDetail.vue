<template>
  <Base :is-in-container="false">
    <div v-if="loading" class="p-4">Loading...</div>
    <div v-else class="space-y-4">
      <div class="flex items-center gap-4">
        <router-link :to="{ name: 'scientificPapersDetail', params: { id: paperId } }"
                    class="text-blue-600 hover:underline"
        >
          ← Back to paper
        </router-link>
        <h1 class="text-xl font-bold">Version: {{ versionName }}</h1>
      </div>

      <div class="border rounded p-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold mb-3">Files</h2>
        <FileManager :paper-id="paperId"
                     :version-id="versionId"
                     :current-path="currentPath"
                     @pathChanged="currentPath = $event"
        />
      </div>
    </div>
  </Base>
</template>

<script lang="ts">
import Base from "@/views/Modules/Base.vue";
import FileManager from "@/views/Modules/ScientificPapers/Components/FileManager.vue";

import { PapersStore } from "@/scripts/Vue/Store/Module/ScientificPapers/PapersStore";

export default {
  data() {
    return {
      versionName: "",
      currentPath: "",
      loading: true,
    };
  },
  computed: {
    paperId(): number {
      return Number(this.$route.params.id);
    },
    versionId(): number {
      return Number(this.$route.params.versionId);
    },
  },
  components: {
    Base,
    FileManager,
  },
  async beforeMount(): Promise<void> {
    const store = PapersStore();
    const paper = await store.getOne(this.paperId);
    const version = (paper?.versions ?? []).find((v: Record) => v.id === this.versionId);
    this.versionName = version?.name ?? "Unknown";
    this.loading = false;
  },
  methods: {},
};
</script>
