<?php
require_once 'mailscan_config.php';

class EmailLeakTracker {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Record an email forwarding event
     */
    public function recordForwarding($chainId, $originalEmailId, $hopNumber, $fromAddress, $toAddress, $subject, $forwardType = 'direct_forward') {
        // Determine if external and unauthorized
        $isExternal = $this->isExternalAddress($toAddress);
        $isUnauthorized = $this->isUnauthorizedRecipient($toAddress);

        // Calculate leak risk score
        $leakRiskScore = $this->calculateLeakRisk($fromAddress, $toAddress, $hopNumber, $forwardType);

        $stmt = $this->db->prepare("
            INSERT INTO email_forwarding_chains
            (chain_id, original_email_id, hop_number, from_address, to_address, subject,
             forward_type, is_external, is_unauthorized, leak_risk_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $chainId,
            $originalEmailId,
            $hopNumber,
            $fromAddress,
            $toAddress,
            $subject,
            $forwardType,
            $isExternal,
            $isUnauthorized,
            $leakRiskScore
        ]);

        // Update recipient statistics
        $this->updateRecipientStats($toAddress, $fromAddress);

        // Check if this creates a leak incident
        if ($isExternal && ($isUnauthorized || $leakRiskScore >= 70)) {
            $this->createLeakIncident($chainId, $originalEmailId, $fromAddress, $toAddress, $hopNumber);
        }

        return $this->db->lastInsertId();
    }

    /**
     * Track complete email chain from original to final recipient
     */
    public function trackEmailChain($originalEmailId, $forwardingPath) {
        $chainId = 'CHAIN-' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6);

        $hopNumber = 1;
        foreach ($forwardingPath as $hop) {
            $this->recordForwarding(
                $chainId,
                $originalEmailId,
                $hopNumber,
                $hop['from'],
                $hop['to'],
                $hop['subject'] ?? '',
                $hop['type'] ?? 'direct_forward'
            );
            $hopNumber++;
        }

