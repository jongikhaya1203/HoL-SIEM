<?php
/**
 * Register AI/ML Analytics Module
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Register AI/ML Module</title>";
echo "<style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #667eea; margin-bottom: 20px; }
.success { color: green; font-weight: bold; padding: 15px; background: #e8f5e9; border-radius: 5px; margin: 15px 0; }
.error { color: red; font-weight: bold; padding: 15px; background: #ffebee; border-radius: 5px; margin: 15px 0; }
.info { color: #666; padding: 15px; background: #e3f2fd; border-radius: 5px; margin: 15px 0; }
.btn { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; font-weight: 600; }
.btn:hover { background: #764ba2; }
</style></head><body>";

echo "<div class='box'>";
echo "<h1>🤖 Register AI/ML Analytics Module</h1>";

require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    echo "<p class='info'>✓ Database connected</p>";

    // Check if module already exists
    $existing = $db->fetchOne("SELECT * FROM modules WHERE module_code = 'AI_ML'");

    if ($existing) {
        echo "<p class='info'>ℹ️ AI/ML module already registered. Updating...</p>";

        $db->query("
            UPDATE modules SET
                module_name = 'AI/ML Analytics',
                category = 'Analytics',
                description = 'Advanced anomaly detection, predictive analytics, and intelligent insights powered by machine learning algorithms',
                icon = '🤖',
                status = 'implemented',
                implementation_level = 100,
                url = 'modules/ai_analytics.php',
                features = 'Anomaly Detection, Predictive Analytics, Pattern Analysis, AI Recommendations, Performance Forecasting, Risk Assessment',
                enabled = 1,
                updated_at = NOW()
            WHERE module_code = 'AI_ML'
        ");

        echo "<p class='success'>✅ AI/ML Analytics module updated successfully!</p>";
    } else {
        echo "<p class='info'>➕ Registering new AI/ML Analytics module...</p>";

        $db->query("
            INSERT INTO modules (
                module_code,
                module_name,
                category,
                description,
                icon,
                status,
                implementation_level,
                url,
                features,
                display_order,
                enabled
            ) VALUES (
                'AI_ML',
                'AI/ML Analytics',
                'Analytics',
                'Advanced anomaly detection, predictive analytics, and intelligent insights powered by machine learning algorithms',
                '🤖',
                'implemented',
                100,
                'modules/ai_analytics.php',
                'Anomaly Detection, Predictive Analytics, Pattern Analysis, AI Recommendations, Performance Forecasting, Risk Assessment',
                19,
                1
            )
        ");

        echo "<p class='success'>✅ AI/ML Analytics module registered successfully!</p>";
    }

    // Verify registration
    $module = $db->fetchOne("SELECT * FROM modules WHERE module_code = 'AI_ML'");

    if ($module) {
        echo "<div class='info'>";
        echo "<h3>Module Details:</h3>";
        echo "<p><strong>Code:</strong> " . htmlspecialchars($module['module_code']) . "</p>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($module['module_name']) . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($module['status']) . "</p>";
        echo "<p><strong>Implementation:</strong> " . htmlspecialchars($module['implementation_level']) . "%</p>";
        echo "<p><strong>URL:</strong> " . htmlspecialchars($module['url']) . "</p>";
        echo "<p><strong>Enabled:</strong> " . ($module['enabled'] ? 'Yes' : 'No') . "</p>";
        echo "</div>";

        echo "<p style='margin-top: 30px;'>";
        echo "<a href='modules/ai_analytics.php' class='btn'>🤖 Open AI/ML Analytics →</a>";
        echo "<a href='index.php' class='btn' style='background: #4CAF50;'>🏠 Back to Dashboard</a>";
        echo "<a href='feature_status.php' class='btn' style='background: #FF9800;'>📊 View Feature Status</a>";
        echo "</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}

echo "</div>";
echo "</body></html>";
?>
