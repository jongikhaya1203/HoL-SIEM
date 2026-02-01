<?php
/**
 * Alert Manager Class
 * Handles Email, SMS, Webhook, and Push Notifications
 */

class AlertManager
{
    private $db;
    private $settings;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $result = $this->db->fetchAll("SELECT * FROM settings WHERE setting_key LIKE 'alert%'");
        $this->settings = [];
        foreach ($result as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    /**
     * Send alert through all enabled channels
     */
    public function sendAlert($severity, $title, $message, $data = [])
    {
        $alert = [
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];

        // Log alert to database
        $this->logAlert($alert);

        // Check if alert should be sent based on severity
        if ($this->shouldAlert($severity)) {
            // Send through enabled channels
            if ($this->isChannelEnabled('email')) {
                $this->sendEmailAlert($alert);
            }

            if ($this->isChannelEnabled('sms')) {
                $this->sendSMSAlert($alert);
            }

            if ($this->isChannelEnabled('webhook')) {
                $this->sendWebhookAlert($alert);
            }

            if ($this->isChannelEnabled('push')) {
                $this->sendPushNotification($alert);
            }
        }

        return true;
    }

    /**
     * Check if alert should be sent based on severity
     */
    private function shouldAlert($severity)
    {
        $critical = ($this->settings['alert_critical'] ?? '1') == '1';
        $high = ($this->settings['alert_high'] ?? '0') == '1';

        if ($severity === 'critical' && $critical) return true;
        if ($severity === 'high' && $high) return true;

        return false;
    }

    /**
     * Check if notification channel is enabled
     */
    private function isChannelEnabled($channel)
    {
        return ($this->settings["alert_enable_{$channel}"] ?? '0') == '1';
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert($alert)
    {
        $to = $this->settings['alert_email'] ?? '';
        if (empty($to)) {
            error_log("Alert email not configured");
            return false;
        }

        $subject = "[{$alert['severity']}] {$alert['title']}";
        $body = "Security Alert from Network Scanner\n\n";
        $body .= "Severity: " . strtoupper($alert['severity']) . "\n";
        $body .= "Title: {$alert['title']}\n";
        $body .= "Message: {$alert['message']}\n";
        $body .= "Time: {$alert['timestamp']}\n\n";

        if (!empty($alert['data'])) {
            $body .= "Additional Details:\n";
            foreach ($alert['data'] as $key => $value) {
                $body .= "  {$key}: {$value}\n";
            }
        }

        $body .= "\n---\nHoL Intelligent Operating Centre\n";

        $headers = "From: alerts@networkscan.local\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send email
        if (mail($to, $subject, $body, $headers)) {
            error_log("Email alert sent to {$to}");
            return true;
        } else {
            error_log("Failed to send email alert");
            return false;
        }
    }

    /**
     * Send SMS alert via Twilio
     */
    private function sendSMSAlert($alert)
    {
        $phoneNumber = $this->settings['alert_sms_number'] ?? '';
        if (empty($phoneNumber)) {
            error_log("SMS phone number not configured");
            return false;
        }

        // In production, integrate with Twilio or another SMS provider
        $message = "[{$alert['severity']}] {$alert['title']}: {$alert['message']}";

        // Simulated SMS send (would use Twilio API in production)
        error_log("SMS Alert would be sent to {$phoneNumber}: {$message}");

        /*
        // Example Twilio integration:
        $twilioSid = $this->settings['twilio_sid'] ?? '';
        $twilioToken = $this->settings['twilio_token'] ?? '';
        $twilioFrom = $this->settings['twilio_from'] ?? '';

        $twilio = new Twilio\Rest\Client($twilioSid, $twilioToken);
        $twilio->messages->create($phoneNumber, [
            'from' => $twilioFrom,
            'body' => $message
        ]);
        */

        return true;
    }

    /**
     * Send webhook notification
     */
    private function sendWebhookAlert($alert)
    {
        $webhookUrl = $this->settings['alert_webhook_url'] ?? '';
        if (empty($webhookUrl)) {
            error_log("Webhook URL not configured");
            return false;
        }

        $payload = json_encode([
            'severity' => $alert['severity'],
            'title' => $alert['title'],
            'message' => $alert['message'],
            'timestamp' => $alert['timestamp'],
            'data' => $alert['data']
        ]);

        // Send webhook
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Webhook alert sent successfully");
            return true;
        } else {
            error_log("Failed to send webhook alert. HTTP {$httpCode}");
            return false;
        }
    }

    /**
     * Send push notification
     */
    private function sendPushNotification($alert)
    {
        // In production, integrate with FCM, OneSignal, or similar service
        error_log("Push notification: [{$alert['severity']}] {$alert['title']}");

        /*
        // Example Firebase Cloud Messaging integration:
        $fcmServerKey = $this->settings['fcm_server_key'] ?? '';
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

        $notification = [
            'title' => $alert['title'],
            'body' => $alert['message'],
            'icon' => 'notification_icon',
            'sound' => 'default'
        ];

        $payload = json_encode([
            'to' => '/topics/alerts',
            'notification' => $notification,
            'data' => $alert['data']
        ]);

        $headers = [
            'Authorization: key=' . $fcmServerKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($fcmUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        */

        return true;
    }

    /**
     * Log alert to database
     */
    private function logAlert($alert)
    {
        try {
            $this->db->query("
                INSERT INTO alert_log (severity, title, message, alert_data, created_at)
                VALUES (?, ?, ?, ?, ?)
            ", [
                $alert['severity'],
                $alert['title'],
                $alert['message'],
                json_encode($alert['data']),
                $alert['timestamp']
            ]);
        } catch (Exception $e) {
            error_log("Failed to log alert: " . $e->getMessage());
        }
    }

    /**
     * Get recent alerts
     */
    public function getRecentAlerts($limit = 50)
    {
        try {
            return $this->db->fetchAll("
                SELECT * FROM alert_log
                ORDER BY created_at DESC
                LIMIT ?
            ", [$limit]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Test alert system
     */
    public function sendTestAlert()
    {
        return $this->sendAlert(
            'info',
            'Test Alert',
            'This is a test alert to verify your notification system is working correctly.',
            ['test' => true, 'source' => 'manual_test']
        );
    }
}
