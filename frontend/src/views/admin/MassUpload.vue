<script setup lang="ts">
import { reactive, ref } from 'vue'
import * as adminApi from '@/api/admin'
import { isApiSuccess } from '@/types/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

interface PendingFile {
  file: File
  title: string
  author: string
  status: 'pending' | 'success' | 'failed'
  reason?: string
}

const pendingFiles = ref<PendingFile[]>([])
const dragging = ref(false)
const uploading = ref(false)
const progress = ref(0)
const feedback = ref('')
const feedbackVariant = ref<'info' | 'success' | 'warning' | 'danger'>('info')

const defaults = reactive({
  author: '',
  categoriesText: '',
  downloadable: true,
  status: 'draft',
})

function titleFromFilename(name: string): string {
  return name.replace(/\.pdf$/i, '').replace(/[_-]/g, ' ')
}

function addFiles(files: File[]) {
  const pdfFiles = files.filter((f) => f.type === 'application/pdf' || /\.pdf$/i.test(f.name))
  if (!pdfFiles.length) {
    toast.error('Please select supported PDF files only.')
    return
  }
  pdfFiles.forEach((file) => {
    pendingFiles.value.push({ file, title: titleFromFilename(file.name), author: '', status: 'pending' })
  })
}

function onFileInput(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files) addFiles(Array.from(input.files))
  input.value = ''
}

function onDrop(event: DragEvent) {
  dragging.value = false
  const files = event.dataTransfer?.files
  if (files) addFiles(Array.from(files))
}

function removeFile(index: number) {
  pendingFiles.value.splice(index, 1)
}

