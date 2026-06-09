<?php
$content = "\n\n";
$content .= "// Routes for App\Controllers\Grafiktradoluar (Grafiktradoluar)\n";
$content .= "\$routes->match(['GET', 'POST'], 'Grafiktradoluar', 'Grafiktradoluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'grafiktradoluar', 'Grafiktradoluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Grafiktradoluar/index', 'Grafiktradoluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'grafiktradoluar/index', 'Grafiktradoluar::index');\n";

file_put_contents('D:\\php-project\\sys-modern\\app\\Config\\Routes.php', $content, FILE_APPEND);
echo "Routes appended.";
