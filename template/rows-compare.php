<meta charset="utf-8">

<style type="text/css" media="all">
    @import url("/public/css/style.css");
</style>

<?php if (count($rows)) { ?>
    <table class="data-table">
        <?php foreach ($rows as $row) { ?>
            <tr>
                <?php
                foreach ($row as $rowItem) {
                    echo '
                    <td>';
                    if(strlen($rowItem) > 100) {
                        echo '<div class="field-value-compressed">' . $rowItem . '</div>';
                    } else {
                        echo $rowItem;
                    }
                    echo '</td>';
                } ?>
            </tr>
        <?php } ?>
    </table>
<?php } else { ?>
    <h2 class="no-records-found"><u>No records found</u></h2>
<?php } ?>
