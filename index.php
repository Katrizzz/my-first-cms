<?php

require("config.php");

try {
    initApplication();
} catch (Exception $e) { 
    $results['errorMessage'] = $e->getMessage();
    require(TEMPLATE_PATH . "/viewErrorPage.php");
}


function initApplication()
{
    $action = isset($_GET['action']) ? $_GET['action'] : "";

    switch ($action) {
        case 'archive':
          archive();
          break;
        case 'viewArticle':
          viewArticle();
          break;
        case 'viewArticleSubcategory':
          viewArticleSubcategory();
          break;
        case 'viewArticleAuthor': // ДОБАВЛЕНО: обработка страницы автора
          viewArticleAuthor();
          break;
        default:
          homepage();
    }
}

function archive() 
{
    $results = [];
    
    $categoryId = ( isset( $_GET['categoryId'] ) && $_GET['categoryId'] ) ? (int)$_GET['categoryId'] : null;
    $subcategoryId = ( isset( $_GET['subcategoryId'] ) && $_GET['subcategoryId'] ) ? (int)$_GET['subcategoryId'] : null;
    
    $results['category'] = Category::getById( $categoryId );
    $results['subcategory'] = Subcategory::getById( $subcategoryId );
    
    // ИЗМЕНЕНИЕ: добавлена поддержка подкатегорий
    $data = Article::getList( 100000, 
                             $results['category'] ? $results['category']->id : null,
                             $results['subcategory'] ? $results['subcategory']->id : null,
                             "publicationDate DESC", true );
    
    $results['articles'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    
    $data = Category::getList();
    $results['categories'] = array();
    foreach ( $data['results'] as $category ) {
        $results['categories'][$category->id] = $category;
    }
    
    // ДОБАВИТЬ: загрузка подкатегорий
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ( $subcategoriesData as $subcategory ) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    // ИЗМЕНЕНИЕ: обновлен заголовок для подкатегорий
    if ($results['subcategory']) {
        $results['pageHeading'] = $results['subcategory']->name;
    } elseif ($results['category']) {
        $results['pageHeading'] = $results['category']->name;
    } else {
        $results['pageHeading'] = "Article Archive";
    }
    
    $results['pageTitle'] = $results['pageHeading'] . " | Widget News";
    
    require( TEMPLATE_PATH . "/archive.php" );
}

/**
 * Загрузка страницы с конкретной статьёй
 * 
 * @return null
 */
function viewArticle() 
{   
    if ( !isset($_GET["articleId"]) || !$_GET["articleId"] ) {
      homepage();
      return;
    }

    $results = array();
    $articleId = (int)$_GET["articleId"];
    $results['article'] = Article::getById($articleId);
    
    // ИЗМЕНЕНИЕ: проверка активности статьи
    if (!$results['article']) {
        throw new Exception("Статья с id = $articleId не найдена");
    }
    
    // Дополнительная проверка: если статья неактивна, показываем ошибку
    if (!$results['article']->active) {
        throw new Exception("Статья с id = $articleId временно недоступна");
    }
    
    // ДОБАВЛЕНО: Загружаем категории для отображения в шаблоне
    $data = Category::getList();
    $results['categories'] = array();
    foreach ($data['results'] as $category) {
        $results['categories'][$category->id] = $category;
    }
    
    // ДОБАВЛЕНО: Загружаем подкатегории для отображения в шаблоне
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ($subcategoriesData as $subcategory) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    $results['category'] = Category::getById($results['article']->categoryId);
    $results['subcategory'] = Subcategory::getById($results['article']->subcategoryId);
    $results['pageTitle'] = $results['article']->title . " | Простая CMS";
    
    require(TEMPLATE_PATH . "/viewArticle.php");
}

/**
 * Вывод домашней ("главной") страницы сайта
 */
function homepage() 
{
    $results = array();
    
    // ИЗМЕНЕНИЕ: добавлен четвертый параметр true - только активные статьи
    $data = Article::getList(HOMEPAGE_NUM_ARTICLES, null, null, "publicationDate DESC", true);
    $results['articles'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    
    $data = Category::getList();
    $results['categories'] = array();
    foreach ( $data['results'] as $category ) { 
        $results['categories'][$category->id] = $category;
    }
    
    // ДОБАВИТЬ: загрузка подкатегорий для главной страницы
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ($subcategoriesData as $subcategory) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    $results['pageTitle'] = "Простая CMS на PHP";
    
    require(TEMPLATE_PATH . "/homepage.php");
}

/**
 * НОВАЯ ФУНКЦИЯ: Загрузка страницы подкатегории
 */
function viewArticleSubcategory() 
{
    $results = [];
    
    if (!isset($_GET["subcategoryId"]) || !$_GET["subcategoryId"]) {
        homepage();
        return;
    }

    $subcategoryId = (int)$_GET["subcategoryId"];
    $results['subcategory'] = Subcategory::getById($subcategoryId);
    
    if (!$results['subcategory']) {
        throw new Exception("Подкатегория с id = $subcategoryId не найдена");
    }
    
    $data = Article::getList(100000, null, $subcategoryId, "publicationDate DESC", true);
    $results['articles'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    
    // Загрузка категорий и подкатегорий для навигации
    $data = Category::getList();
    $results['categories'] = array();
    foreach ($data['results'] as $category) {
        $results['categories'][$category->id] = $category;
    }
    
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ($subcategoriesData as $subcategory) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    $results['pageHeading'] = $results['subcategory']->name;
    $results['pageTitle'] = $results['subcategory']->name . " | Widget News";
    
    require(TEMPLATE_PATH . "/viewArticleSubcategory.php");
}

/**
 * НОВАЯ ФУНКЦИЯ: Загрузка страницы автора
 */
function viewArticleAuthor() 
{
    $results = [];
    
    if (!isset($_GET["authorId"]) || !$_GET["authorId"]) {
        homepage();
        return;
    }

    $authorId = (int)$_GET["authorId"];
    $results['author'] = User::getById($authorId);
    
    if (!$results['author']) {
        throw new Exception("Автор с id = $authorId не найден");
    }
    
    // Получаем все статьи и фильтруем по автору
    $data = Article::getList(100000, null, null, "publicationDate DESC", true);
    $results['articles'] = array();
    
    foreach ($data['results'] as $article) {
        foreach ($article->authors as $author) {
            if ($author['id'] == $authorId) {
                $results['articles'][] = $article;
                break;
            }
        }
    }
    
    $results['totalRows'] = count($results['articles']);
    $results['pageHeading'] = "Articles by " . $results['author']->login;
    $results['pageTitle'] = $results['pageHeading'] . " | Widget News";
    
    require(TEMPLATE_PATH . "/viewArticleAuthor.php");
}

?>