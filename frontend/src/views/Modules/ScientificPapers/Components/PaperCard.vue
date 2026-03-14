<template>
  <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 m-2 card cursor-pointer hover:shadow-lg transition-shadow"
       @click="$emit('click')"
  >
    <div class="p-5">
      <div class="flex justify-between items-start mb-2">
        <h5 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white break-words flex-1 mr-2">
          {{ paper.title }}
        </h5>
        <span :class="statusBadgeClass" class="px-2 py-1 text-xs font-medium rounded">
          {{ statusLabel }}
        </span>
      </div>
      <p v-if="paper.abstract" class="mb-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
        {{ paper.abstract }}
      </p>
      <p class="mb-3 text-xs text-gray-500 dark:text-gray-500">
        {{ formatDate(paper.updatedAt) }}
      </p>

      <div class="flex gap-2" @click.stop>
        <MediumButtonWithIcon :text="$t('scientificPapers.list.button.edit')"
                              button-extra-classes="pt-2 pb-2"
                              class="flex-1"
                              button-classes="w-full m-0-force"
                              text-classes="text-center w-full"
                              @button-click="$emit('editClick', { paper })"
        />
        <MediumButtonWithIcon :text="$t('scientificPapers.list.button.remove')"
                              class="flex-1"
                              button-classes="w-full m-0-force"
                              text-classes="text-center w-full"
                              background-color-class="bg-red-500"
                              @button-click="$emit('removeClick', { paper })"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import MediumButtonWithIcon from "@/components/Navigation/Button/MediumButtonWithIcon.vue";

export default {
  props: {
    paper: {
      type: Object,
      required: true,
    },
  },
  emits: ["click", "editClick", "removeClick"],
  components: {
    MediumButtonWithIcon,
  },
  computed: {
    statusLabel(): string {
      const statusMap = {
        in_progress: this.$t("scientificPapers.list.status.inProgress"),
        under_review: this.$t("scientificPapers.list.status.underReview"),
        published: this.$t("scientificPapers.list.status.published"),
      };
      return statusMap[this.paper.status] || this.paper.status;
    },
    statusBadgeClass(): string {
      const classes = {
        in_progress: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
        under_review: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        published: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
      };
      return classes[this.paper.status] || "bg-gray-100 text-gray-800";
    },
  },
  methods: {
    formatDate(dateStr: string): string {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      return d.toLocaleDateString();
    },
  },
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
