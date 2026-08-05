<?php
/**
 * Po'Boy's Data Layer - Universal Configuration File
 * GitHub: github.com/poboy/poboy-data-layer
 */

// Dashboard Password Authentication
define('DASHBOARD_PASSWORD', 'PoBoyPass2026!');

// Telemetry & Database File Paths
define('POBOY_DB_PATH', __DIR__ . '/logs/poboy.sqlite');
define('POBOY_LOG_JSONL', __DIR__ . '/logs/analytics_log.jsonl');

// Security Options
define('ALLOW_CORS_ALL', true);
