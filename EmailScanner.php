<?php
require_once 'mailscan_config.php';

class EmailScanner {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Scan an email against all active detection rules
     *
     * @param string $emailId Unique email identifier
     * @return array Scan results with matches and risk score
     */
    public function scanEmail($emailId) {
        // Get email details
        $stmt = $this->db->prepare("SELECT * FROM email_logs WHERE email_id = ?");
        $stmt->execute([$emailId]);
        $email = $stmt->fetch();

        if (!$email) {
            return ['error' => 'Email not found'];
        }

        // Get all enabled rules
        $stmt = $this->db->prepare("SELECT * FROM detection_rules WHERE enabled = 1");
        $stmt->execute();
        $rules = $stmt->fetchAll();

        $matches = [];
        $riskScore = 0;

        // Prepare searchable content
        $searchContent = [
            'subject' => $email['subject'] ?? '',
            'body_text' => $email['body_text'] ?? '',
            'body_html' => strip_tags($email['body_html'] ?? ''),
            'sender' => $email['sender_email'] ?? '',
            'recipient' => $email['recipient_email'] ?? ''
        ];

        // Scan against each rule
        foreach ($rules as $rule) {
            $ruleMatches = $this->applyRule($rule, $searchContent);

            if (!empty($ruleMatches)) {
                foreach ($ruleMatches as $match) {
                    // Store match in database
                    $this->recordMatch($emailId, $rule['id'], $match);
                    $matches[] = [
                        'rule' => $rule,
                        'match' => $match
                    ];

                    // Update risk score based on severity
                    $riskScore += $this->getSeverityScore($rule['severity']);
                }
            }
        }

        // Update email status and risk score
        $status = count($matches) > 0 ? 'flagged' : 'scanned';
        $this->updateEmailStatus($emailId, $status, $riskScore);

        return [
            'email_id' => $emailId,
            'status' => $status,
            'risk_score' => $riskScore,
            'matches' => $matches,
            'total_matches' => count($matches)
        ];
    }

    /**
     * Apply a detection rule to email content
     *
     * @param array $rule Detection rule
     * @param array $content Email content sections
     * @return array Matches found
     */
    private function applyRule($rule, $content) {
        $matches = [];

        foreach ($content as $location => $text) {
            if (empty($text)) continue;

            switch ($rule['rule_type']) {
                case 'regex':
                    $regexMatches = $this->findRegexMatches($rule['pattern'], $text);
                    foreach ($regexMatches as $match) {
                        $matches[] = [
                            'matched_content' => $match,
                            'location' => $location,
                            'context' => $this->getContext($text, $match)
                        ];
                    }
                    break;

                case 'keyword':
                    $keywords = array_map('trim', explode('|', $rule['pattern']));
                    foreach ($keywords as $keyword) {
                        if (stripos($text, $keyword) !== false) {
                            $matches[] = [
                                'matched_content' => $keyword,
                                'location' => $location,
                                'context' => $this->getContext($text, $keyword)
                            ];
                        }
                    }
                    break;

                case 'pattern':
                    // Custom pattern matching logic
                    $patternMatches = $this->findPatternMatches($rule['pattern'], $text);
                    foreach ($patternMatches as $match) {
                        $matches[] = [
                            'matched_content' => $match,
                            'location' => $location,
                            'context' => $this->getContext($text, $match)
                        ];
                    }
                    break;
            }
        }

        return $matches;
    }

    /**
     * Find regex matches in text
     */
    private function findRegexMatches($pattern, $text) {
        $matches = [];

        // Ensure pattern is valid
        if (@preg_match('/' . str_replace('/', '\/', $pattern) . '/i', '') === false) {
            return $matches;
        }

        preg_match_all('/' . str_replace('/', '\/', $pattern) . '/i', $text, $found);

        if (!empty($found[0])) {
            $matches = array_unique($found[0]);
        }

        return $matches;
    }

    /**
     * Find pattern matches (custom logic)
     */
    private function findPatternMatches($pattern, $text) {
        // This can be extended for custom pattern matching
        return [];
    }

    /**
     * Get context around a match
     */
    private function getContext($text, $match, $contextLength = 100) {
        $pos = stripos($text, $match);
        if ($pos === false) return '';

        $start = max(0, $pos - $contextLength);
        $length = strlen($match) + ($contextLength * 2);

        $context = substr($text, $start, $length);

        // Add ellipsis if truncated
        if ($start > 0) $context = '...' . $context;
        if ($start + $length < strlen($text)) $context .= '...';

        return $context;
    }

    /**
     * Record a match in the database
     */
    private function recordMatch($emailId, $ruleId, $match) {
        $stmt = $this->db->prepare("
            INSERT INTO scan_results
            (email_id, rule_id, matched_content, match_location, context_snippet)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $emailId,
            $ruleId,
            $match['matched_content'],
            $match['location'],
            $match['context']
        ]);
    }

    /**
     * Update email status
     */
    private function updateEmailStatus($emailId, $status, $riskScore) {
        $stmt = $this->db->prepare("
            UPDATE email_logs
            SET scan_status = ?, risk_score = ?
            WHERE email_id = ?
        ");

        $stmt->execute([$status, $riskScore, $emailId]);
    }

    /**
     * Get numeric score for severity
     */
    private function getSeverityScore($severity) {
        $scores = [
            'low' => 10,
            'medium' => 25,
            'high' => 50,
            'critical' => 100
        ];

        return $scores[$severity] ?? 0;
    }

    /**
     * Get email statistics
     */
    public function getStatistics() {
        $stats = [];

        // Total emails
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM email_logs");
        $stats['total_emails'] = $stmt->fetch()['total'];

        // Flagged emails
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM email_logs WHERE scan_status = 'flagged'");
        $stats['flagged_emails'] = $stmt->fetch()['total'];

        // Total matches
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM scan_results");
        $stats['total_matches'] = $stmt->fetch()['total'];

        // Active rules
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM detection_rules WHERE enabled = 1");
        $stats['active_rules'] = $stmt->fetch()['total'];

        // Matches by severity
        $stmt = $this->db->query("
            SELECT dr.severity, COUNT(*) as count
            FROM scan_results sr
            JOIN detection_rules dr ON sr.rule_id = dr.id
            GROUP BY dr.severity
        ");
        $stats['by_severity'] = $stmt->fetchAll();

        return $stats;
    }
}
?>
