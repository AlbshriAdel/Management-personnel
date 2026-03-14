<template>
  <div>
    <WarningModal :is-visible="showModal"
                  id="paper-remove"
                  :title="$t('generic.action.remove.dialog.header')"
                  @modal-closed="onModalClosed"
                  @confirm="onConfirm"
                  :size="modalSize"
    >
      <template #content>
        {{ $t('components.modal.text.removeRecord') }}
      </template>
    </WarningModal>
  </div>
</template>

<script lang="ts">
import WarningModal from "@/components/Modal/WarningModal.vue";

import { ComponentData } from "@/scripts/Vue/Types/Components/types";

import ResponsiveModalSizeMixin from "@/mixins/Responsive/ResponsiveModalSizeMixin.vue";

export default {
  data(): ComponentData {
    return {
      showModal: false,
    };
  },
  props: {
    id: {
      type: [Number, String],
      required: false,
      default: null,
    },
    isModalVisible: {
      type: Boolean,
      required: true,
      default: false,
    },
  },
  components: {
    WarningModal,
  },
  mixins: [ResponsiveModalSizeMixin],
  emits: ["modalClosed", "removeConfirmClick"],
  methods: {
    onConfirm(): void {
      this.$emit("modalClosed");
      this.$emit("removeConfirmClick");
    },
    onModalClosed(): void {
      this.$emit("modalClosed");
    },
  },
  updated(): void {
    this.showModal = this.isModalVisible;
  },
};
</script>
