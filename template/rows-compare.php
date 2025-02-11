<meta charset="utf-8">

<style type="text/css" media="all">
    @import url("public/css/style.css");
    @import url("public/css/custom.css");
</style>

<h2><?php echo $_REQUEST['firstTableName'] === $_REQUEST['secondTableName'];?></h2>
<?php if (count($rows)) { ?>
    <table class="data-table">
        <?php foreach ($rows as $row) { ?>
            <tr>
                <?php
                $i = 0;
                foreach ($row as $rowItem) {
                    echo '
                    <td>';
                    if($i > 0 && strlen($rowItem) > 100) {
                        echo '<div class="field-value-compressed">' . htmlspecialchars(strip_tags($rowItem)) . '</div>';
                    } else {
                        echo $rowItem;
                    }
                    echo '</td>';
                    $i++;
                } ?>
            </tr>
        <?php } ?>
    </table>
<?php } else { ?>
    <h2 class="no-records-found"><u>No records found</u></h2>
<?php } ?>