        return $chainId;
    }

    /**
     * Get complete forwarding chain for an email
     */
    public function getEmailChain($chainId) {
        $stmt = $this->db->prepare("
            SELECT efc.*,
                   er_from.recipient_name as from_name,
                   er_from.recipient_type as from_type,
                   er_to.recipient_name as to_name,
                   er_to.recipient_type as to_type,
                   dc.classification as to_domain_class
            FROM email_forwarding_chains efc
            LEFT JOIN email_recipients er_from ON efc.from_address = er_from.email_address
            LEFT JOIN email_recipients er_to ON efc.to_address = er_to.email_address
            LEFT JOIN domain_classifications dc ON er_to.domain = dc.domain
            WHERE efc.chain_id = ?
            ORDER BY efc.hop_number ASC
        ");

        $stmt->execute([$chainId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all leak incidents
     */
    public function getLeakIncidents($limit = 50, $status = null) {
        $sql = "
            SELECT li.*,
                   efc.total_hops,
                   COUNT(efc2.id) as chain_length
            FROM leak_incidents li
            LEFT JOIN (
                SELECT chain_id, COUNT(*) as total_hops
                FROM email_forwarding_chains
                GROUP BY chain_id
            ) efc ON li.chain_id = efc.chain_id
            LEFT JOIN email_forwarding_chains efc2 ON li.chain_id = efc2.chain_id
        ";

        if ($status) {
            $sql .= " WHERE li.investigation_status = ? ";
        }

        $sql .= " GROUP BY li.id ORDER BY li.detected_date DESC LIMIT ?";

        $stmt = $this->db->prepare($sql);

        if ($status) {
            $stmt->execute([$status, $limit]);
        } else {
            $stmt->execute([$limit]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Create a leak incident
     */
    private function createLeakIncident($chainId, $originalEmailId, $source, $destination, $totalHops) {
        $incidentId = 'INC-' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6);

        // Determine severity
        $severity = $this->calculateIncidentSeverity($source, $destination, $totalHops);
        $incidentType = $this->determineIncidentType($destination);

        $stmt = $this->db->prepare("
            INSERT INTO leak_incidents
            (incident_id, chain_id, original_email_id, leak_source, leak_destination,
             total_hops, severity, incident_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $incidentId,
            $chainId,
            $originalEmailId,
            $source,
            $destination,
            $totalHops,
            $severity,
            $incidentType
        ]);

        // Update recipient leak count
        $this->incrementLeakCount($destination);

        return $incidentId;
    }

    /**
     * Check if email address is external
     */
    private function isExternalAddress($email) {
        $domain = $this->extractDomain($email);

        $stmt = $this->db->prepare("
            SELECT classification FROM domain_classifications
            WHERE domain = ?
        ");
        $stmt->execute([$domain]);
        $result = $stmt->fetch();

        if ($result && $result['classification'] === 'internal') {
            return false;
        }

        return true;
    }

    /**
     * Check if recipient is unauthorized
     */
    private function isUnauthorizedRecipient($email) {
        $domain = $this->extractDomain($email);

        // Check domain blacklist
        $stmt = $this->db->prepare("
            SELECT trust_level, is_blacklist, classification
            FROM domain_classifications
            WHERE domain = ?
        ");
        $stmt->execute([$domain]);
        $result = $stmt->fetch();

        if ($result) {
            if ($result['is_blacklist'] ||
                $result['trust_level'] === 'blocked' ||
                $result['classification'] === 'competitor' ||
                $result['classification'] === 'suspicious') {
                return true;
            }
        }

        // Check if personal email domain
        $personalDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'protonmail.com'];
        if (in_array($domain, $personalDomains)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate leak risk score
     */
    private function calculateLeakRisk($fromAddress, $toAddress, $hopNumber, $forwardType) {
        $score = 0;

        // Base score by hop number (more hops = higher risk)
        $score += min($hopNumber * 10, 30);

        // Check destination domain
        $toDomain = $this->extractDomain($toAddress);
        $stmt = $this->db->prepare("SELECT risk_score FROM domain_classifications WHERE domain = ?");
        $stmt->execute([$toDomain]);
        $result = $stmt->fetch();

        if ($result) {
            $score += $result['risk_score'];
        } else {
            $score += 50; // Unknown domain = medium risk
        }

        // Forward type risk
        $typeRisks = [
            'external_leak' => 40,
            'personal_email' => 30,
            'bcc' => 20,
            'direct_forward' => 10,
            'cc' => 5
        ];
        $score += $typeRisks[$forwardType] ?? 10;

        return min($score, 100);
    }

    /**
     * Calculate incident severity
     */
    private function calculateIncidentSeverity($source, $destination, $hops) {
        $domain = $this->extractDomain($destination);

        $stmt = $this->db->prepare("
            SELECT classification, trust_level
            FROM domain_classifications
            WHERE domain = ?
        ");
        $stmt->execute([$domain]);
        $result = $stmt->fetch();

        if ($result) {
            if ($result['classification'] === 'competitor' || $result['trust_level'] === 'blocked') {
                return 'critical';
            }
            if ($result['classification'] === 'public' || $result['classification'] === 'suspicious') {
                return 'high';
            }
            if ($result['classification'] === 'personal') {
                return $hops > 2 ? 'high' : 'medium';
            }
        }

        return $hops > 3 ? 'high' : 'medium';
    }

    /**
     * Determine incident type
     */
    private function determineIncidentType($destination) {
        $domain = $this->extractDomain($destination);

        $stmt = $this->db->prepare("SELECT classification FROM domain_classifications WHERE domain = ?");
        $stmt->execute([$domain]);
        $result = $stmt->fetch();

        if ($result) {
            $mapping = [
                'competitor' => 'competitor_leak',
                'public' => 'public_exposure',
                'personal' => 'personal_email',
                'suspicious' => 'external_leak'
            ];
            return $mapping[$result['classification']] ?? 'external_leak';
        }

        return 'external_leak';
    }

    /**
     * Update recipient statistics
     */
    private function updateRecipientStats($toAddress, $fromAddress) {
        $domain = $this->extractDomain($toAddress);
        $isExternal = $this->isExternalAddress($toAddress);

        // Upsert recipient
        $stmt = $this->db->prepare("
            INSERT INTO email_recipients
            (email_address, domain, is_external, total_emails_received, total_emails_forwarded)
            VALUES (?, ?, ?, 1, 0)
            ON DUPLICATE KEY UPDATE
                total_emails_received = total_emails_received + 1
        ");
        $stmt->execute([$toAddress, $domain, $isExternal]);

        // Update from address forward count
        $stmt = $this->db->prepare("
            UPDATE email_recipients
            SET total_emails_forwarded = total_emails_forwarded + 1
            WHERE email_address = ?
        ");
        $stmt->execute([$fromAddress]);
    }

    /**
     * Increment leak count for recipient
     */
    private function incrementLeakCount($email) {
        $stmt = $this->db->prepare("
            UPDATE email_recipients
            SET leak_incidents = leak_incidents + 1
            WHERE email_address = ?
        ");
        $stmt->execute([$email]);
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain($email) {
        $parts = explode('@', $email);
        return isset($parts[1]) ? strtolower($parts[1]) : '';
    }

    /**
     * Get tracking statistics
     */
    public function getTrackingStats() {
        $stats = [];

        // Total chains tracked
        $stmt = $this->db->query("SELECT COUNT(DISTINCT chain_id) as total FROM email_forwarding_chains");
        $stats['total_chains'] = $stmt->fetch()['total'];

        // Total leak incidents
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM leak_incidents");
        $stats['total_incidents'] = $stmt->fetch()['total'];

        // Critical incidents
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM leak_incidents WHERE severity = 'critical'");
        $stats['critical_incidents'] = $stmt->fetch()['total'];

        // External forwards
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM email_forwarding_chains WHERE is_external = 1");
        $stats['external_forwards'] = $stmt->fetch()['total'];

        // Unauthorized recipients
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM email_forwarding_chains WHERE is_unauthorized = 1");
        $stats['unauthorized_forwards'] = $stmt->fetch()['total'];

        // Top leak sources
        $stmt = $this->db->query("
            SELECT leak_source, COUNT(*) as count
            FROM leak_incidents
            GROUP BY leak_source
            ORDER BY count DESC
            LIMIT 5
        ");
        $stats['top_leak_sources'] = $stmt->fetchAll();

        // Incidents by type
        $stmt = $this->db->query("
            SELECT incident_type, COUNT(*) as count
            FROM leak_incidents
            GROUP BY incident_type
        ");
        $stats['incidents_by_type'] = $stmt->fetchAll();

        return $stats;
    }

    /**
     * Get recipients with most leaks
     */
    public function getTopLeakers($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT email_address, recipient_name, total_emails_forwarded, leak_incidents, domain
            FROM email_recipients
            WHERE leak_incidents > 0
            ORDER BY leak_incidents DESC, total_emails_forwarded DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>
