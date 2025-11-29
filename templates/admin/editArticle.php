<?php include "templates/include/header.php" ?>
<?php include "templates/admin/include/header.php" ?>
<!--        <?php echo "<pre>";
            print_r($results);
            print_r($data);
        echo "<pre>"; ?> Данные о массиве $results и типе формы передаются корректно-->

        <h1><?php echo $results['pageTitle']?></h1>

        <form action="admin.php?action=<?php echo $results['formAction']?>" method="post">
            <input type="hidden" name="articleId" value="<?php echo $results['article']->id ?>">

    <?php if ( isset( $results['errorMessage'] ) ) { ?>
            <div class="errorMessage"><?php echo $results['errorMessage'] ?></div>
    <?php } ?>

            <ul>

              <li>
                <label for="title">Article Title</label>
                <input type="text" name="title" id="title" placeholder="Name of the article" required autofocus maxlength="255" value="<?php echo htmlspecialchars( $results['article']->title )?>" />
              </li>

              <li>
                <label for="summary">Article Summary</label>
                <textarea name="summary" id="summary" placeholder="Brief description of the article" required maxlength="1000" style="height: 5em;"><?php echo htmlspecialchars( $results['article']->summary )?></textarea>
              </li>

              <li>
                <label for="content">Article Content</label>
                <textarea name="content" id="content" placeholder="The HTML content of the article" required maxlength="100000" style="height: 30em;"><?php echo htmlspecialchars( $results['article']->content )?></textarea>
              </li>
              <li>
                <label for="categoryId">Article Category</label>
                <select name="categoryId" id="categoryId" required>
                  <option value="" disabled <?php echo !$results['article']->categoryId ? "selected" : "" ?>>Select a category</option>
                  <?php foreach ($results['categories'] as $category) { ?>
                    <option value="<?php echo $category->id ?>"
                      <?php echo ($results['article']->categoryId == $category->id) ? "selected" : "" ?>>
                      <?php echo htmlspecialchars($category->name) ?>
                    </option>
                  <?php } ?>
                </select>
              </li>

              <li>
                <label for="subcategoryId">Article Subcategory</label>
                <select name="subcategoryId" id="subcategoryId">
                  <option value="">No Subcategory</option>
                  <?php
                  // Показываем только подкатегории, принадлежащие выбранной категории
                  $currentCategoryId = $results['article']->categoryId;
                  foreach ($results['subcategories'] as $subcategory) {
                    if ($subcategory->categoryId == $currentCategoryId) {
                      $selected = ($results['article']->subcategoryId == $subcategory->id) ? "selected" : "";
                      echo '<option value="' . $subcategory->id . '" ' . $selected . '>' .
                        htmlspecialchars($subcategory->name) . '</option>';
                    }
                  }
                  ?>
                </select>
                <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                  Подкатегории отображаются только для выбранной категории
                </div>
              </li>

              <!-- Article Authors -->
              <li>
                <label for="authors">Article Authors</label>
                <select name="authors[]" id="authors" multiple size="5" style="height: auto;">
                  <?php foreach ($results['users'] as $user) { ?>
                    <option value="<?php echo $user->id ?>"
                      <?php
                      // Проверяем, выбран ли этот пользователь как автор
                      if (isset($results['currentAuthorIds']) && in_array($user->id, $results['currentAuthorIds'])) {
                        echo ' selected="selected"';
                      }
                      ?>>
                      <?php echo htmlspecialchars($user->login) ?>
                    </option>
                  <?php } ?>
                </select>
                <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                  Для выбора нескольких авторов удерживайте Ctrl (Cmd на Mac)
                </div>
              </li>

              <li>
                <label for="publicationDate">Publication Date</label>
                <input type="date" name="publicationDate" id="publicationDate" placeholder="YYYY-MM-DD" required maxlength="10" value="<?php echo $results['article']->publicationDate ? date( "Y-m-d", $results['article']->publicationDate ) : "" ?>" />
              </li>

              <li>
                <label for="active">Article Status</label>
                <input type="checkbox" name="active" id="active" <?php echo $results['article']->active ? "checked" : "" ?> /> Active
              </li>

            </ul>

            <div class="buttons">
              <input type="submit" name="saveChanges" value="Save Changes" />
              <input type="submit" formnovalidate name="cancel" value="Cancel" />
            </div>

        </form>

    <?php if ($results['article']->id) { ?>
          <p><a href="admin.php?action=deleteArticle&amp;articleId=<?php echo $results['article']->id ?>" onclick="return confirm('Delete This Article?')">
                  Delete This Article
              </a>
          </p>
    <?php } ?>
	  
<?php include "templates/include/footer.php" ?>

              