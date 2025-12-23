<?php

// Bootstrap Drupal for farmOS
require_once 'sites/default/settings.php';
\Drupal::service('entity_type.manager')->getStorage('taxonomy_term');

// Load the sorted taxonomy JSON
$json_file = __DIR__ . '/taxonomy_export_plant_type_sorted.json';
$json = file_get_contents($json_file);
$data = json_decode($json, true);

if (!$data || !isset($data['terms'])) {
    echo "Invalid JSON data\n";
    exit(1);
}

$term_count = count($data['terms']);
echo "Starting import of $term_count taxonomy terms...\n";

$imported = 0;
$errors = 0;

foreach ($data['terms'] as $index => $term_data) {
    try {
        // Create the taxonomy term
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
            'vid' => $term_data['vid'],
            'name' => $term_data['name'],
            'description' => [
                'value' => $term_data['description'] ?? '',
                'format' => 'basic_html',
            ],
            'weight' => $term_data['weight'] ?? 0,
            'parent' => $term_data['parent'] ? [['target_id' => $term_data['parent']]] : [],
        ]);

        // Set custom fields from the fields array
        if (isset($term_data['fields'])) {
            $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', 'plant_type');
            foreach ($term_data['fields'] as $field_name => $field_values) {
                // Skip system fields that shouldn't be set manually
                if (in_array($field_name, ['tid', 'uuid', 'revision_id', 'langcode', 'vid', 'revision_created', 'status', 'changed', 'default_langcode', 'revision_default', 'revision_translation_affected'])) {
                    continue;
                }
                // Add field_ prefix for custom fields
                $drupal_field_name = 'field_' . $field_name;
                // Only set the field if it exists
                if (isset($field_definitions[$drupal_field_name])) {
                    $term->set($drupal_field_name, $field_values);
                }
            }
        }

        $term->save();
        $imported++;

        if ($imported % 100 == 0) {
            echo "Imported $imported / $term_count terms...\n";
        }

    } catch (Exception $e) {
        echo "Error importing term {$term_data['tid']}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "Import completed: $imported terms imported, $errors errors\n";

?>