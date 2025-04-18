<?php
namespace App\Models;

use App\Controllers\DbController;

class Categories {
    private $db;
    private $collection = 'Categories';

    public function __construct() {
        $this->db = DbController::getInstance();
    }

    public function getCategory($id) {
        return $this->db->findOne($this->collection, ['id' => $id]);
    }

    public function getCategoryId($name) {
        $category = $this->db->findOne($this->collection, ['name' => $name]);
        return $category ? $category['id'] : null;
    }

    public function addCategory($category_id) {
        // Check if the category already exists
        $existing = $this->db->findOne($this->collection, ['id' => $category_id]);

        if ($existing) {
            return $existing['id'];
        } else {
            $this->db->insert($this->collection, ['id' => $category_id]);
            return $category_id;
        }
    }

    public function deleteCategory($id) {
        return $this->db->delete($this->collection, ['id' => $id]);
    }
    public function getAllCategories() {
        return $this->db->find($this->collection);
    }

    public function updateCategory($id, $data) {
        return $this->db->update($this->collection, ['id' => $id], $data);
    }
}