async function handleUpload() {
  if (!pendingFiles.value.length) {
    toast.error('Please select at least one PDF file to upload.')
    return
  }
  const missingTitle = pendingFiles.value.some((f) => !f.title.trim())
  if (missingTitle) {
    toast.error('Please provide titles for all files.')
    return
  }

  uploading.value = true
  progress.value = 0
  feedbackVariant.value = 'info'
  feedback.value = 'Uploading files…'

  try {
    const response = await adminApi.massUpload(
      pendingFiles.value.map((f) => ({ file: f.file, title: f.title.trim(), author: f.author.trim() })),
      {
        author: defaults.author,
        categories: defaults.categoriesText
          .split(',')
          .map((c) => c.trim())
          .filter(Boolean),
        downloadable: defaults.downloadable,
        status: defaults.status,
      },
      (percent) => {
        progress.value = percent
      },
    )

    const body = response.data
    if (isApiSuccess(body)) {
      const results = body.data.results
      results.success.forEach((item) => {
        const entry = pendingFiles.value.find((f) => f.file.name === item.filename)
        if (entry) entry.status = 'success'
      })
      results.failed.forEach((item) => {
        const entry = pendingFiles.value.find((f) => f.file.name === item.filename)
        if (entry) {
          entry.status = 'failed'
          entry.reason = item.reason
        }
      })

      const successCount = results.success.length
      const failedCount = results.failed.length
      if (failedCount === 0) {
        feedbackVariant.value = 'success'
        feedback.value = `All ${successCount} books were uploaded successfully!`
        toast.success(feedback.value)
        setTimeout(() => {
          pendingFiles.value = []
          feedback.value = ''
        }, 2000)
      } else {
        feedbackVariant.value = 'warning'
        feedback.value = `${successCount} succeeded, ${failedCount} failed.`
      }
    } else {
      feedbackVariant.value = 'danger'
      feedback.value = 'Upload failed: some or all files could not be processed.'
    }
  } catch (err: any) {
    feedbackVariant.value = 'danger'
    feedback.value = `Upload failed: ${err.response?.data?.message ?? err.message ?? 'Network error'}`
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <div class="container mass-upload-page">
    <div class="page-header">
      <h1>Mass Upload PDFs</h1>
      <RouterLink to="/admin" class="btn btn-outline">Back to Dashboard</RouterLink>
    </div>

    <div
      class="drop-area"
      :class="{ dragging }"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <p class="drop-title">Drag &amp; Drop PDF Files Here</p>
      <p class="text-muted">or</p>
      <input id="pdf-files-input" type="file" accept="application/pdf" multiple hidden @change="onFileInput" />
      <label for="pdf-files-input" class="btn btn-outline">Browse Files</label>
    </div>

    <div v-if="pendingFiles.length" class="files-list card">
      <h3>Selected Files <span class="badge">{{ pendingFiles.length }}</span></h3>
      <div v-for="(item, index) in pendingFiles" :key="item.file.name + index" class="file-row" :class="item.status">
        <div class="file-row-header">
          <strong>{{ item.file.name }}</strong>
          <button type="button" class="btn-close" aria-label="Remove" @click="removeFile(index)">&times;</button>
        </div>
        <div class="file-row-fields">
          <input v-model="item.title" type="text" placeholder="Book Title" required />
          <input v-model="item.author" type="text" placeholder="Author (optional)" />
        </div>
        <p v-if="item.reason" class="file-reason">{{ item.reason }}</p>
      </div>
    </div>

    <div class="card defaults-card">
      <h3>Default Values for All Books</h3>
      <div class="form-grid">
        <div class="form-field">
          <label for="default-author">Default Author</label>
          <input id="default-author" v-model="defaults.author" type="text" placeholder="Optional" />
        </div>
        <div class="form-field">
          <label for="default-categories">Default Categories</label>
          <input id="default-categories" v-model="defaults.categoriesText" type="text" placeholder="Comma-separated categories" />
        </div>
        <div class="form-field checkbox-field">
          <label><input v-model="defaults.downloadable" type="checkbox" /> Allow download</label>
        </div>
        <div class="form-field">
          <label for="default-status">Default Status</label>
          <select id="default-status" v-model="defaults.status">
            <option value="draft">Draft</option>
            <option value="public">Public</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="uploading || feedback" class="upload-status">
      <div v-if="uploading" class="progress-bar-track">
        <div class="progress-bar-fill" :style="{ width: progress + '%' }"></div>
      </div>
      <p v-if="feedback" class="alert" :class="`alert-${feedbackVariant === 'danger' ? 'danger' : feedbackVariant === 'success' ? 'success' : 'info'}`">
        {{ feedback }}
      </p>
    </div>

    <div class="page-actions">
      <button type="button" class="btn btn-primary" :disabled="uploading || !pendingFiles.length" @click="handleUpload">
        {{ uploading ? 'Uploading…' : 'Upload All Files' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.mass-upload-page {
  padding: 2.5rem 1.5rem;
  max-width: 760px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.drop-area {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius);
  padding: 3rem 1.5rem;
  text-align: center;
  margin-bottom: 1.5rem;
  background: var(--color-surface);
}

.drop-area.dragging {
  border-color: var(--color-primary);
  background: var(--color-bg);
}

.drop-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.files-list {
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}

.badge {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  font-size: 0.8rem;
  padding: 0.1rem 0.6rem;
}

.file-row {
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.75rem;
  margin-top: 0.75rem;
}

.file-row.success {
  border-color: var(--color-success);
  background: #f2f8f4;
}

.file-row.failed {
  border-color: var(--color-danger);
  background: #faf1f1;
}

.file-row-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  color: var(--color-text-muted);
}

.file-row-fields {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.file-row-fields input {
  flex: 1;
  min-width: 140px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.4rem 0.6rem;
}

.file-reason {
  margin: 0.4rem 0 0;
  font-size: 0.8rem;
  color: var(--color-danger);
}

.defaults-card {
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem 1rem;
}

.checkbox-field label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--color-text-muted);
}

.upload-status {
  margin-bottom: 1.5rem;
}

.progress-bar-track {
  height: 0.6rem;
  background: var(--color-border);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.progress-bar-fill {
  height: 100%;
  background: var(--color-primary);
  transition: width 0.2s ease;
}

.page-actions {
  display: flex;
  justify-content: flex-end;
}
</style>
