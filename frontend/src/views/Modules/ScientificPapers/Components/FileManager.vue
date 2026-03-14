<template>
  <div>
    <div v-if="currentPath" class="mb-2 text-sm text-gray-600 dark:text-gray-400">
        <span class="cursor-pointer hover:underline" @click="emitPath('')">Root</span>
      <span v-for="(part, i) in pathParts" :key="i">
        / <span class="cursor-pointer hover:underline" @click="emitPath(pathParts.slice(0, i + 1).join('/'))">{{ part }}</span>
      </span>
    </div>

    <div class="mb-3 flex gap-2 flex-wrap">
      <input v-model="newFolderName"
             type="text"
             class="flex-1 min-w-[120px] px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
             placeholder="New folder name"
      />
      <button type="button"
              class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
              @click="createFolder"
      >
        Create folder
      </button>
      <button type="button"
              class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 flex items-center gap-1"
              @click="openUploadModal"
      >
        <fa icon="upload" />
        Upload files
      </button>
    </div>

    <UploadDialog :configuration-id="uploadConfigId"
                  :extra-data="uploadExtraData"
                  :is-visible="isUploadModalVisible"
                  @modal-closed="isUploadModalVisible = false; load()"
                  @upload-finished="load()"
    />

    <div v-if="loading" class="py-4">Loading...</div>
    <div v-else>
      <div v-if="folders.length > 0" class="mb-4">
        <h3 class="text-sm font-medium mb-2">Folders</h3>
        <div class="space-y-1">
          <div v-for="folder in folders"
               :key="folder.path"
               class="flex items-center gap-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded cursor-pointer"
               @click="emitPath(folder.path)"
          >
            <fa icon="folder" class="text-yellow-500" />
            {{ folder.name }}
          </div>
        </div>
      </div>
      <div v-if="files.length > 0">
        <h3 class="text-sm font-medium mb-2">Files</h3>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="text-left py-2">Name</th>
              <th class="text-left py-2">Type</th>
              <th class="text-left py-2">Size</th>
              <th class="text-left py-2">Date</th>
              <th class="text-left py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in files"
                :key="file.path"
                class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <td class="py-2">{{ file.name }}</td>
              <td class="py-2">{{ file.type || '—' }}</td>
              <td class="py-2">{{ formatSize(file.size) }}</td>
              <td class="py-2">{{ file.createdAt }}</td>
              <td class="py-2">
                <a :href="getFileUrl(file)"
                   target="_blank"
                   class="text-blue-600 hover:underline mr-2"
                >{{ canPreview(file) ? 'View' : 'Download' }}</a>
                <button type="button"
                        class="text-red-500 hover:text-red-700"
                        @click="deleteFile(file)"
                >Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="!loading && folders.length === 0 && files.length === 0" class="text-gray-500 py-4">
        No files or folders. Create a folder or upload files.
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import AppAxios from "@/scripts/Core/Services/Request/AppAxios";
import SymfonyRoutes from "@/router/SymfonyRoutes";
import EnvReader from "@/scripts/Core/System/EnvReader";
import UploadDialog from "@/components/Ui/Upload/Dialog/UploadDialog.vue";

const SCIENTIFIC_PAPERS_UPLOAD_CONFIG_ID = "a1b2c3d4e5f6scientificpapers789";

export default {
  props: {
    paperId: { type: Number, required: true },
    versionId: { type: Number, required: true },
    currentPath: { type: String, default: "" },
  },
  emits: ["pathChanged"],
  components: {
    UploadDialog,
  },
  data() {
    return {
      folders: [] as Array<Record>,
      files: [] as Array<Record>,
      newFolderName: "",
      loading: true,
      isUploadModalVisible: false,
      uploadPath: "" as string,
    };
  },
  computed: {
    uploadConfigId(): string {
      return SCIENTIFIC_PAPERS_UPLOAD_CONFIG_ID;
    },
    uploadExtraData(): Record<string, string> {
      return {
        uploadDir: this.uploadPath,
      };
    },
    pathParts(): string[] {
      return this.currentPath ? this.currentPath.split("/").filter(Boolean) : [];
    },
  },
  watch: {
    currentPath: { handler: "load" },
    paperId: { handler: "load" },
    versionId: { handler: "load" },
  },
  async beforeMount(): Promise<void> {
    await this.load();
  },
  methods: {
    async openUploadModal(): Promise<void> {
      let url = SymfonyRoutes.buildUrl(
        `/module/scientific-papers/${this.paperId}/versions/${this.versionId}/files/upload-path`
      );
      if (this.currentPath) {
        url += `?subPath=${encodeURIComponent(this.currentPath)}`;
      }
      const response = await new AppAxios().get(url);
      this.uploadPath = response.data?.singleRecord?.uploadPath ?? "";
      this.isUploadModalVisible = true;
    },
    emitPath(path: string): void {
      this.$emit("pathChanged", path);
      this.$emit("path-changed", path);
    },
    getListUrl(): string {
      let url = SymfonyRoutes.buildUrl(
        `/module/scientific-papers/${this.paperId}/versions/${this.versionId}/files/list`
      );
      if (this.currentPath) {
        url += `?subPath=${encodeURIComponent(this.currentPath)}`;
      }
      return url;
    },
    async load(): Promise<void> {
      this.loading = true;
      try {
        const response = await new AppAxios().get(this.getListUrl());
        const data = response.data?.singleRecord ?? response.data ?? {};
        this.folders = data.folders ?? [];
        this.files = data.files ?? [];
      } catch {
        this.folders = [];
        this.files = [];
      } finally {
        this.loading = false;
      }
    },
    async createFolder(): Promise<void> {
      if (!this.newFolderName.trim()) return;
      const url = SymfonyRoutes.buildUrl(
        `/module/scientific-papers/${this.paperId}/versions/${this.versionId}/files/folder`
      );
      await new AppAxios().post(url, {
        folderName: this.newFolderName.trim(),
        parentPath: this.currentPath,
      });
      this.newFolderName = "";
      this.emitPath(this.currentPath);
      await this.load();
    },
    async deleteFile(file: Record): Promise<void> {
      if (!confirm("Delete this file?")) return;
      const url = SymfonyRoutes.buildUrl(
        `/module/scientific-papers/${this.paperId}/versions/${this.versionId}/files/delete-file`
      );
      await new AppAxios().post(url, { filePath: file.path });
      await this.load();
    },
    getFileUrl(file: Record): string {
      const baseUrl = EnvReader.getBackendBaseUrl?.() ?? "";
      const path = file.publicPath || file.path;
      return `${baseUrl}/${path}`;
    },
    formatSize(bytes: number): string {
      if (!bytes) return "—";
      if (bytes < 1024) return bytes + " B";
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
      return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    },
    canPreview(file: Record): boolean {
      const ext = (file.type || "").toLowerCase();
      const previewTypes = ["jpg", "jpeg", "png", "gif", "webp", "pdf", "svg"];
      return previewTypes.includes(ext);
    },
  },
};
</script>
