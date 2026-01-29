<?php
/**
 * API Rate Limiting Middleware
 * Prevent API abuse with configurable rate limits
 */

class RateLimiter
{
    private $db;
    private $defaultLimit = 100; // requests per minute
    private $storage = 'database'; // or 'redis', 'memcached'

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->createRateLimitTable();
    }

    /**
     * Create rate limit tracking table
     */
    private function createRateLimitTable()
    {
        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS api_rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id VARCHAR(255) NOT NULL,
                    endpoint VARCHAR(255) NOT NULL,
                    request_count INT DEFAULT 1,
                    window_start DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_client_endpoint (client_id, endpoint, window_start)
                ) ENGINE=InnoDB
            ");
        } catch (Exception $e) {
            error_log("Rate limit table creation failed: " . $e->getMessage());
        }
    }

    /**
     * Check if request is allowed
     */
    public function isAllowed($clientId, $endpoint, $limit = null)
    {
        $limit = $limit ?? $this->defaultLimit;
        $windowStart = date('Y-m-d H:i:00'); // 1-minute windows

        // Get current count for this window
        $current = $this->getCurrentCount($clientId, $endpoint, $windowStart);

        if ($current >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'remaining' => 0,
                'reset' => strtotime($windowStart) + 60
            ];
        }

        // Increment counter
        $this->incrementCount($clientId, $endpoint, $windowStart);

        return [
            'allowed' => true,
            'limit' => $limit,
            'remaining' => $limit - $current - 1,
            'reset' => strtotime($windowStart) + 60
        ];
    }

    /**
     * Get current request count
     */
    private function getCurrentCount($clientId, $endpoint, $windowStart)
    {
        try {
            $result = $this->db->fetchOne("
                SELECT request_count
                FROM api_rate_limits
                WHERE client_id = ?
                  AND endpoint = ?
                  AND window_start = ?
            ", [$clientId, $endpoint, $windowStart]);

            return $result ? (int)$result['request_count'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Increment request count
     */
    private function incrementCount($clientId, $endpoint, $windowStart)
    {
        try {
            $this->db->query("
                INSERT INTO api_rate_limits (client_id, endpoint, request_count, window_start)
                VALUES (?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE request_count = request_count + 1
            ", [$clientId, $endpoint, $windowStart]);
        } catch (Exception $e) {
            error_log("Rate limit increment failed: " . $e->getMessage());
        }
    }

    /**
     * Get client identifier
     */
    public function getClientId()
    {
        // Use IP + User Agent as client ID
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return md5($ip . $userAgent);
    }

    /**
     * Middleware function for API routes
     */
    public function middleware($limit = null)
    {
        $clientId = $this->getClientId();
        $endpoint = $_SERVER['REQUEST_URI'] ?? '/';

        $result = $this->isAllowed($clientId, $endpoint, $limit);

        // Set rate limit headers
        header("X-RateLimit-Limit: {$result['limit']}");
        header("X-RateLimit-Remaining: {$result['remaining']}");
        header("X-RateLimit-Reset: {$result['reset']}");

        if (!$result['allowed']) {
            http_response_code(429);
            header('Retry-After: 60');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'limit' => $result['limit'],
                'reset' => $result['reset']
            ]);
            exit;
        }

        return true;
    }

    /**
     * Clean old rate limit records
     */
    public function cleanup($hoursOld = 24)
    {
        try {
            $this->db->query("
                DELETE FROM api_rate_limits
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
            ", [$hoursOld]);
        } catch (Exception $e) {
            error_log("Rate limit cleanup failed: " . $e->getMessage());
        }
    }

    /**
     * Get rate limit statistics
     */
    public function getStatistics()
    {
        try {
            return $this->db->fetchAll("
                SELECT
                    client_id,
                    endpoint,
                    SUM(request_count) as total_requests,
                    COUNT(DISTINCT window_start) as time_windows,
                    MAX(request_count) as max_per_window
                FROM api_rate_limits
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY client_id, endpoint
                ORDER BY total_requests DESC
                LIMIT 100
            ");
        } catch (Exception $e) {
            return [];
        }
    }
}
