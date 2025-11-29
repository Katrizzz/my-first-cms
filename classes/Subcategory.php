<?php

/**
 * Класс для обработки подкатегорий
 */
class Subcategory
{
    public $id = null;
    public $name = null;
    public $categoryId = null; // для удобства в коде
    public $createdAt = null;
    public $categoryName = null;

    public function __construct($data = array())
    {
        if (isset($data['id'])) $this->id = (int) $data['id'];
        if (isset($data['name'])) $this->name = $data['name'];
        
        // Обрабатываем оба варианта
        if (isset($data['category_id'])) {
            $this->categoryId = (int) $data['category_id'];
        }
        if (isset($data['categoryId'])) {
            $this->categoryId = (int) $data['categoryId'];
        }
        
        if (isset($data['created_at'])) $this->createdAt = $data['created_at'];
        if (isset($data['category_name'])) $this->categoryName = $data['category_name'];
    }

    public static function getById($id)
    {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT s.*, c.name as category_name 
                FROM subcategories s 
                LEFT JOIN categories c ON s.category_id = c.id 
                WHERE s.id = :id";
        $st = $conn->prepare($sql);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        $conn = null;

        if ($row) return new Subcategory($row);
        return false;
    }

    public static function getAll()
    {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT s.*, c.name as category_name 
                FROM subcategories s 
                LEFT JOIN categories c ON s.category_id = c.id 
                ORDER BY c.name, s.name";
        $st = $conn->prepare($sql);
        $st->execute();
        $list = array();

        while ($row = $st->fetch()) {
            $list[] = new Subcategory($row);
        }

        $conn = null;
        return $list;
    }

    public static function getByCategory($categoryId)
    {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT * FROM subcategories WHERE category_id = :category_id ORDER BY name";
        $st = $conn->prepare($sql);
        $st->bindValue(":category_id", $categoryId, PDO::PARAM_INT);
        $st->execute();
        $list = array();

        while ($row = $st->fetch()) {
            $list[] = new Subcategory($row);
        }

        $conn = null;
        return $list;
    }

    public function insert()
    {
        if (!is_null($this->id)) trigger_error("Subcategory::insert(): Attempt to insert an object that already has its ID set.", E_USER_ERROR);

        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "INSERT INTO subcategories (name, category_id) VALUES (:name, :category_id)";
        $st = $conn->prepare($sql);
        $st->bindValue(":name", $this->name, PDO::PARAM_STR);
        $st->bindValue(":category_id", $this->categoryId, PDO::PARAM_INT); // Используем categoryId
        $st->execute();
        $this->id = $conn->lastInsertId();
        $conn = null;
    }

    public function update()
    {
        if (is_null($this->id)) trigger_error("Subcategory::update(): Attempt to update an object that does not have its ID set.", E_USER_ERROR);

        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "UPDATE subcategories SET name = :name, category_id = :category_id WHERE id = :id";
        $st = $conn->prepare($sql);
        $st->bindValue(":name", $this->name, PDO::PARAM_STR);
        $st->bindValue(":category_id", $this->categoryId, PDO::PARAM_INT); // Используем categoryId
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
        $conn = null;
    }

    public function delete()
    {
        if (is_null($this->id)) trigger_error("Subcategory::delete(): Attempt to delete an object that does not have its ID set.", E_USER_ERROR);

        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $st = $conn->prepare("DELETE FROM subcategories WHERE id = :id LIMIT 1");
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
        $conn = null;
    }

    public static function getList($numRows = 1000000)
    {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT s.*, c.name as category_name 
            FROM subcategories s 
            LEFT JOIN categories c ON s.category_id = c.id 
            ORDER BY c.name, s.name 
            LIMIT :numRows";

        $st = $conn->prepare($sql);
        $st->bindValue(":numRows", $numRows, PDO::PARAM_INT);
        $st->execute();

        $list = array();
        while ($row = $st->fetch()) {
            $subcategory = new Subcategory($row);
            $subcategory->categoryName = $row['category_name'];
            $list[] = $subcategory;
        }

        $sql = "SELECT COUNT(*) FROM subcategories";
        $totalRows = $conn->query($sql)->fetch();
        $conn = null;

        return array(
            "results" => $list,
            "totalRows" => $totalRows[0]
        );
    }

    public function storeFormValues($params)
    {
        if (isset($params['name'])) $this->name = $params['name'];
        if (isset($params['categoryId'])) $this->categoryId = (int)$params['categoryId'];
    }
}
