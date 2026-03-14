<template>
  <div>
    <div v-for="(item, index) in items" :key="item.id" class="flex items-center gap-2 py-2 border-b dark:border-gray-700 last:border-0">
      <input type="checkbox"
             :checked="item.completed"
             @change="toggleItem(item)"
             class="rounded"
      />
      <span :class="{ 'line-through text-gray-500': item.completed }" class="flex-1">{{ item.title }}</span>
      <button type="button"
              class="text-red-500 hover:text-red-700 text-sm"
              @click="removeItem(item)"
      >
        ×
      </button>
    </div>
    <form @submit.prevent="addItem" class="mt-3 flex gap-2">
      <input v-model="newItemTitle"
             type="text"
             class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
             placeholder="New checklist item"
      />
      <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
        {{ $t('scientificPapers.detail.addChecklistItem') }}
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
    items: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["updated"],
  data() {
    return {
      newItemTitle: "",
    };
  },
  methods: {
    getBaseUrl(): string {
      return SymfonyRoutes.buildUrl(`/module/scientific-papers/${this.paperId}/checklist`);
    },
    async addItem(): Promise<void> {
      if (!this.newItemTitle.trim()) return;
      await new AppAxios().post(this.getBaseUrl(), {
        title: this.newItemTitle.trim(),
        sortOrder: this.items.length,
      });
      this.newItemTitle = "";
      this.$emit("updated");
    },
    async toggleItem(item: Record): Promise<void> {
      await new AppAxios().patch(`${this.getBaseUrl()}/${item.id}`, {
        completed: !item.completed,
      });
      this.$emit("updated");
    },
    async removeItem(item: Record): Promise<void> {
      await new AppAxios().delete(`${this.getBaseUrl()}/${item.id}`);
      this.$emit("updated");
    },
  },
};
</script>
