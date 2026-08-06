<?php
/**
 * Po'Boy Server Side Analytics - Ingestion & Database Query Endpoint v0.9.0-beta
 * GitHub: github.com/dadelonglegs/poboy
 */

$serverStartTime = microtime(true);
require_once __DIR__ . '/config.php';

if (ALLOW_ALL_CORS) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-PoBoy-Version");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (!class_exists('PDO')) return null;

    try {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        if ($driver === 'mysql' && defined('MYSQL_HOST')) {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . (defined('MYSQL_PORT') ? MYSQL_PORT : 3306) . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else {
            if (!in_array('sqlite', PDO::getAvailableDrivers())) return null;
            $sqlitePath = defined('POBOY_DB_PATH') ? POBOY_DB_PATH : (defined('SQLITE_PATH') ? SQLITE_PATH : __DIR__ . '/logs/poboy.sqlite');
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        initDatabase($pdo);
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

function initDatabase($pdo) {
    if (DB_DRIVER === 'mysql') {
        $sql = "CREATE TABLE IF NOT EXISTS poboy_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(64) NOT NULL,
            friendly_username VARCHAR(64) NOT NULL,
            session_id VARCHAR(64),
            channel_group VARCHAR(64),
            first_touch_source VARCHAR(64),
            first_touch_campaign VARCHAR(64),
            gclid VARCHAR(128),
            fbclid VARCHAR(128),
            ip_address VARCHAR(45),
            country VARCHAR(64),
            city VARCHAR(64),
            has_gps TINYINT(1) DEFAULT 0,
            has_converted TINYINT(1) DEFAULT 0,
            received_at DATETIME NOT NULL,
            server_timestamp INT NOT NULL,
            telemetry_json LONGTEXT NOT NULL,
            INDEX idx_pb_user (user_id),
            INDEX idx_pb_session (session_id),
            INDEX idx_pb_channel (channel_group),
            INDEX idx_pb_gclid (gclid),
            INDEX idx_pb_fbclid (fbclid),
            INDEX idx_pb_timestamp (server_timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS poboy_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            friendly_username TEXT NOT NULL,
            session_id TEXT,
            channel_group TEXT,
            first_touch_source TEXT,
            first_touch_campaign TEXT,
            gclid TEXT,
            fbclid TEXT,
            ip_address TEXT,
            country TEXT,
            city TEXT,
            has_gps INTEGER DEFAULT 0,
            has_converted INTEGER DEFAULT 0,
            received_at TEXT NOT NULL,
            server_timestamp INTEGER NOT NULL,
            telemetry_json TEXT NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_pb_user ON poboy_events(user_id);
        CREATE INDEX IF NOT EXISTS idx_pb_session ON poboy_events(session_id);
        CREATE INDEX IF NOT EXISTS idx_pb_channel ON poboy_events(channel_group);
        CREATE INDEX IF NOT EXISTS idx_pb_gclid ON poboy_events(gclid);
        CREATE INDEX IF NOT EXISTS idx_pb_fbclid ON poboy_events(fbclid);
        CREATE INDEX IF NOT EXISTS idx_pb_time ON poboy_events(server_timestamp);
        ";
    }
    $pdo->exec($sql);
}

function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            $candidate = trim($ipList[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }
    return '127.0.0.1';
}

function getIPLocation($ip) {
    if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return [
            'country' => 'Local Network',
            'country_code' => 'US',
            'region' => 'Local',
            'city' => 'Localhost',
            'lat' => 37.7749,
            'lon' => -122.4194,
            'source' => 'Local Development'
        ];
    }

    $cacheFile = __DIR__ . '/logs/geoip_cache.json';
    $cache = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(@file_get_contents($cacheFile), true) ?: [];
    }

    if (isset($cache[$ip])) {
        return $cache[$ip];
    }

    $geoData = [
        'country' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'Unknown',
        'country_code' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX',
        'region' => 'Unknown',
        'city' => 'Unknown',
        'lat' => 0.0,
        'lon' => 0.0,
        'source' => 'Header'
    ];

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $apiJson = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,lat,lon", false, $ctx);
        if ($apiJson) {
            $parsed = json_decode($apiJson, true);
            if ($parsed && ($parsed['status'] ?? '') === 'success') {
                $geoData = [
                    'country' => $parsed['country'] ?? 'Unknown',
                    'country_code' => $parsed['countryCode'] ?? 'XX',
                    'region' => $parsed['regionName'] ?? '',
                    'city' => $parsed['city'] ?? 'Unknown',
                    'lat' => floatval($parsed['lat'] ?? 0),
                    'lon' => floatval($parsed['lon'] ?? 0),
                    'source' => 'GeoIP Service'
                ];
                $cache[$ip] = $geoData;
                @file_put_contents($cacheFile, json_encode($cache));
            }
        }
    } catch (Exception $e) {}

    return $geoData;
}

// FETCH LOGS FOR DASHBOARD
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    session_start();
    $authPass = $_GET['password'] ?? ($_SESSION['sc_authenticated'] ?? '');
    
    if ($authPass !== DASHBOARD_PASSWORD && ($_SESSION['sc_authenticated'] ?? false) !== true) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    header('Content-Type: application/json');
    $pdo = getDBConnection();
    $max = isset($_GET['limit']) ? intval($_GET['limit']) : 5000;

    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    if ($pdo) {
        $sql = "SELECT telemetry_json FROM poboy_events WHERE 1=1";
        $params = [];

        if ($startDate) {
            $sql .= " AND server_timestamp >= :start_ts";
            $params[':start_ts'] = strtotime($startDate . ' 00:00:00');
        }
        if ($endDate) {
            $sql .= " AND server_timestamp <= :end_ts";
            $params[':end_ts'] = strtotime($endDate . ' 23:59:59');
        }

        $sql .= " ORDER BY server_timestamp DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $max, PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll();
        $records = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row['telemetry_json'], true);
            if ($decoded) $records[] = $decoded;
        }
        echo json_encode($records);
        exit();
    } else {
        if (!file_exists(LOG_FILE_PATH)) {
            echo json_encode([]);
            exit();
        }
        $lines = file(LOG_FILE_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $records = [];
        $sliced = array_slice($lines, -$max);
        foreach ($sliced as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                if ($startDate || $endDate) {
                    $ts = $decoded['server_timestamp'] ?? 0;
                    if ($startDate && $ts < strtotime($startDate . ' 00:00:00')) continue;
                    if ($endDate && $ts > strtotime($endDate . ' 23:59:59')) continue;
                }
                $records[] = $decoded;
            }
        }
        echo json_encode(array_reverse($records));
        exit();
    }
}

