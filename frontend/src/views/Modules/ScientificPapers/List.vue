<template>
  <Base :info-block-description="$t('scientificPapers.list.description')"
        :is-in-container="false"
  >
    <div v-if="papers.length > 0" class="flex flex-wrap gap-4">
      <PaperCard v-for="paper in papers"
                 :key="paper.id"
                 :paper="paper"
                 @click="onPaperClick(paper)"
                 @edit-click="onEditClick($event)"
                 @remove-click="onRemoveClick($event)"
      />
    </div>

    <div v-else>
      <NoResultsText :text="$t('scientificPapers.list.noPapers')" />
    </div>
  </Base>

  <AddEditModal :is-modal-visible="isAddEditModalVisible"
                :edited-data="handledPaper"
                :header="addEditHeader"
                @modal-closed="isAddEditModalVisible = false; handledPaper = null"
                @form-submitted="onFormSubmitted"
  />

  <RemoveModal :is-modal-visible="isRemoveModalVisible"
               :id="handledPaper?.id ?? null"
               @modal-closed="isRemoveModalVisible = false"
               @remove-confirm-click="onRemoveConfirm"
  />

  <FloatingRoundedPlus class="mb-10"
                       @click="isAddEditModalVisible = true; handledPaper = null"
                       v-tippy="$t('scientificPapers.list.button.addNew.hoverText')"
  />
</template>

<script lang="ts">
import Base from "@/views/Modules/Base.vue";
import PaperCard from "@/views/Modules/ScientificPapers/Components/PaperCard.vue";
import NoResultsText from "@/components/Page/NoResultsText.vue";

import AddEditModal from "@/views/Modules/ScientificPapers/AddEditModal.vue";
import RemoveModal from "@/views/Modules/ScientificPapers/RemoveModal.vue";

import FloatingRoundedPlus from "@/components/Ui/Floating/FloatingRoundedPlus.vue";

import { ComponentData } from "@/scripts/Vue/Types/Components/types";
import { PapersStore } from "@/scripts/Vue/Store/Module/ScientificPapers/PapersStore";
import { BackendModuleCaller } from "@/scripts/Core/Services/Request/BackendModuleCaller";
import SymfonyScientificPapersRoutes from "@/router/SymfonyRoutes/Modules/SymfonyScientificPapersRoutes";
import VueRouterScientificPapers from "@/router/Modules/VueRouterScientificPapers";

export default {
  data(): ComponentData {
    return {
      store: null as null | InstanceType<typeof PapersStore>,
      handledPaper: null as null | Record,
      papers: [] as Array<Record>,
      isAddEditModalVisible: false,
      isRemoveModalVisible: false,
    };
  },
  components: {
    Base,
    AddEditModal,
    RemoveModal,
    PaperCard,
    NoResultsText,
    FloatingRoundedPlus,
  },
  computed: {
    addEditHeader(): string {
      if (!this.handledPaper) {
        return "Add new paper";
      }
      return "Edit paper";
    },
  },
  async beforeMount(): Promise<void> {
    this.store = PapersStore();
    await this.store.getAll();
    this.papers = this.store.allEntries;
  },
  watch: {
    "store.allEntries"(): void {
      this.papers = this.store?.allEntries ?? [];
    },
  },
  methods: {
    onPaperClick(paper: Record): void {
      this.$router.push({
        name: VueRouterScientificPapers.ROUTE_NAME_PAPER_DETAIL,
        params: { id: String(paper.id) },
      });
    },
    onEditClick(event: { paper: Record }): void {
      this.handledPaper = event.paper;
      this.isAddEditModalVisible = true;
    },
    onRemoveClick(event: { paper: Record }): void {
      this.handledPaper = event.paper;
      this.isRemoveModalVisible = true;
    },
    async onFormSubmitted(): Promise<void> {
      this.isAddEditModalVisible = false;
      this.handledPaper = null;
      await this.store?.getAll();
      this.papers = this.store?.allEntries ?? [];
    },
    async onRemoveConfirm(): Promise<void> {
      if (!this.handledPaper?.id) return;
      await new BackendModuleCaller().remove(
        SymfonyScientificPapersRoutes.PAPERS_BASE_URL,
        this.handledPaper.id
      );
      this.isRemoveModalVisible = false;
      this.handledPaper = null;
      await this.store?.getAll();
      this.papers = this.store?.allEntries ?? [];
    },
  },
};
</script>
