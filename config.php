<?php
/**
 * Po'Boy Server Side Analytics Configuration File
 * GitHub: github.com/dadelonglegs/poboy
 */

// Dashboard Password Authentication
if (!defined('DASHBOARD_PASSWORD')) define('DASHBOARD_PASSWORD', 'PoBoyPass2026!');

// Telemetry & Database File Paths
if (!defined('POBOY_DB_PATH')) define('POBOY_DB_PATH', __DIR__ . '/logs/poboy.sqlite');
if (!defined('POBOY_LOG_JSONL')) define('POBOY_LOG_JSONL', __DIR__ . '/logs/analytics_log.jsonl');
if (!defined('LOG_FILE_PATH')) define('LOG_FILE_PATH', POBOY_LOG_JSONL);

// Database Driver Configuration (sqlite or mysql)
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');
if (!defined('MAX_LOG_SIZE_MB')) define('MAX_LOG_SIZE_MB', 100);
if (!defined('ANONYMIZE_IP')) define('ANONYMIZE_IP', false);

// CORS Security Options
if (!defined('ALLOW_CORS_ALL')) define('ALLOW_CORS_ALL', true);
if (!defined('ALLOW_ALL_CORS')) define('ALLOW_ALL_CORS', true);
