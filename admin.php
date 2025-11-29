<?php

require("config.php");
require("classes/User.php");
require("classes/Subcategory.php");
session_start();
$action = isset($_GET['action']) ? $_GET['action'] : "";
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

if ($action != "login" && $action != "logout" && !$username) {
    login();
    exit;
}

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'newArticle':
        newArticle();
        break;
    case 'editArticle':
        editArticle();
        break;
    case 'deleteArticle':
        deleteArticle();
        break;
    case 'listCategories':
        listCategories();
        break;
    case 'newCategory':
        newCategory();
        break;
    case 'editCategory':
        editCategory();
        break;
    case 'deleteCategory':
        deleteCategory();
        break;
    case 'listSubcategories':
        listSubcategories();
        break;
    case 'newSubcategory':
        newSubcategory();
        break;
    case 'editSubcategory':
        editSubcategory();
        break;
    case 'deleteSubcategory':
        deleteSubcategory();
        break;
    case 'listUsers':
        listUsers();
        break;
    case 'newUser':
        newUser();
        break;
    case 'editUser':
        editUser();
        break;
    case 'deleteUser':
        deleteUser();
        break;
    default:
        listArticles();
}

/**
 * Авторизация пользователя (админа) -- установка значения в сессию
 */
function login() {
    $results = array();
    $results['pageTitle'] = "Admin Login | Widget News";

    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Если логин admin, используем старую проверку
        if ($username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
            $_SESSION['username'] = ADMIN_USERNAME;
            $_SESSION['is_admin'] = true;
            header("Location: admin.php");
        } 
        // Для других пользователей проверяем через БД
        else {
            $user = User::checkLogin($username, $password);
            if ($user) {
                $_SESSION['username'] = $user->login;
                $_SESSION['is_admin'] = false;
                $_SESSION['user_id'] = $user->id;
                header("Location: admin.php");
            } else {
                // Проверим почему не вошел
                $dbUser = User::getByLogin($username);
                if ($dbUser && !$dbUser->active) {
                    $results['errorMessage'] = "Пользователь неактивен. Обратитесь к администратору.";
                } else {
                    $results['errorMessage'] = "Неправильный логин или пароль, попробуйте ещё раз.";
                }
                require(TEMPLATE_PATH . "/admin/loginForm.php");
            }
        }
    } else {
        require(TEMPLATE_PATH . "/admin/loginForm.php");
    }
}

function logout() {
    unset($_SESSION['username']);
    unset($_SESSION['is_admin']);
    unset($_SESSION['user_id']);
    header("Location: admin.php");
}

function newArticle() {
    $results = array();
    $results['pageTitle'] = "New Article";
    $results['formAction'] = "newArticle";
    $results['errors'] = array();

    if (isset($_POST['saveChanges'])) {
        $article = new Article();
        $article->storeFormValues($_POST);
        
        // Валидация категории и подкатегории
        if ($article->subcategoryId) {
            $subcategory = Subcategory::getById($article->subcategoryId);
            if ($subcategory && $subcategory->categoryId != $article->categoryId) {
                $results['errors'][] = "Ошибка: Выбранная категория не соответствует подкатегории!";
            }
        }
        
        // ЕСЛИ ЕСТЬ ОШИБКИ - показываем форму снова с сохраненными данными
        if (!empty($results['errors'])) {
            $results['article'] = $article;
        } else {
            // Если ошибок нет - сохраняем
            $article->insert();
            
            // Сохраняем авторов
            if (isset($_POST['authors']) && is_array($_POST['authors'])) {
                $article->setAuthors($_POST['authors']);
            }
            
            header("Location: admin.php?status=changesSaved");
            return;
        }
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php");
        return;
    } else {
        $results['article'] = new Article;
    }
    
    // Загружаем данные для формы
    $data = Category::getList();
    $results['categories'] = $data['results'];
    
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ($subcategoriesData as $subcategory) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    // Загружаем список пользователей для выбора авторов
    $usersData = User::getList();
    $results['users'] = $usersData['results'];
    
    require(TEMPLATE_PATH . "/admin/editArticle.php");
}

/**
 * Редактирование статьи
 */
