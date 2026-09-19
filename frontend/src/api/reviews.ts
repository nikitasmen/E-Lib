import client from './client'
import type { ApiResponse } from '@/types/api'
import type { Review } from '@/types/review'

export function getReviews(bookId: string) {
  return client.get<ApiResponse<Review[]>>(`/v1/reviews/${bookId}`)
}

export function addReview(bookId: string, rating: number, comment: string) {
  return client.post<ApiResponse<string>>('/v1/reviews', { book_id: bookId, rating, comment })
}
