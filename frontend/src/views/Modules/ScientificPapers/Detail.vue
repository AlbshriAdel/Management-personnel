<template>
  <Base :is-in-container="false">
    <div v-if="loading" class="p-4">Loading...</div>
    <div v-else-if="paper" class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold">{{ paper.title }}</h1>
          <span :class="statusBadgeClass" class="inline-block mt-2 px-3 py-1 text-sm font-medium rounded">
            {{ statusLabel }}
          </span>
        </div>
        <MediumButtonWithIcon :text="$t('scientificPapers.detail.editPaper')"
                              @button-click="isEditModalVisible = true"
        />
      </div>

      <p v-if="paper.abstract" class="text-gray-600 dark:text-gray-400">{{ paper.abstract }}</p>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Checklist -->
        <div class="border rounded p-4 dark:border-gray-700">
          <h2 class="text-lg font-semibold mb-3">{{ $t('scientificPapers.detail.checklist') }}</h2>
          <Checklist :paper-id="paper.id"
                     :items="paper.checklistItems || []"
                     @updated="loadPaper"
          />
        </div>

        <!-- Versions -->
        <div class="border rounded p-4 dark:border-gray-700">
          <h2 class="text-lg font-semibold mb-3">{{ $t('scientificPapers.detail.versions') }}</h2>
          <VersionList :paper-id="paper.id"
                      :versions="paper.versions || []"
                      @updated="loadPaper"
          />
        </div>
      </div>
    </div>
    <div v-else class="p-4">Paper not found.</div>

    <AddEditModal :is-modal-visible="isEditModalVisible"
                  :edited-data="paper"
                  header="Edit paper"
                  @modal-closed="isEditModalVisible = false"
                  @form-submitted="isEditModalVisible = false; loadPaper()"
    />
  </Base>
</template>

<script lang="ts">
import Base from "@/views/Modules/Base.vue";
import Checklist from "@/views/Modules/ScientificPapers/Components/Checklist.vue";
import VersionList from "@/views/Modules/ScientificPapers/Components/VersionList.vue";
import AddEditModal from "@/views/Modules/ScientificPapers/AddEditModal.vue";
import MediumButtonWithIcon from "@/components/Navigation/Button/MediumButtonWithIcon.vue";

import { PapersStore } from "@/scripts/Vue/Store/Module/ScientificPapers/PapersStore";
import VueRouterScientificPapers from "@/router/Modules/VueRouterScientificPapers";

export default {
  data() {
    return {
      paper: null as Record | null,
      loading: true,
      isEditModalVisible: false,
    };
  },
  components: {
    Base,
    Checklist,
    VersionList,
    AddEditModal,
    MediumButtonWithIcon,
  },
  computed: {
    statusLabel(): string {
      if (!this.paper) return "";
      const statusMap = {
        in_progress: this.$t("scientificPapers.list.status.inProgress"),
        under_review: this.$t("scientificPapers.list.status.underReview"),
        published: this.$t("scientificPapers.list.status.published"),
      };
      return statusMap[this.paper.status] || this.paper.status;
    },
    statusBadgeClass(): string {
      if (!this.paper) return "";
      const classes = {
        in_progress: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
        under_review: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        published: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
      };
      return classes[this.paper.status] || "bg-gray-100 text-gray-800";
    },
  },
  async beforeMount(): Promise<void> {
    await this.loadPaper();
  },
  methods: {
    async loadPaper(): Promise<void> {
      const id = Number(this.$route.params.id);
      if (!id) {
        this.loading = false;
        return;
      }
      this.loading = true;
      try {
        const store = PapersStore();
        this.paper = await store.getOne(id);
      } catch {
        this.paper = null;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