function editArticle() {
    $results = array();
    $results['pageTitle'] = "Edit Article";
    $results['formAction'] = "editArticle";
    $results['errors'] = array();

    if (isset($_POST['saveChanges'])) {
        if (!$article = Article::getById((int)$_POST['articleId'])) {
            header("Location: admin.php?error=articleNotFound");
            return;
        }
        
        $article->storeFormValues($_POST);
        
        // Валидация категории и подкатегории
        if ($article->subcategoryId) {
            $subcategory = Subcategory::getById($article->subcategoryId);
            if ($subcategory && $subcategory->categoryId != $article->categoryId) {
                $results['errors'][] = "Ошибка: Выбранная категория не соответствует подкатегории!";
            }
        }
        
        // ЕСЛИ ЕСТЬ ОШИБКИ - показываем форму снова с сохраненными данными
        if (!empty($results['errors'])) {
            $results['article'] = $article;
        } else {
            // Если ошибок нет - сохраняем
            $article->update();
            
            // Сохраняем авторов
            if (isset($_POST['authors']) && is_array($_POST['authors'])) {
                $article->setAuthors($_POST['authors']);
            } else {
                // Если авторы не выбраны, очищаем связь
                $article->setAuthors(array());
            }
            
            header("Location: admin.php?status=changesSaved");
            return;
        }
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php");
        return;
    } else {
        $results['article'] = Article::getById((int)$_GET['articleId']);
        if (!$results['article']) {
            header("Location: admin.php?error=articleNotFound");
            return;
        }
    }
    
    // Загружаем данные для формы
    $data = Category::getList();
    $results['categories'] = $data['results'];
    
    $subcategoriesData = Subcategory::getAll();
    $results['subcategories'] = array();
    foreach ($subcategoriesData as $subcategory) {
        $results['subcategories'][$subcategory->id] = $subcategory;
    }
    
    // Загружаем список пользователей для выбора авторов
    $usersData = User::getList();
    $results['users'] = $usersData['results'];
    
    // Загружаем текущих авторов статьи
    if ($results['article']->id) {
        $results['currentAuthorIds'] = $results['article']->getAuthorIds();
    } else {
        $results['currentAuthorIds'] = array();
    }
    
    require(TEMPLATE_PATH . "/admin/editArticle.php");
}

function deleteArticle() {
    if (!$article = Article::getById((int)$_GET['articleId'])) {
        header("Location: admin.php?error=articleNotFound");
        return;
    }
    $article->delete();
    header("Location: admin.php?status=articleDeleted");
}

function listArticles() {
    $results = array();
    
    $data = Article::getList(1000000, null, null, "publicationDate DESC", false);
    $results['articles'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    
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
    
    $results['pageTitle'] = "Все статьи";

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "articleNotFound") 
            $results['errorMessage'] = "Error: Article not found.";
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "articleDeleted")  {
            $results['statusMessage'] = "Article deleted.";
        }
    }

    require(TEMPLATE_PATH . "/admin/listArticles.php");
}

function listCategories() {
    $results = array();
    $data = Category::getList();
    $results['categories'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Article Categories";

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "categoryNotFound") 
            $results['errorMessage'] = "Error: Category not found.";
        if ($_GET['error'] == "categoryContainsArticles") 
            $results['errorMessage'] = "Error: Category contains articles. Delete the articles, or assign them to another category, before deleting this category.";
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "categoryDeleted")  {
            $results['statusMessage'] = "Category deleted.";
        }
    }

    require(TEMPLATE_PATH . "/admin/listCategories.php");
}

function newCategory() {
    $results = array();
    $results['pageTitle'] = "New Article Category";
    $results['formAction'] = "newCategory";

    if (isset($_POST['saveChanges'])) {
        $category = new Category;
        $category->storeFormValues($_POST);
        $category->insert();
        header("Location: admin.php?action=listCategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listCategories");
    } else {
        $results['category'] = new Category;
        require(TEMPLATE_PATH . "/admin/editCategory.php");
    }
}

function editCategory() {
    $results = array();
    $results['pageTitle'] = "Edit Article Category";
    $results['formAction'] = "editCategory";

    if (isset($_POST['saveChanges'])) {
        if (!$category = Category::getById((int)$_POST['categoryId'])) {
            header("Location: admin.php?action=listCategories&error=categoryNotFound");
            return;
        }
        $category->storeFormValues($_POST);
        $category->update();
        header("Location: admin.php?action=listCategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listCategories");
    } else {
        $results['category'] = Category::getById((int)$_GET['categoryId']);
        require(TEMPLATE_PATH . "/admin/editCategory.php");
    }
}

