<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        submit_button('Save Settings');
        ?>
    </form>
</div>
