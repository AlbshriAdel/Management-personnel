<template>
  <div>
    <Modal :is-visible="showModal"
           id="paper-edit"
           :title="header"
           @modal-closed="onModalClosed"
           :size="modalSize"
    >
      <template #content>
        <AddEditForm :paper-data="paperFormData"
                     @submit="$emit('formSubmitted')"
        />
      </template>
    </Modal>
  </div>
</template>

<script lang="ts">
import Modal from "@/components/Modal/Modal.vue";
import AddEditForm from "@/views/Modules/ScientificPapers/AddEditForm.vue";
import ResponsiveModalSizeMixin from "@/mixins/Responsive/ResponsiveModalSizeMixin.vue";

import { ComponentData } from "@/scripts/Vue/Types/Components/types";

export default {
  data(): ComponentData {
    return {
      initialSmallSizeModal: "medium",
      showModal: false,
      paperFormData: { ...this.editedData },
    };
  },
  props: {
    editedData: {
      type: [null, Object],
      required: false,
      default: () => ({}),
    },
    header: {
      type: String,
      required: true,
    },
    isModalVisible: {
      type: Boolean,
      required: true,
      default: false,
    },
  },
  components: {
    Modal,
    AddEditForm,
  },
  mixins: [ResponsiveModalSizeMixin],
  emits: ["modalClosed", "formSubmitted"],
  methods: {
    onModalClosed(): void {
      this.$emit("modalClosed");
    },
  },
  updated(): void {
    this.showModal = this.isModalVisible;
  },
  watch: {
    editedData(): void {
      this.paperFormData = { ...this.editedData };
    },
  },
};
</script>
