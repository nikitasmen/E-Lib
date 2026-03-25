<?php
namespace App\Helpers\Database;

/**
 * MongoDB helper utility to provide safe creation of MongoDB specific objects
 */
class MongoHelper {
    
    /**
     * Safely create an ObjectId from a string
     * 
     * @param string $id The ID string
     * @return \MongoDB\BSON\ObjectId|string The ObjectId or the original string if MongoDB extension not available
     */
    public static function createObjectId($id) {
        if (class_exists('\MongoDB\BSON\ObjectId')) {
            try {
                return new \MongoDB\BSON\ObjectId($id);
            } catch (\Exception $e) {
                error_log("Error creating ObjectId: " . $e->getMessage());
                return $id;
            }
        }
        return $id;
    }
    
    /**
     * Safely create a MongoDB Regex object
     * 
     * @param string $pattern The regex pattern
     * @param string $flags Regex flags (e.g., 'i' for case-insensitive)
     * @return \MongoDB\BSON\Regex|array A MongoDB Regex object or an array simulating one
     */
    public static function createRegex($pattern, $flags = '') {
        if (class_exists('\MongoDB\BSON\Regex')) {
            try {
                return new \MongoDB\BSON\Regex($pattern, $flags);
            } catch (\Exception $e) {
                error_log("Error creating Regex: " . $e->getMessage());
                return ['$regex' => $pattern, '$options' => $flags];
            }
        }
        return ['$regex' => $pattern, '$options' => $flags];
    }
    
    /**
     * Safely create a MongoDB UTC DateTime
     * 
     * @param int|null $milliseconds Milliseconds since epoch, or null for current time
     * @return \MongoDB\BSON\UTCDateTime|int A MongoDB UTCDateTime object or timestamp
     */
    public static function createUTCDateTime($milliseconds = null) {
        if ($milliseconds === null) {
            $milliseconds = floor(microtime(true) * 1000);
        }
        
        if (class_exists('\MongoDB\BSON\UTCDateTime')) {
            try {
                return new \MongoDB\BSON\UTCDateTime($milliseconds);
            } catch (\Exception $e) {
                error_log("Error creating UTCDateTime: " . $e->getMessage());
                return $milliseconds;
            }
        }
        return $milliseconds;
    }
}
