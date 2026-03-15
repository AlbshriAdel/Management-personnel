<template>
  <form @submit.prevent="onSubmit">
    <div class="mb-3">
      <label class="block text-sm font-medium mb-1">Title</label>
      <input v-model="formData.title"
             type="text"
             required
             class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
             placeholder="Paper title"
      />
    </div>
    <div class="mb-3">
      <label class="block text-sm font-medium mb-1">Abstract</label>
      <textarea v-model="formData.abstract"
                rows="4"
                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                placeholder="Brief abstract (optional)"
      />
    </div>
    <div class="mb-3">
      <label class="block text-sm font-medium mb-1">Status</label>
      <select v-model="formData.status"
              class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
      >
        <option value="in_progress">In Progress</option>
        <option value="under_review">Under Review</option>
        <option value="published">Published</option>
      </select>
    </div>
    <div class="flex justify-end">
      <button type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
      >
        Save
      </button>
    </div>
  </form>
</template>

<script lang="ts">
import { BackendModuleCaller } from "@/scripts/Core/Services/Request/BackendModuleCaller";
import BackendModuleCallConfig from "@/scripts/Dto/BackendModuleCallConfig";
import BaseApiResponse from "@/scripts/Response/BaseApiResponse";
import SymfonyScientificPapersRoutes from "@/router/SymfonyRoutes/Modules/SymfonyScientificPapersRoutes";

export default {
  props: {
    paperData: {
      type: [Object, null],
      required: false,
      default: () => ({
        title: "",
        abstract: "",
        status: "in_progress",
      }),
    },
  },
  emits: ["submit", "cancel"],
  data() {
    return {
      formData: {
        title: this.paperData?.title ?? "",
        abstract: this.paperData?.abstract ?? "",
        status: this.paperData?.status ?? "in_progress",
      },
    };
  },
  watch: {
    paperData: {
      handler(val) {
        this.formData = {
          title: val?.title ?? "",
          abstract: val?.abstract ?? "",
          status: val?.status ?? "in_progress",
        };
      },
      deep: true,
    },
  },
  methods: {
    async onSubmit(): Promise<void> {
      const data = {
        title: this.formData.title,
        abstract: this.formData.abstract,
        status: this.formData.status,
      };
      const config = new BackendModuleCallConfig(
        SymfonyScientificPapersRoutes.PAPERS_BASE_URL,
        this.paperData?.id ?? null,
        BaseApiResponse,
        data
      );
      config.reload = false;

      if (this.paperData?.id) {
        await new BackendModuleCaller().update(config);
      } else {
        await new BackendModuleCaller().new(config);
      }
      this.$emit("submit");
    },
  },
};
</script>
