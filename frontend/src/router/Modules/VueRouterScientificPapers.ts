import { RouteRecordRaw } from "vue-router";
import { Component } from "vue";

import UserModuleRights from "@/scripts/Core/Security/Rights/UserModuleRights";

export default class VueRouterScientificPapers {
    static readonly ROUTE_PREFIX = "/scientific-papers";
    static readonly ROUTE_GROUP = "scientificPapers";

    static readonly ROUTE_NAME_PAPERS_LIST = VueRouterScientificPapers.ROUTE_GROUP + "List";
    static readonly ROUTE_PATH_PAPERS_LIST = VueRouterScientificPapers.ROUTE_PREFIX + "/list";

    static readonly ROUTE_NAME_PAPER_DETAIL = VueRouterScientificPapers.ROUTE_GROUP + "Detail";
    static readonly ROUTE_PATH_PAPER_DETAIL = VueRouterScientificPapers.ROUTE_PREFIX + "/:id";

    static readonly ROUTE_NAME_VERSION_DETAIL = VueRouterScientificPapers.ROUTE_GROUP + "VersionDetail";
    static readonly ROUTE_PATH_VERSION_DETAIL = VueRouterScientificPapers.ROUTE_PREFIX + "/:id/version/:versionId";

    public static readonly routesConfiguration: Array<RouteRecordRaw> = [
        {
            path: VueRouterScientificPapers.ROUTE_PATH_PAPERS_LIST,
            name: VueRouterScientificPapers.ROUTE_NAME_PAPERS_LIST,
            component: (): Promise<Component> => import("@/views/Modules/ScientificPapers/List.vue"),
            meta: {
                requiredRight: UserModuleRights.CAN_ACCESS_SCIENTIFIC_PAPERS_MODULE,
            },
        },
        {
            path: VueRouterScientificPapers.ROUTE_PATH_PAPER_DETAIL,
            name: VueRouterScientificPapers.ROUTE_NAME_PAPER_DETAIL,
            component: (): Promise<Component> => import("@/views/Modules/ScientificPapers/Detail.vue"),
            meta: {
                requiredRight: UserModuleRights.CAN_ACCESS_SCIENTIFIC_PAPERS_MODULE,
            },
        },
        {
            path: VueRouterScientificPapers.ROUTE_PATH_VERSION_DETAIL,
            name: VueRouterScientificPapers.ROUTE_NAME_VERSION_DETAIL,
            component: (): Promise<Component> => import("@/views/Modules/ScientificPapers/VersionDetail.vue"),
            meta: {
                requiredRight: UserModuleRights.CAN_ACCESS_SCIENTIFIC_PAPERS_MODULE,
            },
        },
    ];
}
