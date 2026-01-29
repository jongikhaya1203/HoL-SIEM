<?php
/**
 * Storage Scanner - Duplicate Detection & Data Classification
 */

class StorageScanner {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Scan files for duplicates and classification
     */
    public function scanFiles($scanId, $files) {
        $filesByHash = [];
        $classifications = [];
        $totalScanned = 0;

        // Load classification rules
        $rules = $this->getClassificationRules();

        foreach ($files as $file) {
            // Insert file scan record
            $this->insertFileScan($scanId, $file);

            // Group files by hash for duplicate detection
            $hash = $file['hash'];
            if (!isset($filesByHash[$hash])) {
                $filesByHash[$hash] = [];
            }
            $filesByHash[$hash][] = $file;

            // Classify file
            $classification = $this->classifyFile($file, $rules);
            $classifications[] = $classification;

            $totalScanned++;
        }

        // Find duplicate groups (hash with 2+ files)
        $duplicates = [];
        foreach ($filesByHash as $hash => $filesWithHash) {
            if (count($filesWithHash) >= 2) {
                $duplicates[$hash] = $filesWithHash;
            }
        }

        // Create duplicate groups
        $this->processDuplicateGroups($duplicates);

        // Generate recommendations
        $this->generateRecommendations($scanId);

        return [
            'total_scanned' => $totalScanned,
            'duplicates_found' => count($duplicates),
            'classifications' => $classifications
        ];
    }

    /**
     * Check if file is a duplicate
     */
    private function checkDuplicate($fileHash, $fileSize) {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count, GROUP_CONCAT(file_path) as paths
            FROM file_scans
            WHERE file_hash = ? AND file_size_bytes = ?
        ", [$fileHash, $fileSize]);
        $result = $stmt->fetch();

        return [
            'is_duplicate' => $result['count'] > 1,
            'duplicate_count' => $result['count'],
            'paths' => $result['paths']
        ];
    }

    /**
     * Classify file based on rules
     */
    private function classifyFile($file, $rules) {
        $classification = 'Unclassified';
        $sensitivity = 'Public';
        $tier = 'Tier 2 - Standard';
        $retention = 365;
        $highestPriority = 0;

        foreach ($rules as $rule) {
            if ($rule['priority'] <= $highestPriority) {
                continue; // Skip lower priority rules if already matched
            }

            $matches = false;

            switch ($rule['match_type']) {
                case 'extension':
                    $extensions = explode('|', $rule['match_pattern']);
                    $matches = in_array(strtolower($file['extension']), array_map('strtolower', $extensions));
                    break;

                case 'filename_pattern':
                    $patterns = explode('|', $rule['match_pattern']);
                    foreach ($patterns as $pattern) {
                        // Escape regex chars except %, then convert % to .*
                        $pattern = preg_quote($pattern, '/');
                        $pattern = str_replace('\\%', '.*', $pattern);
                        if (preg_match('/' . $pattern . '/i', $file['name'])) {
                            $matches = true;
                            break;
                        }
                    }
                    break;

                case 'path_pattern':
                    $patterns = explode('|', $rule['match_pattern']);
                    foreach ($patterns as $pattern) {
                        // Escape regex chars except %, then convert % to .*
                        $pattern = preg_quote($pattern, '/');
                        $pattern = str_replace('\\%', '.*', $pattern);
                        if (preg_match('/' . $pattern . '/i', $file['path'])) {
                            $matches = true;
                            break;
                        }
                    }
                    break;

                case 'size_range':
                    if (strpos($rule['match_pattern'], '>') === 0) {
                        $threshold = intval(substr($rule['match_pattern'], 1));
                        $matches = $file['size'] > $threshold;
                    } elseif (strpos($rule['match_pattern'], '<') === 0) {
                        $threshold = intval(substr($rule['match_pattern'], 1));
                        $matches = $file['size'] < $threshold;
                    }
                    break;
            }

            if ($matches) {
                $classification = $rule['classification'];
                $sensitivity = $rule['sensitivity_level'];
                $tier = $rule['storage_tier_recommendation'];
                $retention = $rule['retention_days'];
                $highestPriority = $rule['priority'];
            }
        }

        return [
            'classification' => $classification,
            'sensitivity' => $sensitivity,
            'tier' => $tier,
            'retention' => $retention
        ];
    }

