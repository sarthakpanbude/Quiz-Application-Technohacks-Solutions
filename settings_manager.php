<?php
// settings_manager.php
// Centralized settings management service for the Quiz Platform

class SettingsManager {
    private static $settings = null;
    private static $pdo = null;

    public static function init($pdo) {
        self::$pdo = $pdo;
        self::loadSettings();
    }

    private static function loadSettings($forceReload = false) {
        if (self::$settings !== null && !$forceReload) {
            return;
        }

        require __DIR__ . '/settings_schema.php';
        global $DEFAULT_SETTINGS;

        // Load default values from schema
        self::$settings = [];
        foreach ($DEFAULT_SETTINGS as $category => $keys) {
            foreach ($keys as $key => $meta) {
                self::$settings[$key] = $meta['value'];
            }
        }

        // Apply database overrides
        if (self::$pdo) {
            try {
                $stmt = self::$pdo->query("SELECT setting_key, setting_value FROM global_settings");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Exception $e) {
                // Table might not exist yet during migration
            }
        }
    }

    public static function get($key, $default = null) {
        self::loadSettings();
        if (!array_key_exists($key, self::$settings)) {
            self::logDebug("Setting loaded: key='$key' (NOT FOUND IN SCHEMA), returning default='" . (is_array($default) ? json_encode($default) : $default) . "'");
            return $default;
        }
        $val = self::$settings[$key];
        self::logDebug("Setting loaded & applied: key='$key', value='" . (is_array($val) ? json_encode($val) : $val) . "'");
        return $val;
    }

    public static function getBool($key, $default = false) {
        $val = self::get($key, $default);
        return filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1 || $val === 'Enabled';
    }

    public static function getInt($key, $default = 0) {
        return intval(self::get($key, $default));
    }

    public static function getFloat($key, $default = 0.0) {
        return floatval(self::get($key, $default));
    }

    public static function set($key, $value, $category = 'General') {
        self::loadSettings();
        self::$settings[$key] = $value;
        if (self::$pdo) {
            $stmt = self::$pdo->prepare("INSERT INTO global_settings (setting_key, setting_value, category) VALUES (?, ?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
            $stmt->execute([$key, $value, $category]);
        }
        self::logDebug("Setting updated: key='$key', value='$value', category='$category'");
    }

    private static function logDebug($message) {
        $logFile = __DIR__ . '/settings_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] $message\n", 3, $logFile);
    }
}
