<?php include "templates/include/header.php" ?>
	  
    <h1 style="width: 75%;"><?php echo htmlspecialchars( $results['article']->title )?></h1>
    <div style="width: 75%; font-style: italic;"><?php echo htmlspecialchars( $results['article']->summary )?></div>
    <div style="width: 75%;"><?php echo $results['article']->content?></div>
    <p class="pubDate">Published on <?php  echo date('j F Y', $results['article']->publicationDate)?>
    
    <?php if ( $results['category'] ) { ?>
        in 
        <a href="./?action=archive&amp;categoryId=<?php echo $results['category']->id?>">
            <?php echo htmlspecialchars($results['category']->name) ?>
        </a>
    <?php } ?>
    
    <?php if ( $results['subcategory'] ) { ?>
        | Подкатегория: 
        <a href="./?action=viewArticleSubcategory&amp;subcategoryId=<?php echo $results['subcategory']->id?>">
            <?php echo htmlspecialchars($results['subcategory']->name) ?>
        </a>
    <?php } ?>
    
    <?php 
    // ДОБАВЛЕНО: Отображение авторов
    $authors = $results['article']->getAuthors();
    if (!empty($authors)) { 
        $authorLinks = array();
        foreach ($authors as $author) {
            if (is_array($author) && isset($author['login'])) {
                $authorLinks[] = '<a href="./?action=viewArticleAuthor&amp;authorId=' . $author['id'] . '">' . 
                               htmlspecialchars($author['login']) . '</a>';
            } elseif (is_object($author) && isset($author->login)) {
                $authorLinks[] = '<a href="./?action=viewArticleAuthor&amp;authorId=' . $author->id . '">' . 
                               htmlspecialchars($author->login) . '</a>';
            }
        }
        if (!empty($authorLinks)) { ?>
            | Авторы: <?php echo implode(', ', $authorLinks) ?>
        <?php } 
    } ?>
        
    </p>

    <p><a href="./">Вернуться на главную страницу</a></p>
	  
<?php include "templates/include/footer.php" ?>