    /**
     * Insert file scan record
     */
    private function insertFileScan($scanId, $file) {
        $classification = $this->classifyFile($file, $this->getClassificationRules());

        $this->db->query("
            INSERT INTO file_scans
            (scan_id, file_path, file_name, file_extension, file_size_bytes, file_hash,
             created_date, modified_date, storage_location, owner, department,
             data_classification, sensitivity_level)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $scanId,
            $file['path'],
            $file['name'],
            $file['extension'],
            $file['size'],
            $file['hash'],
            $file['created'] ?? date('Y-m-d H:i:s'),
            $file['modified'] ?? date('Y-m-d H:i:s'),
            $file['location'] ?? 'Unknown',
            $file['owner'] ?? 'System',
            $file['department'] ?? 'General',
            $classification['classification'],
            $classification['sensitivity']
        ]);
    }

    /**
     * Get classification rules
     */
    private function getClassificationRules() {
        $stmt = $this->db->query("
            SELECT * FROM classification_rules
            WHERE enabled = 1
            ORDER BY priority DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Process duplicate groups
     */
    private function processDuplicateGroups($duplicates) {
        // $duplicates is already grouped by hash
        // Create duplicate group records
        foreach ($duplicates as $hash => $files) {
            if (count($files) < 2) continue;

            $groupId = 'DUP-' . substr($hash, 0, 12);
            $fileSize = $files[0]['size'];
            $wastedSpace = $fileSize * (count($files) - 1);

            $priority = 'Low';
            if ($wastedSpace > 10737418240) { // > 10GB
                $priority = 'Critical';
            } elseif ($wastedSpace > 1073741824) { // > 1GB
                $priority = 'High';
            } elseif ($wastedSpace > 104857600) { // > 100MB
                $priority = 'Medium';
            }

            $recommendation = $this->generateDuplicationRecommendation($wastedSpace, count($files));

            $this->db->query("
                INSERT INTO duplicate_groups
                (group_id, file_hash, file_size_bytes, duplicate_count, total_wasted_space,
                 file_type, first_occurrence_path, priority, recommendation)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                duplicate_count = VALUES(duplicate_count),
                total_wasted_space = VALUES(total_wasted_space)
            ", [
                $groupId,
                $hash,
                $fileSize,
                count($files),
                $wastedSpace,
                $files[0]['type'] ?? 'Unknown',
                $files[0]['path'] ?? '',
                $priority,
                $recommendation
            ]);
        }
    }

    /**
     * Generate deduplication recommendation
     */
    private function generateDuplicationRecommendation($wastedSpace, $fileCount) {
        $wastedGB = round($wastedSpace / 1073741824, 2);

        return "Found $fileCount copies of the same file, wasting $wastedGB GB of storage. " .
               "Recommendation: Keep one master copy and remove or replace others with symbolic links. " .
               "Potential savings: $wastedGB GB.";
    }

    /**
     * Generate optimization recommendations
     */
    private function generateRecommendations($scanId) {
        // Recommendation 1: Deduplication
        $stmt = $this->db->query("
            SELECT SUM(total_wasted_space) as total_waste,
                   COUNT(*) as group_count
            FROM duplicate_groups
            WHERE status = 'Detected'
        ");
        $dupStats = $stmt->fetch();

        if ($dupStats['total_waste'] > 0) {
            $this->createRecommendation(
                'Deduplication',
                'Remove Duplicate Files',
                "Found {$dupStats['group_count']} groups of duplicate files wasting " .
                round($dupStats['total_waste'] / 1073741824, 2) . " GB of storage.",
                $dupStats['total_waste'],
                $dupStats['group_count'],
                $dupStats['total_waste'] > 10737418240 ? 'Critical' : 'High'
            );
        }

        // Recommendation 2: Tier Migration
        $stmt = $this->db->query("
            SELECT COUNT(*) as file_count,
                   SUM(file_size_bytes) as total_size
            FROM file_scans
            WHERE data_classification IN ('Archive', 'Backup', 'Log File')
            AND storage_tier = 'Tier 1 - High Performance'
        ");
        $tierStats = $stmt->fetch();

        if ($tierStats['file_count'] > 0) {
            $potentialSavings = ($tierStats['total_size'] / 1073741824) * 0.35 * 12; // Annual savings
            $this->createRecommendation(
                'Tier Migration',
                'Move Archive Data to Lower-Cost Storage',
                "Move {$tierStats['file_count']} archive/backup files from high-performance to archive tier. " .
                "Potential annual cost savings: $" . round($potentialSavings, 2),
                $tierStats['total_size'],
                $tierStats['file_count'],
                'Medium'
            );
        }

        // Recommendation 3: Delete Old Temporary Files
        $stmt = $this->db->query("
            SELECT COUNT(*) as file_count,
                   SUM(file_size_bytes) as total_size
            FROM file_scans
            WHERE data_classification = 'Temporary'
            AND DATEDIFF(NOW(), modified_date) > 30
        ");
        $tempStats = $stmt->fetch();

        if ($tempStats['file_count'] > 0) {
            $this->createRecommendation(
                'Delete',
                'Remove Old Temporary Files',
                "Delete {$tempStats['file_count']} temporary files older than 30 days.",
                $tempStats['total_size'],
                $tempStats['file_count'],
                'Low'
            );
        }
    }

    /**
     * Create optimization recommendation
     */
    private function createRecommendation($type, $title, $description, $savings, $fileCount, $priority) {
        $recommendationId = 'REC-' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6);

        $this->db->query("
            INSERT INTO optimization_recommendations
            (recommendation_id, recommendation_type, title, description,
             potential_savings_bytes, affected_files_count, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $recommendationId,
            $type,
            $title,
            $description,
            $savings,
            $fileCount,
            $priority
        ]);
    }

    /**
     * Get scan statistics
     */
    public function getScanStatistics($scanId = null) {
        $stats = [];

        // Total files scanned
        $where = $scanId ? "WHERE scan_id = '$scanId'" : "";
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM file_scans $where");
        $stats['total_files'] = $stmt->fetch()['total'];

        // Total storage used
        $stmt = $this->db->query("SELECT SUM(file_size_bytes) as total FROM file_scans $where");
        $stats['total_storage_bytes'] = $stmt->fetch()['total'] ?? 0;

        // Duplicate files
        $stmt = $this->db->query("
            SELECT COUNT(*) as total,
                   SUM(total_wasted_space) as wasted
            FROM duplicate_groups
        ");
        $dupStats = $stmt->fetch();
        $stats['duplicate_groups'] = $dupStats['total'];
        $stats['wasted_space_bytes'] = $dupStats['wasted'] ?? 0;

        // By classification
        $stmt = $this->db->query("
            SELECT data_classification,
                   COUNT(*) as count,
                   SUM(file_size_bytes) as total_size
            FROM file_scans $where
            GROUP BY data_classification
            ORDER BY total_size DESC
        ");
        $stats['by_classification'] = $stmt->fetchAll();

        // By sensitivity
        $stmt = $this->db->query("
            SELECT sensitivity_level,
                   COUNT(*) as count,
                   SUM(file_size_bytes) as total_size
            FROM file_scans $where
            GROUP BY sensitivity_level
        ");
        $stats['by_sensitivity'] = $stmt->fetchAll();

        // Recommendations
        $stmt = $this->db->query("
            SELECT COUNT(*) as total,
                   SUM(potential_savings_bytes) as savings
            FROM optimization_recommendations
            WHERE status = 'Pending'
        ");
        $recStats = $stmt->fetch();
        $stats['pending_recommendations'] = $recStats['total'];
        $stats['potential_savings_bytes'] = $recStats['savings'] ?? 0;

        return $stats;
    }

    /**
     * Get duplicate groups
     */
    public function getDuplicateGroups($limit = 50) {
        return $this->db->query("
            SELECT dg.*,
                   COUNT(fs.id) as file_count
            FROM duplicate_groups dg
            LEFT JOIN file_scans fs ON dg.file_hash = fs.file_hash
            WHERE dg.status != 'Ignored'
            GROUP BY dg.id
            ORDER BY dg.total_wasted_space DESC
            LIMIT ?
        ", [$limit])->fetchAll();
    }

    /**
     * Get files in duplicate group
     */
    public function getFilesInDuplicateGroup($groupId) {
        return $this->db->query("
            SELECT fs.*
            FROM file_scans fs
            JOIN duplicate_groups dg ON fs.file_hash = dg.file_hash
            WHERE dg.group_id = ?
        ", [$groupId])->fetchAll();
    }

    /**
     * Get optimization recommendations
     */
    public function getRecommendations($status = 'Pending') {
        $where = $status ? "WHERE status = ?" : "";
        $params = $status ? [$status] : [];

        return $this->db->query("
            SELECT * FROM optimization_recommendations
            $where
            ORDER BY
                CASE priority
                    WHEN 'Critical' THEN 1
                    WHEN 'High' THEN 2
                    WHEN 'Medium' THEN 3
                    WHEN 'Low' THEN 4
                END,
                created_date DESC
        ", $params)->fetchAll();
    }
}
?>
