<?php


/**
 * Класс для обработки статей
 */
class Article
{
    // Свойства
    /**
    * @var int ID статей из базы данны
    */
    public $id = null;

    /**
    * @var int Дата первой публикации статьи
    */
    public $publicationDate = null;

    /**
    * @var string Полное название статьи
    */
    public $title = null;

     /**
    * @var int ID категории статьи
    */
    public $categoryId = null;

    /**
    * @var string Краткое описание статьи
    */
    public $summary = null;

    /**
    * @var string HTML содержание статьи
    */
    public $content = null;

    /*
    * @var bool Активность статьи
    */
    public $active = 1;
    
    public $subcategoryId = null;

    public $firstchars = null;

    public $authors = array();



    /**
     * Создаст объект статьи
     * 
     * @param array $data массив значений (столбцов) строки таблицы статей
     */
    public function __construct($data=array())
    {
        
      if (isset($data['id'])) {
          $this->id = (int) $data['id'];
      }
      
      if (isset( $data['publicationDate'])) {
          $this->publicationDate = (string) $data['publicationDate'];     
      }

      //die(print_r($this->publicationDate));

      if (isset($data['title'])) {
          $this->title = $data['title'];        
      }

      if (isset($data['categoryId'])) {
            $this->categoryId = (int) $data['categoryId'];
        } elseif (isset($data['category_id'])) {
            $this->categoryId = (int) $data['category_id'];
        }
      
        if (isset($data['subcategoryId'])) {
            $this->subcategoryId = $data['subcategoryId'] !== null ? (int) $data['subcategoryId'] : null;
        } elseif (isset($data['subcategory_id'])) {
            $this->subcategoryId = $data['subcategory_id'] !== null ? (int) $data['subcategory_id'] : null;
        }
      
      if (isset($data['summary'])) {
          $this->summary = $data['summary'];         
      }
      
      if (isset($data['content'])) {
          $this->content = $data['content'];  
      }

      $this->active = isset($data['active']) ? (int)$data['active'] : 1;
   
      $this->authors = array();
    }


    /**
    * Устанавливаем свойства с помощью значений формы редактирования записи в заданном массиве
    *
    * @param assoc Значения записи формы
    */
    public function storeFormValues ( $params ) {

      // Сохраняем все параметры
      $this->__construct( $params );

      // Разбираем и сохраняем дату публикации
      if ( isset($params['publicationDate']) ) {
        $publicationDate = explode ( '-', $params['publicationDate'] );

        if ( count($publicationDate) == 3 ) {
          list ( $y, $m, $d ) = $publicationDate;
          $this->publicationDate = mktime ( 0, 0, 0, $m, $d, $y );
        }
      }

      // Обрабатываем checkbox для активности статьи
        $this->active = isset($params['active']) ? 1 : 0;

        // Обрабатываем подкатегорию (может быть пустой)
        $this->subcategoryId = isset($params['subcategoryId']) && $params['subcategoryId'] !== ''
            ? (int)$params['subcategoryId']
            : null;

        // Обрабатываем авторов
        if (isset($params['authors']) && is_array($params['authors'])) {
            $this->authors = $params['authors'];
        }
    }


    /**
    * Возвращаем объект статьи соответствующий заданному ID статьи
    *
    * @param int ID статьи
    * @return Article|false Объект статьи или false, если запись не найдена или возникли проблемы
    */
    public static function getById($id) {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT *, UNIX_TIMESTAMP(publicationDate) "
            . "AS publicationDate FROM articles WHERE id = :id";
        $st = $conn->prepare($sql);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();

        $row = $st->fetch();
        $conn = null;

        if ($row) {
            $article = new Article($row);
            // Загружаем авторов статьи
            $article->loadAuthors();
            return $article;
        }
        return false;
    }


