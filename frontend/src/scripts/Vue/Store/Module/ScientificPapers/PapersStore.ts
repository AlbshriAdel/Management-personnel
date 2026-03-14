/**
 * @description defines the store for scientific papers
 */
import { defineStore } from 'pinia';

import { BackendModuleCaller } from "@/scripts/Core/Services/Request/BackendModuleCaller";

import SymfonyScientificPapersRoutes from "@/router/SymfonyRoutes/Modules/SymfonyScientificPapersRoutes";

const PapersStore = defineStore('scientificPapersStore', {
    state: () => ({
        allEntries: [] as Array<Record>,
    }),
    actions: {
        async getAll(): Promise<void> {
            this.allEntries = await new BackendModuleCaller().getAll(SymfonyScientificPapersRoutes.PAPERS_BASE_URL);
        },
        async getOne(id: number): Promise<Record> {
            return await new BackendModuleCaller().get(SymfonyScientificPapersRoutes.PAPERS_BASE_URL, id);
        },
    }
});

export { PapersStore };