function deleteCategory() {
    if (!$category = Category::getById((int)$_GET['categoryId'])) {
        header("Location: admin.php?action=listCategories&error=categoryNotFound");
        return;
    }
    $articles = Article::getList(1000000, $category->id);
    if ($articles['totalRows'] > 0) {
        header("Location: admin.php?action=listCategories&error=categoryContainsArticles");
        return;
    }
    $category->delete();
    header("Location: admin.php?action=listCategories&status=categoryDeleted");
}

function listSubcategories() {
    $results = array();
    $data = Subcategory::getList();
    $results['subcategories'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Article Subcategories";

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "subcategoryNotFound") 
            $results['errorMessage'] = "Error: Subcategory not found.";
        if ($_GET['error'] == "subcategoryContainsArticles") 
            $results['errorMessage'] = "Error: Subcategory contains articles. Delete the articles, or assign them to another subcategory, before deleting this subcategory.";
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "subcategoryDeleted")  {
            $results['statusMessage'] = "Subcategory deleted.";
        }
    }

    require(TEMPLATE_PATH . "/admin/listSubcategory.php");
}

function newSubcategory() {
    $results = array();
    $results['pageTitle'] = "New Article Subcategory";
    $results['formAction'] = "newSubcategory";

    if (isset($_POST['saveChanges'])) {
        $subcategory = new Subcategory;
        $subcategory->storeFormValues($_POST);
        $subcategory->insert();
        header("Location: admin.php?action=listSubcategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listSubcategories");
    } else {
        $results['subcategory'] = new Subcategory;
        $data = Category::getList();
        $results['categories'] = $data['results'];
        require(TEMPLATE_PATH . "/admin/editSubcategory.php");
    }
}

function editSubcategory() {
    $results = array();
    $results['pageTitle'] = "Edit Article Subcategory";
    $results['formAction'] = "editSubcategory";

    if (isset($_POST['saveChanges'])) {
        if (!$subcategory = Subcategory::getById((int)$_POST['subcategoryId'])) {
            header("Location: admin.php?action=listSubcategories&error=subcategoryNotFound");
            return;
        }
        $subcategory->storeFormValues($_POST);
        $subcategory->update();
        header("Location: admin.php?action=listSubcategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listSubcategories");
    } else {
        $results['subcategory'] = Subcategory::getById((int)$_GET['subcategoryId']);
        $data = Category::getList();
        $results['categories'] = $data['results'];
        require(TEMPLATE_PATH . "/admin/editSubcategory.php");
    }
}

function deleteSubcategory() {
    if (!$subcategory = Subcategory::getById((int)$_GET['subcategoryId'])) {
        header("Location: admin.php?action=listSubcategories&error=subcategoryNotFound");
        return;
    }
    
    $articles = Article::getList(1000000, null, $subcategory->id);
    if ($articles['totalRows'] > 0) {
        header("Location: admin.php?action=listSubcategories&error=subcategoryContainsArticles");
        return;
    }
    
    $subcategory->delete();
    header("Location: admin.php?action=listSubcategories&status=subcategoryDeleted");
}

function listUsers() {
    $results = array();
    $data = User::getList();
    $results['users'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Все пользователи";

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "userNotFound") 
            $results['errorMessage'] = "Error: User not found.";
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "userDeleted")  {
            $results['statusMessage'] = "User deleted.";
        }
    }

    require(TEMPLATE_PATH . "/admin/listUsers.php");
}

function newUser() {
    $results = array();
    $results['pageTitle'] = "New User";
    $results['formAction'] = "newUser";

    if (isset($_POST['saveChanges'])) {
        $user = new User();
        $user->storeFormValues($_POST);
        $user->insert();
        header("Location: admin.php?action=listUsers&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listUsers");
    } else {
        $results['user'] = new User();
        require(TEMPLATE_PATH . "/admin/editUser.php");
    }
}

function editUser() {
    $results = array();
    $results['pageTitle'] = "Edit User";
    $results['formAction'] = "editUser";

    if (isset($_POST['saveChanges'])) {
        if (!$user = User::getById((int)$_POST['userId'])) {
            header("Location: admin.php?action=listUsers&error=userNotFound");
            return;
        }
        $user->storeFormValues($_POST);
        $user->update();
        header("Location: admin.php?action=listUsers&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listUsers");
    } else {
        $results['user'] = User::getById((int)$_GET['userId']);
        require(TEMPLATE_PATH . "/admin/editUser.php");
    }
}

function deleteUser() {
    if (!$user = User::getById((int)$_GET['userId'])) {
        header("Location: admin.php?action=listUsers&error=userNotFound");
        return;
    }
    $user->delete();
    header("Location: admin.php?action=listUsers&status=userDeleted");
}