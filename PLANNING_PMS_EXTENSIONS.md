# Personal Management System - Extension & Modification Planning Document

**Project:** [personal-management-system](https://github.com/Volmarg/personal-management-system)  
**Repository Structure:** Backend (PHP/Symfony) + Frontend (Vue.js/TypeScript) - separate repos  
**Installation Guide:** https://volmarg.github.io/docs/getting-started/installation.html

---

## Executive Summary

This document provides a comprehensive implementation plan for extending the Personal Management System (PMS) with the requested features. The PMS uses a **modular architecture** with:
- **Backend:** Symfony 5.4, PHP 8.3, Doctrine ORM
- **Frontend:** Vue 3, TypeScript, Vue I18n (@intlify), Vite
- **Translations:** Backend uses YAML (Symfony Translator), Frontend uses JSON (Vue I18n)

---

## 1. Arabic Language Support

### Current State
- **Backend:** Symfony Translator with `translations/` directory, YAML files per module (`messages.en.yaml`)
- **Frontend:** Vue I18n with `src/translations/en-US/` - JSON files organized by feature
- **Locale:** Single language (English) - `EnvReader.getAppDefaultLanguage()` returns default
- **RTL:** No RTL support currently

### Implementation Plan

#### 1.1 Backend (Symfony)
| Task | Details |
|------|---------|
| Add Arabic translations | Create `messages.ar.yaml` for each existing `messages.en.yaml` in `translations/` |
| Update `config/packages/translation.yaml` | Add `ar` to fallbacks: `fallbacks: [en, ar]` |
| Update `config/packages/framework.yaml` | Add `ar` to supported locales |
| Create translation files | ~15 files: `translations/modules/*/messages.ar.yaml`, `translations/user/messages.ar.yaml`, etc. |

#### 1.2 Frontend (Vue)
| Task | Details |
|------|---------|
| Create `ar-SA` locale folder | `src/translations/ar-SA/` - mirror structure of `en-US/` |
| Add Arabic JSON files | Copy all 25+ JSON files from `en-US/` and translate |
| Update `TranslationsProvider.ts` | Support dynamic locale loading based on user preference |
| Add locale switcher | Store user locale in settings/localStorage, pass to backend via header/cookie |
| RTL support | Add `dir="rtl"` and `lang="ar"` to `<html>` when Arabic selected |

#### 1.3 RTL Layout
| Task | Details |
|------|---------|
| CSS logical properties | Replace `margin-left`, `padding-right` with `margin-inline-start`, `padding-inline-end` |
| Bootstrap/CSS framework | If using Bootstrap 5, add `dir="rtl"` - has built-in RTL support |
| Flexbox/Grid | Use `flex-direction: row-reverse` or logical properties for RTL |
| Icons | Mirror icon placement (e.g., chevrons) for RTL |
| Create RTL override stylesheet | `styles/rtl.scss` with direction-specific overrides |

#### 1.4 User Preference Storage
- Add `locale` field to User settings (backend: `Setting` entity or user preferences table)
- API: `GET/PATCH /user/setting/locale` or extend existing `BaseDataAction`
- Frontend: Persist in Vuex/Pinia or localStorage, send `Accept-Language: ar` header

### Estimated Effort
- Backend translations: 2-3 days
- Frontend translations: 3-4 days  
- RTL layout: 2-3 days
- **Total: ~8-10 days**

---

## 2. Manage Sections from Admin Panel

### Current State
- **Sections/Modules:** Hardcoded in `ModulesService::ALL_MODULES` - array of constant strings
- **Module locking:** `SettingsLockModuleService` - lock/unlock modules via `ModulesLockAction`
- **No CRUD for sections:** Modules are defined in code, not database
- **Permissions:** JWT-based auth, `ModuleAttribute` for route protection, `LockedResource` for folder-level locking

### Implementation Plan

#### 2.1 Database Schema Changes
Create new entities:

```php
// Section (replaces/enhances hardcoded module concept)
- id, name, slug, icon, display_order, active, created_at, updated_at
- permissions (JSON: browse, read, create, update, delete, admin)

// SectionPermission (granular per-section)
- id, section_id, permission_key, allowed_roles (JSON), created_at
```

#### 2.2 Migration Strategy
| Option | Pros | Cons |
|--------|------|------|
| **A: New Section entity** | Full flexibility, sections in DB | Breaking change, migration of existing modules |
| **B: Extend Module entity** | Reuse existing `Module` (id, name, active) | Current Module is minimal, used for Todo relation |
| **C: Settings-based** | Use existing `Setting` entity | Less structured, harder to manage permissions |

**Recommendation:** Option A - Create `Section` entity, seed from `ALL_MODULES` in migration, gradually migrate.

#### 2.3 Backend API Endpoints
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/module/system/settings/sections` | List all sections |
| GET | `/module/system/settings/sections/{id}` | Get section details |
| PATCH | `/module/system/settings/sections/{id}` | Update section (name, icon, order, active) |
| DELETE | `/module/system/settings/sections/{id}` | Soft-delete/deactivate section |
| GET | `/module/system/settings/sections/{id}/permissions` | Get section permissions |
| PATCH | `/module/system/settings/sections/{id}/permissions` | Update permissions (browse, read, edit, delete) |

#### 2.4 Permission Model
- Extend `JwtUserRightsHandler` to check section permissions from DB
- Permission keys: `section.{slug}.browse`, `section.{slug}.read`, `section.{slug}.create`, etc.
- Map to existing `ModuleAttribute` - check DB instead of hardcoded list

#### 2.5 Frontend - Admin Panel UI
- New view: `System Settings > Sections` (add to `SystemSettingsModules.vue` or new tab)
- Table: Name, Icon, Order, Active, Actions (Edit, Delete)
- Edit modal: Name, Display order, Active toggle, Permissions matrix (checkboxes per role/user)
- Reorder: Drag-and-drop for `display_order`

### Estimated Effort
- Database + migrations: 1-2 days
- Backend API + permission integration: 3-4 days
- Frontend admin UI: 2-3 days
- **Total: ~6-9 days**

---

## 3. Scheduled Backups with Object Storage Support

### Current State
- **Existing backup:** `CronMakeBackupCommand` - creates SQL dump + zip of files, saves to local directory
- **Arguments:** `backup-directory`, `backup-database-name`, `backup-files-name`
- **Options:** `--skip-files`, `--skip-database`
- **No scheduling:** Must be run manually or via system cron
- **No Object Storage:** Files stored locally only

### Implementation Plan

#### 3.1 Configuration (Initial Setup)
Add to `.env`:
```
# Object Storage (S3-compatible: AWS S3, MinIO, DigitalOcean Spaces, etc.)
BACKUP_OBJECT_STORAGE_ENABLED=false
BACKUP_OBJECT_STORAGE_PROVIDER=s3  # s3, minio, do_spaces
BACKUP_S3_ENDPOINT=https://s3.amazonaws.com
BACKUP_S3_REGION=us-east-1
BACKUP_S3_BUCKET=my-pms-backups
BACKUP_S3_ACCESS_KEY=
BACKUP_S3_SECRET_KEY=
BACKUP_S3_PATH_PREFIX=pms-backups/

# Schedule (cron expression)
BACKUP_SCHEDULE_CRON=0 2 * * *  # Daily at 2 AM
```

#### 3.2 PHP Dependencies
```json
"league/flysystem-bundle": "^3.0",
"league/flysystem-aws-s3-v3": "^3.0",
"aws/aws-sdk-php": "^3.0"
```
Or for S3-compatible (MinIO, DO Spaces): Use same AWS SDK with custom endpoint.

#### 3.3 Backend Implementation
| Component | Details |
|-----------|---------|
| `ObjectStorageBackupService` | Wrapper around Flysystem - upload zip to S3 after local backup |
| `BackupScheduleService` | Store schedule in DB, use Symfony Scheduler or external cron |
| `BackupConfigurationAction` | API to get/set backup config (admin only) |
| `BackupStatusAction` | API to get last backup status, trigger manual backup |
| Modify `CronMakeBackupCommand` | Add `--upload-to-s3` option, call ObjectStorageBackupService |
| Configuration wizard | API to validate S3 credentials before enabling |

#### 3.4 Database
```sql
-- Backup configuration (encrypted credentials)
backup_config: id, enabled, provider, config_json (encrypted), schedule_cron, last_run_at, last_status
```

#### 3.5 Scheduling Options
| Option | Implementation |
|--------|----------------|
| **Symfony Scheduler** | `symfony/scheduler` - run backup command on schedule |
| **Cron + DB config** | Keep system cron, read schedule from DB |
| **Manual trigger** | Button in admin to run backup now |

**Recommendation:** Use system cron (simplest) - admin configures cron expression, user adds to crontab. Alternative: Implement `symfony/scheduler` if available for Symfony 5.4.

#### 3.6 Admin UI
- Settings > Backups (new section)
- Step 1: Configure Object Storage (endpoint, bucket, credentials) - Test connection button
- Step 2: Set schedule (cron builder or preset: daily, weekly)
- Step 3: Enable backups
- Display: Last backup date, status, "Run now" button

### Estimated Effort
- Flysystem + S3 integration: 2 days
- Configuration + validation: 1-2 days
- Scheduling logic: 1-2 days
- Admin UI: 1-2 days
- **Total: ~5-8 days**

---

## 4. Scientific Papers Section Management

### Implementation Plan

#### 4.1 Data Model
```php
// MyScientificPaper
- id, user_id, title, abstract (text), status (enum: in_progress, under_review, published)
- created_at, updated_at, deleted (soft delete)

// Status enum: IN_PROGRESS, UNDER_REVIEW, PUBLISHED
```

#### 4.2 Backend
| Component | Details |
|-----------|---------|
| Entity | `MyScientificPaper` |
| Repository | `MyScientificPaperRepository` |
| Service | `MyScientificPapersService` - CRUD |
| Actions | `MyScientificPapersAction` - list, create, update, delete |
| Add to ModulesService | `MODULE_NAME_SCIENTIFIC_PAPERS = "Scientific Papers"` |

#### 4.3 Frontend - Paper Cards View
- Route: `/papers` or `/scientific-papers`
- Layout: Card grid (similar to Travel Ideas or Achievements)
- Each card: Title, status tag (badge), abstract preview, created date
- Status tag colors: In Progress (yellow), Under Review (blue), Published (green)
- Click card → Navigate to paper details page

### Estimated Effort
- Backend: 1-2 days
- Frontend cards: 1-2 days
- **Total: ~2-4 days**

---

## 5. Paper Details Page

### Implementation Plan

#### 5.1 Checklist Feature
```php
// MyScientificPaperChecklistItem
- id, paper_id, title, completed (bool), sort_order, created_at
```

| Component | Details |
|-----------|---------|
| Entity | `MyScientificPaperChecklistItem` |
| API | CRUD for checklist items |
| Frontend | Checklist component (similar to Todo elements) - add, toggle complete, reorder, delete |

#### 5.2 Version Folders
```php
// MyScientificPaperVersion
- id, paper_id, name (e.g., "v1.0", "Submission"), created_at

// Files stored in: upload/scientific_papers/{paper_id}/{version_id}/{filename}
// Or use ModuleData/Storage pattern - folder path as record_identifier
```

| Component | Details |
|-----------|---------|
| Entity | `MyScientificPaperVersion` |
| File storage | Reuse `StorageService` pattern - base path: `scientific_papers/{paperId}/` |
| Folder structure | Each version = folder, subfolders allowed (use existing tree logic from Storage) |

#### 5.3 File Upload per Version
- Reuse `FileUploadService`, `StorageFolderService`, `StorageFileService`
- Path: `EnvReader::getUploadDir()/scientific_papers/{paper_id}/{version_id}/{subfolder}/`
- Allow nested folders within each version

#### 5.4 Page Layout
```
[Paper Title] [Status Badge] [Edit]
---
Tabs: Overview | Checklist | Versions
---
Overview: Abstract, metadata
Checklist: Add item, list with checkboxes
Versions: Tree of version folders, each expandable to show subfolders + files
```

### Estimated Effort
- Checklist: 1 day
- Version folders + file upload: 2-3 days
- Page layout + integration: 1-2 days
- **Total: ~4-6 days**

---

## 6. File Management Features

### Current State
- Storage module shows: `name`, `ext`, `size`, `tags` (from `getDirNodeFiles`)
- Actions: Rename, download, remove (via `StorageFileAction`, `FolderHandlerMixin`)
- **Missing:** File type (MIME), creation date, Preview/View

### Implementation Plan

#### 6.1 Backend Enhancements
| Field | Source | API Change |
|-------|--------|------------|
| File name | Already have | - |
| File type | `mime_content_type()` or extension map | Add to response |
| File size | `$nodeData['size']` | Already in response |
| Creation date | `filemtime()` or `stat()` | Add to `getDirNodeFiles` |

#### 6.2 File Actions
| Action | Implementation |
|--------|----------------|
| Download | Already exists - `StorageFileAction` |
| Delete | Already exists - `RemoveFiles` |
| View/Preview | New: For images/PDFs - return URL or base64; for others - download or "preview not available" |

#### 6.3 Preview Implementation
- **Images:** Use existing lightbox or `<img src="...">` - files already served from upload dir
- **PDF:** Embed with `<iframe>` or `<object>` - need route to serve file with `Content-Disposition: inline`
- **Text files:** Fetch and display in modal
- **Other:** Show "Download to view" message

#### 6.4 Frontend - File Table Enhancement
- Add columns: Type (icon + MIME), Size (formatted), Date
- Action buttons: View (eye icon), Download, Delete
- Reuse `SingleFile.vue` pattern from Storage/Files

### Estimated Effort
- Backend: 0.5-1 day
- Frontend: 1-2 days
- **Total: ~1.5-3 days**

---

## 7. Additional Recommendations

### 7.1 Security & Performance
| Recommendation | Rationale |
|----------------|-----------|
| **Rate limiting on backup trigger** | Prevent abuse of "Run backup now" |
| **Encrypt backup config in DB** | Use existing `specsharper/encrypt-bundle` for S3 credentials |
| **File upload size limits** | Ensure `php.ini` and Symfony config allow scientific paper sizes (e.g., 50MB) |
| **Virus scanning for uploads** | Optional: ClamAV integration for uploaded files |

### 7.2 UX Improvements
| Recommendation | Rationale |
|----------------|-----------|
| **Paper search/filter** | Filter by status, date range, full-text search on title/abstract |
| **Paper export** | Export paper metadata + checklist to PDF/Word |
| **Version comparison** | Diff view between versions (optional, complex) |
| **Bulk operations** | Select multiple papers, bulk status update, bulk delete |

### 7.3 Technical Debt
| Recommendation | Rationale |
|----------------|-----------|
| **Unify Module/Section concept** | Current `Module` entity is underused - consolidate with Section management |
| **API versioning** | Add `/api/v1/` prefix for future compatibility |
| **E2E tests** | Add Playwright/Cypress for critical flows (login, backup, paper CRUD) |

### 7.4 Integration Opportunities
| Recommendation | Rationale |
|----------------|-----------|
| **DOI integration** | Fetch paper metadata from DOI (CrossRef API) when adding papers |
| **Citation export** | Export to BibTeX, RIS format |
| **Calendar integration** | Link paper deadlines (review, submission) to Schedules module |

---

## Implementation Priority & Timeline

| Phase | Features | Estimated Duration |
|-------|----------|-------------------|
| **Phase 1** | Scientific Papers section + Paper details + File management | 3-4 weeks |
| **Phase 2** | Arabic localization + RTL | 2 weeks |
| **Phase 3** | Section management from admin | 1.5-2 weeks |
| **Phase 4** | Scheduled backups + Object Storage | 1.5-2 weeks |

**Total estimated: 8-10 weeks** (single developer, full-time)

---

## File Structure Summary (New/Modified)

### Backend (personal-management-system)
```
src/
├── Entity/Modules/ScientificPapers/
│   ├── MyScientificPaper.php
│   ├── MyScientificPaperChecklistItem.php
│   └── MyScientificPaperVersion.php
├── Action/Modules/ScientificPapers/
│   ├── MyScientificPapersAction.php
│   ├── MyScientificPaperChecklistAction.php
│   └── MyScientificPaperVersionAction.php
├── Services/Module/ScientificPapers/
│   └── MyScientificPapersService.php
├── Services/Backup/
│   ├── ObjectStorageBackupService.php
│   └── BackupConfigurationService.php
├── Action/Modules/System/Settings/
│   ├── SectionsAction.php
│   └── BackupConfigAction.php
translations/
├── modules/scientific_papers/messages.en.yaml
├── modules/scientific_papers/messages.ar.yaml
└── (all existing) messages.ar.yaml
```

### Frontend (personal-management-system-front)
```
src/
├── views/Modules/ScientificPapers/
│   ├── List.vue (paper cards)
│   ├── Detail.vue (paper details page)
│   ├── Components/
│   │   ├── PaperCard.vue
│   │   ├── Checklist.vue
│   │   └── VersionFolders.vue
├── router/Modules/VueRouterScientificPapers.ts
├── translations/
│   ├── en-US/module/scientificPapers.json
│   └── ar-SA/ (all files - new locale)
```

---

## Dependencies to Add

### Backend (composer.json)
```json
"league/flysystem-bundle": "^3.0",
"league/flysystem-aws-s3-v3": "^3.0",
"aws/aws-sdk-php": "^3.0"
```

### Frontend (package.json)
- No new dependencies for Arabic/RTL - Vue I18n already supports it
- Optional: `vue-draggable-plus` for section reordering

---

## Conclusion

This plan provides a structured approach to implementing all requested features. Key considerations:

1. **Arabic + RTL** requires coordinated changes across backend YAML, frontend JSON, and CSS
2. **Section management** is the most architecturally significant - consider phased rollout
3. **Scientific Papers** can leverage existing patterns (Todo, Issues, Storage)
4. **Backups + Object Storage** builds on existing `CronMakeBackupCommand`
5. **File management** enhancements are incremental to current Storage module

For implementation, start with **Scientific Papers** (highest user value, self-contained) or **Arabic** (if target audience is Arabic-speaking).
