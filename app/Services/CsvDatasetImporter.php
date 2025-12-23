<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CsvDatasetImporter
{
    protected $connection = 'mysql'; // Use MySQL connection
    protected $batchSize = 1000; // Insert in batches for performance
    protected $largeFileThreshold = 10000; // Files with more than this many rows use LOAD DATA INFILE
    
    /**
     * Import a CSV file into a PostgreSQL table
     */
    public function import(string $csvPath, ?string $tableName = null, array $options = []): array
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("CSV file not found: {$csvPath}");
        }
        
        Log::info("Starting CSV import", ['file' => $csvPath]);
        
        // Auto-generate table name from filename if not provided
        if (!$tableName) {
            $tableName = $this->generateTableName($csvPath);
        }
        
        // Analyze CSV structure
        $analysis = $this->analyzeCsvStructure($csvPath, $options);
        
        // Drop table if requested
        if ($options['drop'] ?? $options['drop_if_exists'] ?? false) {
            DB::statement("DROP TABLE IF EXISTS {$tableName}");
        }
        
        // Create or replace table
        $this->createTable($tableName, $analysis['columns']);
        
        // Import data - use LOAD DATA INFILE for large files
        $rowEstimate = $analysis['row_count'] ?? 0;
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql' && $rowEstimate > $this->largeFileThreshold) {
            Log::info("Using LOAD DATA LOCAL INFILE for large file", ['rows' => $rowEstimate]);
            $imported = $this->importDataWithLoadFile($csvPath, $tableName, $analysis['columns'], $options['delimiter'] ?? ',');
        } else {
            Log::info("Using batch INSERT for file", ['rows' => $rowEstimate]);
            $imported = $this->importData($csvPath, $tableName, $analysis['columns'], $options['delimiter'] ?? ',');
        }
        
        // Create indexes for common columns
        $this->createIndexes($tableName, $analysis['columns']);
        
        Log::info("CSV import completed", [
            'table' => $tableName,
            'rows' => $imported
        ]);
        
        return [
            'table_name' => $tableName,
            'rows_imported' => $imported,
            'columns' => count($analysis['columns']),
            'column_names' => array_keys($analysis['columns'])
        ];
    }
    
    /**
     * Generate a valid PostgreSQL table name from filename
     */
    protected function generateTableName(string $csvPath): string
    {
        $filename = pathinfo($csvPath, PATHINFO_FILENAME);
        
        // Convert to snake_case and remove special characters
        $tableName = Str::snake($filename);
        $tableName = preg_replace('/[^a-z0-9_]/', '', $tableName);
        $tableName = preg_replace('/_+/', '_', $tableName);
        $tableName = trim($tableName, '_');
        
        // Ensure it starts with a letter
        if (preg_match('/^[0-9]/', $tableName)) {
            $tableName = 'dataset_' . $tableName;
        }
        
        // Add prefix to avoid conflicts
        $tableName = 'ds_' . $tableName;
        
        return $tableName;
    }
    
    /**
     * Analyze CSV structure to determine column types
     */
    protected function analyzeCsvStructure(string $csvPath, array $options): array
    {
        $handle = fopen($csvPath, 'r');
        
        // Read and clean headers (remove BOM if present)
        $headers = fgetcsv($handle, 0, $options['delimiter'] ?? ',');
        if (!empty($headers[0])) {
            // Remove UTF-8 BOM from first column if present
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        
        // Make headers unique
        $headers = $this->makeUnique($headers);
        
        // Sample rows to detect data types
        $sampleSize = min(1000, $options['sample_size'] ?? 1000);
        $samples = [];
        $delimiter = $options['delimiter'] ?? ',';
        
        for ($i = 0; $i < $sampleSize; $i++) {
            $row = fgetcsv($handle, 0, $delimiter);
            if ($row === false) break;
            $samples[] = $row;
        }
        
        // Count total rows for large file detection
        $totalRows = count($samples);
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $totalRows++;
        }
        
        fclose($handle);
        
        // Detect column types
        $columns = [];
        foreach ($headers as $index => $header) {
            $columnSamples = array_column($samples, $index);
            $columns[$header] = $this->detectColumnType($columnSamples);
        }
        
        return [
            'columns' => $columns,
            'row_count' => $totalRows,
            'row_count_sample' => count($samples)
        ];
    }
    
    /**
     * Detect the best PostgreSQL data type for a column
     */
    protected function detectColumnType(array $samples): string
    {
        $nonEmpty = array_filter($samples, fn($v) => $v !== null && $v !== '');
        
        if (empty($nonEmpty)) {
            return 'TEXT';
        }
        
        $allInteger = true;
        $allNumeric = true;
        $allBoolean = true;
        $allDate = true;
        $maxLength = 0;
        
        foreach ($nonEmpty as $value) {
            $value = trim($value);
            $maxLength = max($maxLength, strlen($value));
            
            // Check if integer
            if ($allInteger && !preg_match('/^-?\d+$/', $value)) {
                $allInteger = false;
            }
            
            // Check if numeric (float/decimal)
            if ($allNumeric && !is_numeric($value)) {
                $allNumeric = false;
            }
            
            // Check if boolean
            if ($allBoolean && !in_array(strtolower($value), ['true', 'false', '1', '0', 't', 'f', 'yes', 'no'])) {
                $allBoolean = false;
            }
            
            // Check if date (must match common date formats, not just month names)
            if ($allDate) {
                $timestamp = strtotime($value);
                // Must be a valid timestamp AND match a common date format pattern
                $isDateFormat = preg_match('/^\d{4}-\d{2}-\d{2}/', $value) || // YYYY-MM-DD
                               preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value) || // MM/DD/YYYY
                               preg_match('/^\d{2}-\d{2}-\d{4}/', $value) ||   // DD-MM-YYYY
                               preg_match('/^\d{4}\/\d{2}\/\d{2}/', $value);   // YYYY/MM/DD
                
                if (!$timestamp || !$isDateFormat) {
                    $allDate = false;
                }
            }
        }
        
        // Return most specific type
        if ($allBoolean) {
            return 'BOOLEAN';
        }
        
        if ($allInteger) {
            return 'BIGINT';
        }
        
        if ($allNumeric) {
            return 'NUMERIC';
        }
        
        if ($allDate) {
            return 'TIMESTAMP';
        }
        
        // Default to VARCHAR or TEXT based on length
        if ($maxLength > 255) {
            return 'TEXT';
        }
        
        return "VARCHAR({$maxLength})";
    }
    
    /**
     * Create PostgreSQL table with analyzed schema
     */
    protected function createTable(string $tableName, array $structure): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL syntax
            $columns = ["id INT AUTO_INCREMENT PRIMARY KEY"];
            
            foreach ($structure as $columnName => $dataType) {
                $sanitizedColumn = $this->sanitizeColumnName($columnName);
                $columns[] = "`{$sanitizedColumn}` {$dataType}";
            }
            
            $columns[] = "imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $columns[] = "source_file VARCHAR(255)";
            
            $columnsStr = implode(",\n    ", $columns);
            
            $sql = "CREATE TABLE {$tableName} (
    {$columnsStr}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        } else {
            // PostgreSQL syntax
            $columns = ["id SERIAL PRIMARY KEY"];
            
            foreach ($structure as $columnName => $dataType) {
                $sanitizedColumn = $this->sanitizeColumnName($columnName);
                $columns[] = "\"{$sanitizedColumn}\" {$dataType}";
            }
            
            $columns[] = "imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $columns[] = "source_file VARCHAR(255)";
            
            $columnsStr = implode(",\n    ", $columns);
            
            $sql = "CREATE TABLE {$tableName} (
    {$columnsStr}
)";
        }

        DB::statement($sql);
    }
    
    /**
     * Import CSV data using MySQL LOAD DATA LOCAL INFILE (for large files)
     */
    protected function importDataWithLoadFile(string $csvPath, string $tableName, array $structure, string $delimiter = ','): int
    {
        // Get absolute path
        $absolutePath = realpath($csvPath);
        
        // Read headers to map to columns
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!empty($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        fclose($handle);
        
        // Build column mapping
        $sourceFile = basename($csvPath);
        $sanitizedColumns = [];
        $userVars = [];
        
        foreach ($headers as $index => $header) {
            $sanitizedColumn = $this->sanitizeColumnName($header);
            $sanitizedColumns[] = $sanitizedColumn;
            $userVars[] = "@var{$index}";
        }
        
        // Build LOAD DATA statement
        $columnsList = implode(', ', $userVars);
        $setStatements = [];
        
        foreach ($sanitizedColumns as $index => $column) {
            $setStatements[] = "{$column} = NULLIF(@var{$index}, '')";
        }
        
        $setStatements[] = "source_file = " . DB::connection()->getPdo()->quote($sourceFile);
        $setClause = implode(', ', $setStatements);
        
        // Execute LOAD DATA
        $sql = "LOAD DATA LOCAL INFILE " . DB::connection()->getPdo()->quote($absolutePath) . "
            INTO TABLE {$tableName}
            FIELDS TERMINATED BY " . DB::connection()->getPdo()->quote($delimiter) . "
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\\n'
            IGNORE 1 ROWS
            ({$columnsList})
            SET {$setClause}";
        
        Log::info("Executing LOAD DATA LOCAL INFILE", ['table' => $tableName]);
        
        try {
            DB::connection()->getPdo()->setAttribute(\PDO::MYSQL_ATTR_LOCAL_INFILE, true);
            DB::statement($sql);
            
            // Get row count
            $count = DB::table($tableName)->count();
            Log::info("LOAD DATA completed", ['rows' => $count]);
            
            return $count;
        } catch (\Exception $e) {
            Log::error("LOAD DATA failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Import CSV data into table
     */
    protected function importData(string $csvPath, string $tableName, array $structure, string $delimiter = ','): int
    {
        $handle = fopen($csvPath, 'r');
        
        // Read and clean headers (remove BOM if present)
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!empty($headers[0])) {
            // Remove UTF-8 BOM from first column if present
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        
        $driver = DB::connection()->getDriverName();
        $batch = [];
        $batchSize = 1000;
        $sourceFile = basename($csvPath);
        $rowCount = 0;
        
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;
            $data = [];
            
            foreach ($headers as $index => $header) {
                $sanitizedColumn = $this->sanitizeColumnName($header);
                $value = $row[$index] ?? null;
                
                // Handle empty strings as NULL
                if ($value === '' || $value === null) {
                    $data[$sanitizedColumn] = null;
                    continue;
                }
                
                // Convert values based on column type
                $dataType = $structure[$header] ?? 'TEXT';
                
                if (str_contains($dataType, 'BIGINT') || str_contains($dataType, 'INT')) {
                    $data[$sanitizedColumn] = filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : null;
                } elseif (str_contains($dataType, 'NUMERIC') || str_contains($dataType, 'DECIMAL')) {
                    $data[$sanitizedColumn] = is_numeric($value) ? (float)$value : null;
                } elseif (str_contains($dataType, 'BOOLEAN')) {
                    $data[$sanitizedColumn] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } elseif (str_contains($dataType, 'TIMESTAMP')) {
                    try {
                        $data[$sanitizedColumn] = $value;
                    } catch (\Exception $e) {
                        $data[$sanitizedColumn] = null;
                    }
                } else {
                    $data[$sanitizedColumn] = $value;
                }
            }
            
            $data['source_file'] = $sourceFile;
            $batch[] = $data;
            
            // Insert batch when it reaches the batch size
            if (count($batch) >= $batchSize) {
                DB::table($tableName)->insert($batch);
                $batch = [];
            }
        }
        
        // Insert remaining rows
        if (!empty($batch)) {
            DB::table($tableName)->insert($batch);
        }
        
        fclose($handle);
        
        \Log::info("Imported $rowCount rows from $sourceFile into $tableName");
        
        return $rowCount;
    }
    
    /**
     * Convert value to appropriate type
     */
    protected function convertValue($value, string $type)
    {
        $value = trim($value);
        
        if ($value === '' || $value === null) {
            return null;
        }
        
        if (str_starts_with($type, 'BOOLEAN')) {
            return in_array(strtolower($value), ['true', '1', 't', 'yes']);
        }
        
        if (str_starts_with($type, 'BIGINT') || str_starts_with($type, 'INTEGER')) {
            return (int) $value;
        }
        
        if (str_starts_with($type, 'NUMERIC') || str_starts_with($type, 'DECIMAL')) {
            return (float) $value;
        }
        
        if (str_starts_with($type, 'TIMESTAMP')) {
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        }
        
        return $value;
    }
    
    /**
     * Create indexes for common columns
     */
    protected function createIndexes(string $tableName, array $structure): void
    {
        $driver = DB::connection()->getDriverName();
        $indexableColumns = ['country', 'year', 'date', 'region', 'area', 'crop', 'country_or_area'];
        
        foreach ($structure as $columnName => $dataType) {
            $sanitizedColumn = $this->sanitizeColumnName($columnName);
            $lowerColumn = strtolower($sanitizedColumn);
            
            if (in_array($lowerColumn, $indexableColumns)) {
                try {
                    $indexName = "idx_{$tableName}_{$sanitizedColumn}";
                    
                    if ($driver === 'mysql') {
                        DB::statement("CREATE INDEX {$indexName} ON {$tableName} (`{$sanitizedColumn}`)");
                    } else {
                        DB::statement("CREATE INDEX {$indexName} ON {$tableName} (\"{$sanitizedColumn}\")");
                    }
                } catch (\Exception $e) {
                    // Ignore if index already exists
                }
            }
        }
    }
    
    /**
     * Make array values unique by appending numbers
     */
    protected function makeUnique(array $array): array
    {
        $counts = array_count_values($array);
        $result = [];
        $used = [];
        
        foreach ($array as $value) {
            if ($counts[$value] > 1) {
                $counter = $used[$value] ?? 1;
                $used[$value] = $counter + 1;
                $result[] = $value . '_' . $counter;
            } else {
                $result[] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Sanitize column name for database use
     */
    protected function sanitizeColumnName(string $columnName): string
    {
        // Handle leading underscores or special characters
        $sanitized = trim($columnName);
        
        // Remove leading underscores temporarily
        $leadingUnderscores = '';
        while (str_starts_with($sanitized, '_')) {
            $leadingUnderscores .= 'u_';
            $sanitized = substr($sanitized, 1);
        }
        
        // Convert to snake_case and remove special characters
        $sanitized = Str::snake($sanitized);
        $sanitized = preg_replace('/[^a-z0-9_]/', '', $sanitized);
        
        // Re-add the leading underscore marker
        $sanitized = $leadingUnderscores . $sanitized;
        
        // Ensure it doesn't start with a number
        if (is_numeric($sanitized[0] ?? '')) {
            $sanitized = 'col_' . $sanitized;
        }
        
        // Ensure we have at least something
        if (empty($sanitized)) {
            $sanitized = 'col_unknown';
        }
        
        return $sanitized;
    }
    
    /**
     * Get list of imported dataset tables
     */
    public function getImportedDatasets(): array
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            $tables = DB::select("
                SHOW TABLES LIKE 'ds_%'
            ");
            
            $datasets = [];
            foreach ($tables as $table) {
                // MySQL returns table name in a dynamic key
                $tableName = current((array)$table);
                $count = DB::table($tableName)->count();
                $columns = $this->getTableColumns($tableName);
                
                $datasets[] = [
                    'table_name' => $tableName,
                    'display_name' => $this->tableNameToDisplayName($tableName),
                    'row_count' => $count,
                    'column_count' => count($columns),
                    'columns' => $columns
                ];
            }
        } else {
            // PostgreSQL
            $tables = DB::select("
                SELECT 
                    tablename as table_name,
                    schemaname as schema_name
                FROM pg_tables 
                WHERE schemaname = 'public' 
                AND tablename LIKE 'ds_%'
                ORDER BY tablename
            ");
            
            $datasets = [];
            foreach ($tables as $table) {
                $count = DB::table($table->table_name)->count();
                $columns = $this->getTableColumns($table->table_name);
                
                $datasets[] = [
                    'table_name' => $table->table_name,
                    'display_name' => $this->tableNameToDisplayName($table->table_name),
                    'row_count' => $count,
                    'column_count' => count($columns),
                    'columns' => $columns
                ];
            }
        }
        
        return $datasets;
    }
    
    /**
     * Get table column information
     */
    protected function getTableColumns(string $tableName): array
    {
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                character_maximum_length
            FROM information_schema.columns
            WHERE table_name = ?
            AND column_name NOT IN ('id', 'imported_at', 'source_file')
            ORDER BY ordinal_position
        ", [$tableName]);
        
        return array_map(fn($col) => [
            'name' => $col->column_name,
            'type' => $col->data_type,
            'max_length' => $col->character_maximum_length
        ], $columns);
    }
    
    /**
     * Convert table name to display name
     */
    protected function tableNameToDisplayName(string $tableName): string
    {
        // Remove ds_ prefix
        $name = preg_replace('/^ds_/', '', $tableName);
        
        // Convert underscores to spaces and title case
        return Str::title(str_replace('_', ' ', $name));
    }
}