// INGEST TELEMETRY POST PAYLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);

    if (!$payload) $payload = $_POST;

    if (empty($payload) || !isset($payload['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload format']);
        exit();
    }

    $clientIP = getClientIP();
    $geoDetected = getIPLocation($clientIP);

    $displayIP = $clientIP;
    if (defined('ANONYMIZE_IP') && ANONYMIZE_IP) {
        if (filter_var($clientIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $clientIP);
            $parts[3] = 'xxx';
            $displayIP = implode('.', $parts);
        }
    }

    if (isset($payload['telemetry']['location']) && is_array($payload['telemetry']['location'])) {
        if (empty($payload['telemetry']['location']['city'])) {
            $payload['telemetry']['location']['city'] = $geoDetected['city'] ?? 'Unknown';
            $payload['telemetry']['location']['region'] = $geoDetected['region'] ?? '';
            $payload['telemetry']['location']['country'] = $geoDetected['country'] ?? 'Unknown';
            $payload['telemetry']['location']['country_code'] = $geoDetected['country_code'] ?? 'XX';
        }
    }

    $payload['location'] = [
        'detected' => $geoDetected,
        'provided' => $payload['location']['provided'] ?? null,
        'has_provided_gps' => !empty($payload['location']['provided'])
    ];

    $nowStr = date('Y-m-d H:i:s');
    $nowTs = time();
    $executionTimeMs = round((microtime(true) - $serverStartTime) * 1000, 2);

    $serverTelemetry = [
        'server_hostname' => gethostname() ?: ($_SERVER['SERVER_NAME'] ?? 'Unknown Host'),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/cPanel',
        'http_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
        'cf_ray_id' => $_SERVER['HTTP_CF_RAY'] ?? null,
        'execution_time_ms' => $executionTimeMs,
        'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        'memory_peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
        'database_driver' => DB_DRIVER
    ];

    $record = [
        'received_at' => $nowStr,
        'server_timestamp' => $nowTs,
        'ip_address' => $displayIP,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'server_telemetry' => $serverTelemetry,
        'telemetry' => $payload
    ];

    $jsonPayload = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO poboy_events (
                user_id, friendly_username, session_id, channel_group, 
                first_touch_source, first_touch_campaign, gclid, fbclid, 
                ip_address, country, city, has_gps, has_converted, received_at, server_timestamp, telemetry_json
            ) VALUES (
                :user_id, :friendly_username, :session_id, :channel_group,
                :first_touch_source, :first_touch_campaign, :gclid, :fbclid,
                :ip_address, :country, :city, :has_gps, :has_converted, :received_at, :server_timestamp, :telemetry_json
            )");

            $firstTouch = $payload['first_touch'] ?? [];
            $clickIds = $payload['current_visit']['click_ids'] ?? [];
            $isConv = (!empty($payload['is_conversion']) || !empty($payload['conversion'])) ? 1 : 0;

            $stmt->execute([
                ':user_id' => $payload['user_id'],
                ':friendly_username' => $payload['friendly_username'] ?? '',
                ':session_id' => $payload['session_id'] ?? '',
                ':channel_group' => $payload['current_visit']['channel_group'] ?? 'Direct',
                ':first_touch_source' => $firstTouch['utms']['utm_source'] ?? 'direct',
                ':first_touch_campaign' => $firstTouch['utms']['utm_campaign'] ?? 'direct',
                ':gclid' => $clickIds['gclid'] ?? null,
                ':fbclid' => $clickIds['fbclid'] ?? null,
                ':ip_address' => $displayIP,
                ':country' => $geoDetected['country'] ?? 'Unknown',
                ':city' => $geoDetected['city'] ?? 'Unknown',
                ':has_gps' => !empty($payload['location']['provided']) ? 1 : 0,
                ':has_converted' => $isConv,
                ':received_at' => $nowStr,
                ':server_timestamp' => $nowTs,
                ':telemetry_json' => $jsonPayload
            ]);
        } catch (Exception $e) {}
    }

    if (file_exists(LOG_FILE_PATH) && (filesize(LOG_FILE_PATH) > (MAX_LOG_SIZE_MB * 1024 * 1024))) {
        @rename(LOG_FILE_PATH, LOG_FILE_PATH . '.' . date('Y-m-d_H-i-s') . '.bak');
    }
    @file_put_contents(LOG_FILE_PATH, $jsonPayload . "\n", FILE_APPEND | LOCK_EX);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'timestamp' => $nowTs,
        'ip_address' => $displayIP,
        'friendly_name' => $payload['friendly_username'] ?? '',
        'detected_city' => $geoDetected['city'],
        'detected_country' => $geoDetected['country'],
        'server_execution_time_ms' => $executionTimeMs,
        'server_software' => $serverTelemetry['server_software']
    ]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