    /**
    * Возвращает все (или диапазон) объекты Article из базы данных
    *
    * @param int $numRows Количество возвращаемых строк (по умолчанию = 1000000)
    * @param int $categoryId Вернуть статьи только из категории с указанным ID
    * @param string $order Столбец, по которому выполняется сортировка статей (по умолчанию = "publicationDate DESC")
    * @return Array|false Двух элементный массив: results => массив объектов Article; totalRows => общее количество строк
    */
    public static function getList(
        $numRows = 1000000,
        $categoryId = null,
        $subcategoryId = null,
        $order = "publicationDate DESC",
        $onlyActive = true
    ) {
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $fromPart = "FROM articles";

        // Формируем условия WHERE
        $whereConditions = array();
        if ($categoryId) {
            $whereConditions[] = "categoryId = :categoryId";
        }
        if ($subcategoryId) {
            $whereConditions[] = "subcategoryId = :subcategoryId";
        }
        if ($onlyActive) {
            $whereConditions[] = "active = 1";
        }

        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        }

        $sql = "SELECT *, UNIX_TIMESTAMP(publicationDate) 
                AS publicationDate
                $fromPart $whereClause
                ORDER BY  $order  LIMIT :numRows";

        $st = $conn->prepare($sql);
        $st->bindValue(":numRows", $numRows, PDO::PARAM_INT);

        if ($categoryId) {
            $st->bindValue(":categoryId", $categoryId, PDO::PARAM_INT);
        }
        if ($subcategoryId) {
            $st->bindValue(":subcategoryId", $subcategoryId, PDO::PARAM_INT);
        }

        $st->execute();
        $list = array();

        while ($row = $st->fetch()) {
            $article = new Article($row);
            // Загружаем авторов для каждой статьи
            $article->loadAuthors();
            $list[] = $article;
        }

        // Получаем общее количество статей
        $sql = "SELECT COUNT(*) AS totalRows $fromPart $whereClause";
        $st = $conn->prepare($sql);
        if ($categoryId) {
            $st->bindValue(":categoryId", $categoryId, PDO::PARAM_INT);
        }
        if ($subcategoryId) {
            $st->bindValue(":subcategoryId", $subcategoryId, PDO::PARAM_INT);
        }
        $st->execute();
        $totalRows = $st->fetch();
        $conn = null;

