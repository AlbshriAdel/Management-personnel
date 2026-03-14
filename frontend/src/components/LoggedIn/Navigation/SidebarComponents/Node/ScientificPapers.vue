<template>
  <NodeBase :required-right="nodeRight">
    <template #default="props">
      <SingleDropdownMenuElement :label="$t('navbar.rightSidebar.menu.scientificPapers.label')"
                                 :is-locked="!props.isRightGranted"
      >
        <template #icon>
          <fa icon="file-alt"/>
        </template>

        <template #children>
          <SingleMenuElement :route-path="router.ROUTE_PATH_PAPERS_LIST"
                             :label="$t('navbar.rightSidebar.menu.scientificPapers.children.list.label')"
                             @click="onMenuElementClick"
          />
        </template>
      </SingleDropdownMenuElement>
    </template>
  </NodeBase>
</template>

<script lang="ts">
import SingleDropdownMenuElement from "@/components/LoggedIn/Navigation/SidebarComponents/SingleDropdownMenuElement.vue";
import SingleMenuElement from "@/components/LoggedIn/Navigation/SidebarComponents/SingleMenuElement.vue";
import NodeBase from "@/components/LoggedIn/Navigation/SidebarComponents/Node/NodeBase.vue";

import SidebarMixin from "@/components/LoggedIn/Navigation/SidebarComponents/Mixin/SidebarMixin.vue";

import UserModuleRights from "@/scripts/Core/Security/Rights/UserModuleRights";

import VueRouterScientificPapers from "@/router/Modules/VueRouterScientificPapers";

export default {
  components: {
    NodeBase,
    SingleDropdownMenuElement,
    SingleMenuElement
  },
  computed: {
    nodeRight(): string {
      return UserModuleRights.CAN_ACCESS_SCIENTIFIC_PAPERS_MODULE;
    },
    router() {
      return VueRouterScientificPapers;
    }
  },
  mixins: [
    SidebarMixin
  ]
}
</script>
