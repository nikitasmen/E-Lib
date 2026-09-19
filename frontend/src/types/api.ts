export interface ApiSuccess<T> {
  status: 'success'
  data: T
}

export interface ApiError {
  status: 'error'
  message: string | Record<string, string[]>
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError

export function isApiSuccess<T>(response: ApiResponse<T>): response is ApiSuccess<T> {
  return response.status === 'success'
}

/** Extracts a human-readable message from an ApiError's `message` field. */
export function apiErrorMessage(error: ApiError, fallback = 'Something went wrong'): string {
  if (typeof error.message === 'string') {
    return error.message
  }
  if (error.message && typeof error.message === 'object') {
    return Object.values(error.message).flat().join(' ')
  }
  return fallback
}