        return array(
            "results" => $list,
            "totalRows" => $totalRows[0]
        );
    }

    /**
    * Вставляем текущий объект Article в базу данных, устанавливаем его ID
    */
    public function insert()
    {
        if (!is_null($this->id)) {
            trigger_error("Article::insert(): Attempt to insert an Article object that already has its ID property set (to $this->id).", E_USER_ERROR);
        }

        // Вставляем статью
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "INSERT INTO articles ( publicationDate, categoryId, subcategoryId, title, summary, content, active ) VALUES ( FROM_UNIXTIME(:publicationDate), :categoryId, :subcategoryId, :title, :summary, :content, :active )";
        $st = $conn->prepare($sql);
        $st->bindValue(":publicationDate", $this->publicationDate, PDO::PARAM_INT);
        $st->bindValue(":categoryId", $this->categoryId, PDO::PARAM_INT);
        $st->bindValue(":subcategoryId", $this->subcategoryId, $this->subcategoryId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(":title", $this->title, PDO::PARAM_STR);
        $st->bindValue(":summary", $this->summary, PDO::PARAM_STR);
        $st->bindValue(":content", $this->content, PDO::PARAM_STR);
        $st->bindValue(":active", $this->active, PDO::PARAM_INT);
        $st->execute();
        $this->id = $conn->lastInsertId();

        // Сохраняем авторов
        $this->saveAuthors();

        $conn = null;
    }

    /**
    * Обновляем текущий объект статьи в базе данных
    */
    public function update()
    {
        if (is_null($this->id)) {
            trigger_error("Article::update(): Attempt to update an Article object that does not have its ID property set.", E_USER_ERROR);
        }

        // Обновляем статью
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "UPDATE articles SET publicationDate=FROM_UNIXTIME(:publicationDate),"
            . " categoryId=:categoryId, subcategoryId=:subcategoryId, title=:title, summary=:summary,"
            . " content=:content, active=:active WHERE id = :id";

        $st = $conn->prepare($sql);
        $st->bindValue(":publicationDate", $this->publicationDate, PDO::PARAM_INT);
        $st->bindValue(":categoryId", $this->categoryId, PDO::PARAM_INT);
        $st->bindValue(":subcategoryId", $this->subcategoryId, $this->subcategoryId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(":title", $this->title, PDO::PARAM_STR);
        $st->bindValue(":summary", $this->summary, PDO::PARAM_STR);
        $st->bindValue(":content", $this->content, PDO::PARAM_STR);
        $st->bindValue(":active", $this->active, PDO::PARAM_INT);
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();

        // Сохраняем авторов
        $this->saveAuthors();

        $conn = null;
    }


    /**
    * Удаляем текущий объект статьи из базы данных
    */
    public function delete()
    {
        if (is_null($this->id)) {
            trigger_error("Article::delete(): Attempt to delete an Article object that does not have its ID property set.", E_USER_ERROR);
        }

        // Удаляем статью
        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

        // Сначала удаляем связи с авторами
        $st = $conn->prepare("DELETE FROM article_authors WHERE article_id = :id");
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();

        // Затем удаляем саму статью
        $st = $conn->prepare("DELETE FROM articles WHERE id = :id LIMIT 1");
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
        $conn = null;
    }

    /**
     * Загружает авторов статьи в свойство $authors
     */
    public function loadAuthors()
    {
        if (is_null($this->id)) return;

        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $sql = "SELECT u.id, u.login 
                FROM users u 
                INNER JOIN article_authors aa ON u.id = aa.user_id 
                WHERE aa.article_id = :articleId
                ORDER BY u.login";
        $st = $conn->prepare($sql);
        $st->bindValue(":articleId", $this->id, PDO::PARAM_INT);
        $st->execute();

        $this->authors = array();
        while ($row = $st->fetch()) {
            $this->authors[] = array(
                'id' => $row['id'],
                'login' => $row['login']
            );
        }

        $conn = null;
    }

    /**
     * Сохраняет авторов статьи из свойства $authors
     */
    public function saveAuthors()
    {
        if (is_null($this->id)) return;

        $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

        // Удаляем старых авторов
        $sql = "DELETE FROM article_authors WHERE article_id = :articleId";
        $st = $conn->prepare($sql);
        $st->bindValue(":articleId", $this->id, PDO::PARAM_INT);
        $st->execute();

        // Добавляем новых авторов
        if (!empty($this->authors) && is_array($this->authors)) {
            $sql = "INSERT INTO article_authors (article_id, user_id) VALUES ";
            $placeholders = array();
            $values = array();

            foreach ($this->authors as $authorId) {
                $placeholders[] = "(?, ?)";
                $values[] = $this->id;
                $values[] = (int)$authorId;
            }

            $sql .= implode(", ", $placeholders);
            $st = $conn->prepare($sql);
            $st->execute($values);
        }

        $conn = null;
    }

    /**
     * Получить список авторов статьи (альтернативный метод)
     * @return array Массив с информацией об авторах
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Получить ID авторов статьи
     * @return array Массив ID авторов
     */
    public function getAuthorIds()
    {
        $authorIds = array();
        foreach ($this->authors as $author) {
            // Проверяем структуру данных автора
            if (is_array($author) && isset($author['id'])) {
                $authorIds[] = $author['id'];
            } elseif (is_numeric($author)) {
                // Если автор представлен просто ID
                $authorIds[] = (int)$author;
            }
        }
        return $authorIds;
    }

    /**
     * Устанавливает авторов для статьи (альтернативный метод)
     * @param array $authorIds Массив ID авторов
     */
    public function setAuthors($authorIds)
    {
        $this->authors = $authorIds;
    }
}
