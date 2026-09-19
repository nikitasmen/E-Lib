import { onMounted, ref } from 'vue'
import { getCategories } from '@/api/books'
import { isApiSuccess } from '@/types/api'

/** Distinct categories already in use, for the category picker's suggestions. */
export function useCategoryOptions() {
  const categories = ref<string[]>([])

  onMounted(async () => {
    try {
      const response = await getCategories()
      if (isApiSuccess(response.data)) categories.value = response.data.data
    } catch {
      // A failed lookup just means fewer suggestions — typing a new category still works.
    }
  })

  return categories
}
