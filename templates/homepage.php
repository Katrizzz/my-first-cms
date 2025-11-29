<?php include "templates/include/header.php" ?>
    <ul id="headlines">
    <?php foreach ($results['articles'] as $article) { ?>
        <li class='<?php echo $article->id?>'>
            <h2>
                <span class="pubDate">
                    <?php echo date('j F', $article->publicationDate)?>
                </span>
                
                <a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>">
                    <?php echo htmlspecialchars( $article->title )?>
                </a>
                
                <?php if (isset($article->categoryId)) { ?>
                    <span class="category">
                        in 
                        <a href=".?action=archive&amp;categoryId=<?php echo $article->categoryId?>">
                            <?php echo htmlspecialchars($results['categories'][$article->categoryId]->name )?>
                        </a>
                    </span>
                <?php } 
                else { ?>
                    <span class="category">
                        <?php echo "Без категории"?>
                    </span>
                <?php } ?>
                
                <!-- ВЫВОД ПОДКАТЕГОРИИ -->
                <?php if (isset($article->subcategoryId) && isset($results['subcategories'][$article->subcategoryId])) { ?>
                    <span class="subcategory">
                        / 
                        <a href=".?action=archive&amp;subcategoryId=<?php echo $article->subcategoryId?>">
                            <?php echo htmlspecialchars($results['subcategories'][$article->subcategoryId]->name )?>
                        </a>
                    </span>
                <?php } ?>
            </h2>

            <!-- ДОБАВИТЬ ВЫВОД АВТОРОВ -->
            <?php if (!empty($article->authors)) { ?>
                <div class="authors">
                    Authors: 
                    <?php 
                    $authorLinks = array();
                    foreach ($article->authors as $author) {
                        $authorLinks[] = '<a href=".?action=viewArticleAuthor&amp;authorId=' . $author['id'] . '">' . 
                                       htmlspecialchars($author['login']) . '</a>';
                    }
                    echo implode(', ', $authorLinks);
                    ?>
                </div>
            <?php } ?>

            <!-- ВЫВОДИМ SUMMARY ВМЕСТО CONTENT -->
            <!--<p class="summary<?php echo $article->id?>"><?php echo htmlspecialchars($article->summary)?></p>-->
            <p class="summary<?php echo $article->id?>"><?php echo htmlspecialchars(mb_strimwidth($article->content, 0, 53, '...'))?></p>
            <img id="loader-identity" src="JS/ajax-loader.gif" alt="gif">
            
            <ul class="ajax-load">
                <li><a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>" class="ajaxArticleBodyByPost" data-contentId="<?php echo $article->id?>">Показать продолжение (POST)</a></li>
                <li><a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>" class="ajaxArticleBodyByGet" data-contentId="<?php echo $article->id?>">Показать продолжение (GET)</a></li>
                <li><a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>" class="newAjaxPost" data-contentId="<?php echo $article->id?>">(POST) -- NEW</a></li>
                <li><a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>" class="newAjaxGet" data-contentId="<?php echo $article->id?>" >(GET)  -- NEW</a></li>
            </ul>
            <a href=".?action=viewArticle&amp;articleId=<?php echo $article->id?>" class="showContent" data-contentId="<?php echo $article->id?>">Показать полностью</a>
        </li>
    <?php } ?>
    </ul>
    <p><a href="./?action=archive">Article Archive</a></p>
