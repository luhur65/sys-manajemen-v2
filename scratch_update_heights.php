<?php
$dir = new RecursiveDirectoryIterator('app/Views');
$iterator = new RecursiveIteratorIterator($dir);
$phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$modifiedFiles = [];

foreach ($phpFiles as $file) {
    $filePath = $file[0];
    $content = file_get_contents($filePath);
    
    // Check if the file uses lazy loading (loadGridData)
    if (strpos($content, 'loadGridData') !== false) {
        // Find the initialization of jqGrid, usually $("#jqGrid").jqGrid({
        // We'll use a regex to find height property inside jqGrid config
        // Because there can be multiple grids, we only want to target the main one
        // Let's replace ANY `height: \d+,` or `height: \$\(window\).*,` 
        // with `height: 400,`
        
        // Wait, some heights are for detail grids or other things.
        // Let's just replace all `height: <number or formula>,` that are properties of grid
        $newContent = preg_replace_callback('/(height\s*:\s*)([^\n,]+)(,)/', function($matches) {
            // We want to skip replacing if it's already 400 or if it's css height like `height: 35px;`
            if (trim($matches[2]) === '400') {
                return $matches[0];
            }
            if (strpos($matches[2], 'px') !== false) {
                return $matches[0];
            }
            // Usually grid heights are numeric or JS window height formulas
            return $matches[1] . '400' . $matches[3];
        }, $content);
        
        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            $modifiedFiles[] = $filePath;
        }
    }
}

echo "Modified files:\n";
print_r($modifiedFiles);
