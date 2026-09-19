<script setup lang="ts">
import { reactive, ref } from 'vue'
import * as adminApi from '@/api/admin'
import { isApiSuccess, requestErrorMessage } from '@/types/api'
import { useToast } from '@/composables/useToast'
import { useCategoryOptions } from '@/composables/useCategoryOptions'
import CategoryPicker from '@/components/CategoryPicker.vue'

const toast = useToast()
const availableCategories = useCategoryOptions()

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
  categories: [] as string[],
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
    const response = await adminApi.uploadBooks(
      pendingFiles.value.map((f) => ({ file: f.file, title: f.title.trim(), author: f.author.trim() })),
      {
        author: defaults.author,
        categories: defaults.categories,
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
  } catch (err) {
    feedbackVariant.value = 'danger'
    feedback.value = `Upload failed: ${requestErrorMessage(err, 'Network error')}`
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <div class="container upload-pdf-page">
    <div class="page-header">
      <h1>Upload PDF</h1>
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
      <input
        id="pdf-files-input"
        type="file"
        accept="application/pdf"
        multiple
        hidden
        data-testid="file-input"
        @change="onFileInput"
      />
      <label for="pdf-files-input" class="btn btn-outline">Browse Files</label>
    </div>

    <div v-if="pendingFiles.length" class="files-list card">
      <h3>Selected Files <span class="badge">{{ pendingFiles.length }}</span></h3>
      <div
        v-for="(item, index) in pendingFiles"
        :key="item.file.name + index"
        class="file-row"
        :class="item.status"
        :data-testid="`file-row-${index}`"
      >
        <div class="file-row-header">
          <strong>{{ item.file.name }}</strong>
          <button type="button" class="btn-close" aria-label="Remove" @click="removeFile(index)">&times;</button>
        </div>
        <div class="file-row-fields">
          <input
            v-model="item.title"
            type="text"
            placeholder="Book Title"
            required
            :data-testid="`file-title-input-${index}`"
          />
          <input
            v-model="item.author"
            type="text"
            placeholder="Author (optional)"
            :data-testid="`file-author-input-${index}`"
          />
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
          <CategoryPicker
            id="default-categories"
            v-model="defaults.categories"
            :options="availableCategories"
            placeholder="Fiction, Fantasy, Adventure…"
          />
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
      <p
        v-if="feedback"
        class="alert"
        :class="`alert-${feedbackVariant === 'danger' ? 'danger' : feedbackVariant === 'success' ? 'success' : 'info'}`"
        data-testid="upload-feedback"
      >
        {{ feedback }}
      </p>
    </div>

    <div class="page-actions">
      <button
        type="button"
        class="btn btn-primary"
        :disabled="uploading || !pendingFiles.length"
        data-testid="upload-button"
        @click="handleUpload"
      >
        {{ uploading ? 'Uploading…' : 'Upload All Files' }}
      </button>
    </div>
  </div>
</template>

<style scoped src="@/css/views/admin/UploadPdf.css"></style>
