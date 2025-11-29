<?php include "templates/include/header.php" ?>
<?php include "templates/admin/include/header.php" ?>

    <h1><?php echo $results['pageTitle']?></h1>

    <form action="admin.php?action=<?php echo $results['formAction']?>" method="post">
        <input type="hidden" name="userId" value="<?php echo $results['user']->id ?>"/>

        <?php if (isset($results['errorMessage'])) { ?>
            <div class="errorMessage"><?php echo $results['errorMessage'] ?></div>
        <?php } ?>

        <ul>
            <li>
                <label for="login">Login</label>
                <input type="text" name="login" id="login" placeholder="User login" required autofocus maxlength="50" value="<?php echo htmlspecialchars($results['user']->login)?>" />
            </li>

            <li>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="User password" <?php echo ($results['formAction'] == 'newUser') ? 'required' : '' ?> maxlength="255" />
                <?php if ($results['formAction'] == 'editUser'): ?>
                    <small>Оставьте пустым, если не хотите менять пароль</small>
                <?php endif; ?>
            </li>

            <li>
                <label for="active">User Status</label>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="active" name="active" value="1" 
                           <?php echo ($results['user']->active ? 'checked' : ''); ?> />
                    <label for="active" class="checkbox-label">
                        User is active
                    </label>
                </div>
            </li>
        </ul>

        <div class="buttons">
            <input type="submit" name="saveChanges" value="Save Changes" />
            <input type="submit" formnovalidate name="cancel" value="Cancel" />
        </div>
    </form>

    <?php if ($results['user']->id) { ?>
        <p><a href="admin.php?action=deleteUser&amp;userId=<?php echo $results['user']->id ?>" onclick="return confirm('Delete This User?')">Delete This User</a></p>
    <?php } ?>

<?php include "templates/include/footer.php" ?>
