<?php

namespace App\Helpers;

/**
 * Normalizes book presentation for UI/API (thumbnail URLs, IDs).
 */
class BookDisplayHelper
{
    public static function bookIdString(array $book): string
    {
        $id = $book['_id'] ?? null;
        if ($id instanceof \MongoDB\BSON\ObjectId) {
            return (string) $id;
        }
        if (is_array($id) && isset($id['$oid'])) {
            return (string) $id['$oid'];
        }
        return is_string($id) ? $id : '';
    }

    /**
     * Thumbnail URL that goes through PHP so legacy files (wrong disk path) still resolve.
     */
    public static function thumbnailUrl(array $book): string
    {
        $id = self::bookIdString($book);
        if ($id === '') {
            return '/assets/uploads/thumbnails/placeholder-book.jpg';
        }
        return '/api/v1/books/' . $id . '/thumbnail';
    }

    public static function applyThumbnailForApi(array &$book): void
    {
        $book['thumbnail'] = self::thumbnailUrl($book);
    }
}
