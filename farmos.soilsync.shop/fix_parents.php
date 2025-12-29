<?php

// Bootstrap Drupal for farmOS
require_once 'sites/default/settings.php';

// Load the sorted taxonomy JSON
$json_file = __DIR__ . '/taxonomy_export_plant_type_sorted.json';
$json = file_get_contents($json_file);
$data = json_decode($json, true);

if (!$data || !isset($data['terms'])) {
    echo "Invalid JSON data\n";
    exit(1);
}

// Build map of old_tid => new_tid
$map = [];
foreach ($data['terms'] as $index => $term_data) {
    $old_tid = $term_data['tid'];
    $new_tid = $index + 1;
    $map[$old_tid] = $new_tid;
}

echo "Built map for " . count($map) . " terms\n";

// Now update each term's parent
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$updated = 0;

foreach ($data['terms'] as $term_data) {
    $old_tid = $term_data['tid'];
    $new_tid = $map[$old_tid];

    if (!empty($term_data['parent'])) {
        $old_parent = $term_data['parent'];
        if (isset($map[$old_parent])) {
            $new_parent = $map[$old_parent];

            // Load the term
            $term = $storage->load($new_tid);
            if ($term) {
                // Set the correct parent
                $term->set('parent', [['target_id' => $new_parent]]);
                $term->save();
                $updated++;
                if ($updated % 100 == 0) {
                    echo "Updated $updated terms\n";
                }
            }
        }
    }
}

echo "Updated $updated terms with correct parents\n";

?>