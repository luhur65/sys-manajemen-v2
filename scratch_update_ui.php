<?php
$branches = ['mdn', 'jkt', 'sby', 'mks', 'smg', 'btg'];
$basePath = "D:\\php-project\\sys-modern\\app\\Views\\";

$search = "<!-- <div id=\"jqGridPager\"></div> -->";
$insert = "<!-- <div id=\"jqGridPager\"></div> -->\n            <div class=\"d-flex justify-content-between align-items-center p-2 mt-0\">\n                <div id=\"lastUpdateHandler\">Last Update : <?= \$last_update ?></div>\n                <div id=\"jqGridInfoHandler\"></div>\n            </div>";

foreach ($branches as $b) {
    $file = $basePath . "omsetrekapmarketing" . $b . "\\index.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Only replace if not already replaced
        if (strpos($content, 'id="lastUpdateHandler"') === false) {
            $content = str_replace($search, $insert, $content);
            file_put_contents($file, $content);
        }
    }
}
echo "UI updated for all branches.";
