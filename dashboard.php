<?php
/**
 * Po'Boy Server Side Analytics v0.9.0-beta
 * GitHub: github.com/dadelonglegs/poboy
 */

if (function_exists('opcache_invalidate')) { @opcache_invalidate(__FILE__, true); }
require_once __DIR__ . '/config.php';
session_start();

if (isset($_GET['logout'])) {
    unset($_SESSION['sc_authenticated']);
    header('Location: dashboard.php');
    exit();
}

$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === DASHBOARD_PASSWORD) {
        $_SESSION['sc_authenticated'] = true;
    } else {
        $loginError = "Invalid password. Please try again.";
    }
}

$isAuthenticated = $_SESSION['sc_authenticated'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Po'Boy Server Side Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg-dark: #050b14;
            --bg-card: #0b162a;
            --bg-card-hover: #122442;
            --border-color: rgba(251, 79, 20, 0.25);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --primary: #fb4f14;
            --primary-hover: #ff6b2b;
            --navy-brand: #002244;
            --navy-light: #162a4a;
            --primary-gradient: linear-gradient(135deg, #fb4f14 0%, #ff7a00 50%, #002244 100%);
            --accent-gradient: linear-gradient(135deg, #002244 0%, #0e1e38 60%, #fb4f14 100%);
            --accent-orange: #fb4f14;
            --accent-gold: #ffb703;
            --accent-green: #10b981;
            --accent-cyan: #38bdf8;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --radius-lg: 16px;
            --radius-md: 10px;
            --font-sans: 'Outfit', -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            font-family: var(--font-sans);
            line-height: 1.5;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* LOGIN SCREEN */
        .login-container {
            max-width: 440px;
            margin: 100px auto;
            padding: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 35px rgba(251, 79, 20, 0.15);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 5px;
            background: var(--primary-gradient);
        }
        .login-logo {
            font-size: 30px;
            font-weight: 800;
            background: linear-gradient(135deg, #fb4f14 0%, #ff8a50 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .login-sub {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        .form-group { margin-top: 20px; text-align: left; }
        .form-group label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: #050b14;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: var(--accent-orange);
            box-shadow: 0 0 15px rgba(251, 79, 20, 0.3);
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(251, 79, 20, 0.4);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(251, 79, 20, 0.6);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 10px;
            border-radius: var(--radius-md);
            font-size: 13px;
            margin-bottom: 16px;
        }

        /* MAIN LAYOUT */
        .header-bar {
            background: var(--navy-gradient);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }
        .brand-badge {
            font-size: 11px;
            background: rgba(251, 79, 20, 0.2);
            color: var(--accent-orange);
            border: 1px solid rgba(251, 79, 20, 0.4);
            padding: 3px 9px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .user-nav { display: flex; align-items: center; gap: 16px; }
        .logout-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            padding: 6px 14px;
            border: 1px solid var(--border-subtle);
            border-radius: 6px;
            transition: all 0.2s;
        }
        .logout-link:hover { color: #ffffff; border-color: var(--accent-orange); background: rgba(251, 79, 20, 0.1); }

        .container { max-width: 1400px; margin: 24px auto; padding: 0 24px; }

        /* HERO DATE BAR & TABS */
        .date-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-subtle);
            padding-bottom: 12px;
            overflow-x: auto;
        }
        .tab-btn {
            padding: 10px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .tab-btn:hover { color: #ffffff; border-color: var(--border-color); background: var(--bg-card-hover); }
        .tab-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--accent-orange);
            box-shadow: 0 4px 15px rgba(251, 79, 20, 0.3);
        }

        /* METRIC CARDS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
        }
        .metric-card:hover {
            border-color: var(--border-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--primary);
        }
        .metric-title { font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .metric-value { font-size: 32px; font-weight: 800; color: #ffffff; margin: 6px 0; font-family: var(--font-mono); }
        .metric-sub { font-size: 12px; color: var(--text-muted); }

        /* PRESET CHIPS & INPUTS */
        .preset-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .chip {
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        .chip:hover, .chip.active {
            background: rgba(251, 79, 20, 0.2);
            color: var(--accent-orange);
            border-color: var(--accent-orange);
        }

        .rule-input, .rule-select {
            padding: 8px 12px;
            background: #050b14;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 13px;
            outline: none;
        }
        .rule-input:focus, .rule-select:focus { border-color: var(--accent-orange); }

        /* PANELS & CARDS */
        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .panel-title { font-size: 18px; font-weight: 700; color: #ffffff; }
        .panel-sub { font-size: 13px; color: var(--text-secondary); }

        /* CHARTS / BREAKDOWNS */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .bar-row { margin-bottom: 12px; }
        .bar-label { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; font-weight: 500; }
        .bar-track { height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #fb4f14, #ff7a00); border-radius: 5px; transition: width 0.4s ease; }

        /* DATA TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .data-table th {
            padding: 14px 16px;
            background: rgba(0, 34, 68, 0.6);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-primary);
            vertical-align: middle;
        }
        .data-table tr:hover td { background: var(--bg-card-hover); }

        /* BADGES & BUTTONS */
        .friendly-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            background: rgba(251, 79, 20, 0.12);
            border: 1px solid rgba(251, 79, 20, 0.3);
            color: var(--accent-orange);
            border-radius: 6px;
            font-weight: 600;
            font-family: var(--font-mono);
            font-size: 12px;
        }
        .avatar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent-orange); display: inline-block; }

        .channel-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .channel-paid-search { background: rgba(251, 79, 20, 0.2); color: #fb4f14; border: 1px solid rgba(251, 79, 20, 0.4); }
        .channel-organic-search { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); }
        .channel-organic-social { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); }
        .channel-direct { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }

        .converted-badge {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.4);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
        }

        .tag {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border-subtle);
            border-radius: 4px;
            font-size: 11px;
            color: var(--text-secondary);
            margin-right: 4px;
            margin-bottom: 4px;
            font-family: var(--font-mono);
        }

        .btn-view {
            padding: 6px 12px;
            background: rgba(251, 79, 20, 0.15);
            border: 1px solid rgba(251, 79, 20, 0.3);
            color: var(--accent-orange);
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-view:hover { background: var(--primary); color: #ffffff; }

        /* MAP */
        #map { height: 420px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); }

        /* MODAL */
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-backdrop.active { display: flex; }
        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.9);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px; }
        .close-btn { font-size: 24px; cursor: pointer; color: var(--text-secondary); }
        .close-btn:hover { color: #ffffff; }

        .timeline-tree { border-left: 2px solid var(--border-color); padding-left: 20px; margin-left: 10px; }
        .timeline-item { position: relative; margin-bottom: 24px; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px; top: 4px;
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--accent-orange);
            box-shadow: 0 0 10px var(--accent-orange);
        }
        .timeline-title { font-weight: 700; font-size: 14px; color: #ffffff; margin-bottom: 4px; }
        .timeline-detail { background: #050b14; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-subtle); font-family: var(--font-mono); font-size: 12px; color: var(--text-secondary); line-height: 1.6; }

        /* FILTER RULES BUILDER */
        .rules-container { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
        .rule-row { display: flex; gap: 10px; align-items: center; background: #050b14; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-subtle); flex-wrap: wrap; }
        .btn-add-rule { padding: 8px 16px; background: rgba(251, 79, 20, 0.15); border: 1px dashed var(--accent-orange); color: var(--accent-orange); border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; width: 100%; text-align: center; }
        .btn-add-rule:hover { background: rgba(251, 79, 20, 0.25); }
        .btn-remove-rule { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
    </style>
</head>
<body>

<?php if (!$isAuthenticated): ?>
    <div class="login-container">
        <div class="login-logo"> Po'Boy Server Side</div>
        <div class="login-sub">Universal Visitor Telemetry & Attribution Engine</div>
        
        <?php if ($loginError): ?>
            <div class="error-msg"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Dashboard Authentication Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter password..." required autofocus>
            </div>
            <button type="submit" class="btn-primary">Unlock Dashboard ➔</button>
        </form>
    </div>
<?php else: ?>
    <header class="header-bar">
        <div class="brand">
            🥪 Po'Boy Server Side Analytics <span class="brand-badge">v0.9.0-beta</span>
        </div>
        <div class="user-nav">
            <span style="font-size: 13px; color: var(--text-secondary);">GitHub: <a href="https://github.com/dadelonglegs/poboy" target="_blank" style="color: var(--accent-orange); text-decoration: none;">dadelonglegs/poboy</a></span>
            <a href="?logout=1" class="logout-link">Logout</a>
        </div>
    </header>

    <div class="container">
        <!-- HERO DATE RANGE BAR -->
        <div class="date-bar">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #ffffff;">📅 Date Range & Filter Control Hub</h3>
                <p style="font-size: 12px; color: var(--text-secondary);">Filter real-time telemetry logs by custom timeframes</p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <div class="preset-chips" style="margin-bottom: 0;">
                    <div class="chip" onclick="setDateRange('today')">Today</div>
                    <div class="chip" onclick="setDateRange('yesterday')">Yesterday</div>
                    <div class="chip" onclick="setDateRange('7days')">Last 7 Days</div>
                    <div class="chip" onclick="setDateRange('30days')">Last 30 Days</div>
                    <div class="chip active" onclick="setDateRange('all')" id="chip-all">All Time</div>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="date" id="startDate" class="rule-input">
                    <span style="color: var(--text-muted); font-size: 12px;">to</span>
                    <input type="date" id="endDate" class="rule-input">
                    <button onclick="loadLogs()" class="btn-view" style="padding: 8px 14px; background: var(--primary); color:#ffffff;">Apply</button>
                </div>
            </div>
        </div>

        <!-- TOP METRICS GRID -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Unique Tracked Visitors</div>
                <div class="metric-value" id="stat-unique">0</div>
                <div class="metric-sub">Self-healing handles</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Sessions Generated</div>
                <div class="metric-value" id="stat-sessions">0</div>
                <div class="metric-sub">Auto 30-min timeouts</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Converted Leads</div>
                <div class="metric-value" id="stat-conversions" style="color: var(--accent-green);">0</div>
                <div class="metric-sub">GTM Pixel Triggers</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Locations Mapped</div>
                <div class="metric-value" id="stat-mapped" style="color: var(--accent-cyan);">0</div>
                <div class="metric-sub">IP GeoIP & GPS Pins</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Active Filter Rules</div>
                <div class="metric-value" id="stat-rules-count" style="color: var(--accent-orange);">0</div>
                <div class="metric-sub">Layered criteria</div>
            </div>
        </div>

        <!-- INTERACTIVE TAB NAVIGATION -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('overview')">📊 Analytics Overview</button>
            <button class="tab-btn" onclick="switchTab('stream')">⚡ Visitor Telemetry Stream</button>
            <button class="tab-btn" onclick="switchTab('map')">🗺️ GeoIP & GPS Map</button>
            <button class="tab-btn" onclick="switchTab('rules')">🎛️ Filter Query Builder</button>
            <button class="tab-btn" onclick="switchTab('sandbox')">🧪 Telemetry Sandbox</button>
        </div>

        <!-- TAB 1: ANALYTICS OVERVIEW BREAKDOWN -->
        <div id="tab-overview" class="tab-content">
            <div class="analytics-grid">
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">📣 Acquisition Channels</div>
                    </div>
                    <div id="breakdown-channels">Loading channel breakdown...</div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">💻 Device Categories & OS</div>
                    </div>
                    <div id="breakdown-devices">Loading device breakdown...</div>
                </div>
            </div>
        </div>

        <!-- TAB 2: VISITOR STREAM TABLE -->
        <div id="tab-stream" class="tab-content" style="display: none;">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Enterprise Visitor Telemetry Stream</div>
                        <div class="panel-sub">Real-time visitor logs matching active date range and rules</div>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button onclick="exportScreenCSV()" class="btn-view">📥 Export Screen View (CSV)</button>
                        <button onclick="exportFullRawCSV()" class="btn-view" style="background: var(--primary); color:#fff;">🌐 Export Full Raw Dataset</button>
                        <button onclick="loadLogs()" class="btn-view">🔄 Refresh</button>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Friendly Handle / ID</th>
                            <th>Session / Visits</th>
                            <th>Hardware & OS</th>
                            <th>Location</th>
                            <th>Channel Grouping</th>
                            <th>UTMs & Click IDs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <tr><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">Loading analytics logs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: MAP CARD -->
        <div id="tab-map" class="tab-content" style="display: none;">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">🌍 Visitor Location Map & Density Visualizer</div>
                        <div class="panel-sub">Live geographical map updating synchronously with active layer filters</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span style="font-size: 12px; color: var(--text-secondary);">Mode:</span>
                        <button id="btnMapPins" onclick="setMapMode('pins')" class="btn-view" style="background: var(--primary); color:#ffffff;">📍 Pin Markers</button>
                        <button id="btnMapHeat" onclick="setMapMode('heatmap')" class="btn-view" style="background: rgba(255,255,255,0.05);">🔥 Heatmap Mode</button>
                    </div>
                </div>
                <div id="map"></div>
            </div>
        </div>

        <!-- TAB 4: QUERY BUILDER -->
        <div id="tab-rules" class="tab-content" style="display: none;">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">🎛️ Telemetry & Parameter Query Builder</div>
                        <div class="panel-sub">Filter visitors by Hardware (RAM/CPU/Retina), Location, Click IDs, and UTMs</div>
                    </div>
                    <button onclick="clearAllFilters()" class="btn-view">🗑️ Clear All Rules</button>
                </div>

                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; font-weight: 600;">Quick Segment Presets:</div>
                <div class="preset-chips">
                    <div class="chip" onclick="applyPreset('converted')">🎯 Converted Leads</div>
                    <div class="chip" onclick="applyPreset('paid')">⚡ Paid Campaigns (gclid / fbclid)</div>
                    <div class="chip" onclick="applyPreset('organic')">🌿 Organic Search</div>
                    <div class="chip" onclick="applyPreset('social')">💬 Organic Social</div>
                    <div class="chip" onclick="applyPreset('returning')">🔄 Returning Visitors</div>
                    <div class="chip" onclick="applyPreset('gps')">🎯 GPS Pins</div>
                </div>

                <div class="rules-container" id="rulesContainer"></div>
                <button onclick="addFilterRule()" class="btn-add-rule">+ Add Layered Filter Rule</button>
            </div>
        </div>

        <!-- TAB 5: SIMULATOR -->
        <div id="tab-sandbox" class="tab-content" style="display: none;">
            <div class="panel">
                <div class="panel-title" style="margin-bottom: 8px; color: var(--accent-orange);">🧪 Telemetry Testing Sandbox</div>
                <div class="panel-sub" style="margin-bottom: 20px;">Simulate incoming visits and GTM conversion triggers with full GA4-rivaling telemetry.</div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button onclick="runSimulation('paid')" class="btn-primary" style="width: auto; margin-top: 0; padding: 12px 24px;">Fire Visit (Paid Google Campaign)</button>
                    <button onclick="runSimulation('conversion')" class="btn-primary" style="width: auto; margin-top: 0; padding: 12px 24px; background: #10b981;">Fire Conversion Event (GTM Pixel)</button>
                    <button onclick="runSimulation('gps')" class="btn-primary" style="width: auto; margin-top: 0; padding: 12px 24px; background: #38bdf8; color:#000;">Fire Visit (GPS Provided Pin)</button>
                </div>
                <div id="simResult" style="margin-top: 16px; font-size: 13px; font-family: var(--font-mono); color: var(--accent-green);"></div>
            </div>
        </div>
    </div>

    <!-- JOURNEY MODAL -->
    <div class="modal-backdrop" id="journeyModal">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 style="font-size: 20px; color: #ffffff;" id="modalHandle">User Journey & Telemetry Inspector</h3>
                    <p style="font-size: 13px; color: var(--text-secondary);" id="modalUserId">ID: --</p>
                </div>
                <span class="close-btn" onclick="closeModal()">✕</span>
            </div>
            <div class="timeline-tree" id="modalTimeline"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        let rawLogs = [];
        let filteredLogs = [];
        let activeRules = [];
        let map = null;
        let markersGroup = null;
        let heatLayer = null;
        let currentMapMode = 'pins';

        const AVAILABLE_FIELDS = [
            { id: 'friendly_username', name: 'Friendly Handle', type: 'text' },
            { id: 'user_id', name: 'User ID (UUID)', type: 'text' },
            { id: 'is_conversion', name: 'Conversion Triggered (true/false)', type: 'text' },
            { id: 'channel_group', name: 'Channel Grouping', type: 'text' },
            { id: 'utm_source', name: 'UTM Source', type: 'text' },
            { id: 'utm_medium', name: 'UTM Medium', type: 'text' },
            { id: 'utm_campaign', name: 'UTM Campaign', type: 'text' },
            { id: 'click_id_type', name: 'Click ID Type (gclid, fbclid, etc.)', type: 'text' },
            { id: 'visit_count', name: 'Visit / Touch Count', type: 'number' },
            { id: 'country', name: 'Country', type: 'text' },
            { id: 'city', name: 'City', type: 'text' }
        ];

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tabId}`).style.display = 'block';
            event.currentTarget.classList.add('active');
            if (tabId === 'map') {
                setTimeout(initMap, 200);
            }
        }

        function initMap() {
            if (map) { map.invalidateSize(); return; }
            map = L.map('map').setView([20, 0], 2);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
                maxZoom: 18
            }).addTo(map);
            markersGroup = L.layerGroup().addTo(map);
            renderDashboard(filteredLogs);
        }

        function setMapMode(mode) {
            currentMapMode = mode;
            document.getElementById('btnMapPins').style.background = mode === 'pins' ? 'var(--primary)' : 'rgba(255,255,255,0.05)';
            document.getElementById('btnMapPins').style.color = mode === 'pins' ? '#ffffff' : 'var(--text-secondary)';
            document.getElementById('btnMapHeat').style.background = mode === 'heatmap' ? 'var(--primary)' : 'rgba(255,255,255,0.05)';
            document.getElementById('btnMapHeat').style.color = mode === 'heatmap' ? '#ffffff' : 'var(--text-secondary)';
            renderDashboard(filteredLogs);
        }

        function setDateRange(range) {
            const today = new Date();
            const formatDate = d => d.toISOString().split('T')[0];

            if (range === 'today') {
                document.getElementById('startDate').value = formatDate(today);
                document.getElementById('endDate').value = formatDate(today);
            } else if (range === 'yesterday') {
                const yest = new Date(today);
                yest.setDate(yest.getDate() - 1);
                document.getElementById('startDate').value = formatDate(yest);
                document.getElementById('endDate').value = formatDate(yest);
            } else if (range === '7days') {
                const d7 = new Date(today);
                d7.setDate(d7.getDate() - 7);
                document.getElementById('startDate').value = formatDate(d7);
                document.getElementById('endDate').value = formatDate(today);
            } else if (range === '30days') {
                const d30 = new Date(today);
                d30.setDate(d30.getDate() - 30);
                document.getElementById('startDate').value = formatDate(d30);
                document.getElementById('endDate').value = formatDate(today);
            } else if (range === 'all') {
                document.getElementById('startDate').value = '';
                document.getElementById('endDate').value = '';
            }
            loadLogs();
        }

        async function loadLogs() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            let url = 'log.php?action=fetch';
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;

            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Failed to load logs');
                rawLogs = await res.json();
                applyFilterRules();
            } catch (err) {
                document.getElementById('logTableBody').innerHTML = `<tr><td colspan="7" style="text-align:center; color:#ef4444; padding:30px;">Error loading logs: ${err.message}</td></tr>`;
            }
        }

        function addFilterRule(fieldId = 'channel_group', op = 'equals', val = '') {
            const ruleId = 'rule_' + Math.random().toString(36).substring(7);
            activeRules.push({ id: ruleId, field: fieldId, operator: op, value: val });
            renderRulesUI();
            applyFilterRules();
        }

        function removeFilterRule(ruleId) {
            activeRules = activeRules.filter(r => r.id !== ruleId);
            renderRulesUI();
            applyFilterRules();
        }

        function clearAllFilters() {
            activeRules = [];
            renderRulesUI();
            applyFilterRules();
        }

        function renderRulesUI() {
            const container = document.getElementById('rulesContainer');
            document.getElementById('stat-rules-count').textContent = activeRules.length;

            if (activeRules.length === 0) {
                container.innerHTML = '<div style="font-size: 13px; color: var(--text-muted); font-style: italic;">No active filter rules. Click "+ Add Layered Filter Rule" or select a preset above!</div>';
                return;
            }

            container.innerHTML = activeRules.map((rule, idx) => {
                const fieldOptions = AVAILABLE_FIELDS.map(f => `<option value="${f.id}" ${f.id === rule.field ? 'selected' : ''}>${f.name}</option>`).join('');
                return `
                    <div class="rule-row">
                        <span style="font-size: 12px; font-weight: 700; color: var(--accent-orange); font-family: var(--font-mono);">${idx === 0 ? 'WHERE' : 'AND'}</span>
                        <select class="rule-select" onchange="updateRule('${rule.id}', 'field', this.value)">
                            ${fieldOptions}
                        </select>
                        <select class="rule-select" onchange="updateRule('${rule.id}', 'operator', this.value)">
                            <option value="equals" ${rule.operator === 'equals' ? 'selected' : ''}>equals</option>
                            <option value="contains" ${rule.operator === 'contains' ? 'selected' : ''}>contains</option>
                            <option value="not_equals" ${rule.operator === 'not_equals' ? 'selected' : ''}>does not equal</option>
                        </select>
                        <input type="text" class="rule-input" style="flex: 1; min-width: 200px;" value="${rule.value}" placeholder="Enter search value..." onkeyup="updateRule('${rule.id}', 'value', this.value)">
                        <button onclick="removeFilterRule('${rule.id}')" class="btn-remove-rule">✕ Remove</button>
                    </div>
                `;
            }).join('');
        }

        function updateRule(ruleId, key, val) {
            const r = activeRules.find(item => item.id === ruleId);
            if (r) {
                r[key] = val;
                applyFilterRules();
            }
        }

        function applyPreset(type) {
            activeRules = [];
            if (type === 'converted') addFilterRule('is_conversion', 'equals', 'true');
            else if (type === 'paid') addFilterRule('click_id_type', 'contains', 'gclid');
            else if (type === 'organic') addFilterRule('channel_group', 'equals', 'Organic Search');
            else if (type === 'social') addFilterRule('channel_group', 'equals', 'Organic Social');
            else if (type === 'returning') addFilterRule('visit_count', 'greater_than', '1');
            else if (type === 'gps') addFilterRule('location_type', 'equals', 'GPS Provided');
        }

        function matchesRule(log, rule) {
            const tel = log.telemetry || {};
            const loc = tel.location || {};
            const current = tel.current_visit || {};
            const utms = current.utms || {};
            const cids = current.click_ids || {};

            let val = '';
            if (rule.field === 'friendly_username') val = tel.friendly_username || '';
            else if (rule.field === 'user_id') val = tel.user_id || '';
            else if (rule.field === 'is_conversion') val = (tel.is_conversion || tel.conversion) ? 'true' : 'false';
            else if (rule.field === 'channel_group') val = current.channel_group || tel.first_touch?.channel_group || '';
            else if (rule.field === 'utm_source') val = utms.utm_source || '';
            else if (rule.field === 'utm_medium') val = utms.utm_medium || '';
            else if (rule.field === 'utm_campaign') val = utms.utm_campaign || '';
            else if (rule.field === 'click_id_type') val = Object.keys(cids).join(', ');
            else if (rule.field === 'visit_count') val = tel.visit_count || 1;
            else if (rule.field === 'country') val = loc.detected?.country || '';
            else if (rule.field === 'city') val = loc.detected?.city || '';

            const strVal = String(val).toLowerCase();
            const targetVal = String(rule.value).toLowerCase();

            if (rule.operator === 'equals') return strVal === targetVal;
            if (rule.operator === 'contains') return strVal.includes(targetVal);
            if (rule.operator === 'not_equals') return strVal !== targetVal;

            return true;
        }

        function applyFilterRules() {
            filteredLogs = rawLogs;
            if (activeRules.length > 0) {
                filteredLogs = rawLogs.filter(log => {
                    return activeRules.every(rule => matchesRule(log, rule));
                });
            }
            renderDashboard(filteredLogs);
        }

        function getChannelClass(channel) {
            const c = (channel || '').toLowerCase();
            if (c.includes('organic search')) return 'channel-organic-search';
            if (c.includes('social')) return 'channel-organic-social';
            if (c.includes('paid')) return 'channel-paid-search';
            return 'channel-direct';
        }

        function renderBreakdowns(logs) {
            const channels = {};
            const devices = {};
            const total = logs.length || 1;

            logs.forEach(l => {
                const t = l.telemetry || {};
                const ch = t.current_visit?.channel_group || t.first_touch?.channel_group || 'Direct';
                channels[ch] = (channels[ch] || 0) + 1;

                const meta = t.telemetry || t.browser || {};
                const dev = meta.device_category || 'Desktop';
                devices[dev] = (devices[dev] || 0) + 1;
            });

            let chHtml = '';
            for (let c in channels) {
                const pct = Math.round((channels[c] / total) * 100);
                chHtml += `
                    <div class="bar-row">
                        <div class="bar-label"><span>${c}</span><span>${channels[c]} (${pct}%)</span></div>
                        <div class="bar-track"><div class="bar-fill" style="width: ${pct}%;"></div></div>
                    </div>
                `;
            }
            document.getElementById('breakdown-channels').innerHTML = chHtml || '<div style="color:var(--text-muted);">No channel data available.</div>';

            let devHtml = '';
            for (let d in devices) {
                const pct = Math.round((devices[d] / total) * 100);
                devHtml += `
                    <div class="bar-row">
                        <div class="bar-label"><span>${d}</span><span>${devices[d]} (${pct}%)</span></div>
                        <div class="bar-track"><div class="bar-fill" style="width: ${pct}%; background: linear-gradient(90deg, #38bdf8, #002244);"></div></div>
                    </div>
                `;
            }
            document.getElementById('breakdown-devices').innerHTML = devHtml || '<div style="color:var(--text-muted);">No device data available.</div>';
        }

        function renderDashboard(logs) {
            renderBreakdowns(logs);

            if (map && markersGroup) {
                markersGroup.clearLayers();
                if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

                const heatPoints = [];
                logs.forEach(log => {
                    const tel = log.telemetry || {};
                    const loc = tel.location || {};
                    const provided = loc.provided;
                    const detected = loc.detected;

                    let lat = null; let lon = null; let isGps = false; let label = '';

                    if (provided && provided.latitude && provided.longitude) {
                        lat = provided.latitude; lon = provided.longitude; isGps = true;
                        label = `🎯 User-Provided GPS: ${provided.latitude.toFixed(4)}, ${provided.longitude.toFixed(4)}`;
                    } else if (detected && detected.lat && detected.lon) {
                        lat = detected.lat; lon = detected.lon;
                        label = `🌐 Detected IP: ${detected.city || ''}, ${detected.country || ''}`;
                    }

                    if (lat !== null && lon !== null && (lat !== 0 || lon !== 0)) {
                        heatPoints.push([lat, lon, isGps ? 1.0 : 0.6]);

                        if (currentMapMode === 'pins') {
                            const marker = L.circleMarker([lat, lon], {
                                radius: isGps ? 8 : 6,
                                fillColor: isGps ? '#10b981' : '#fb4f14',
                                color: '#ffffff',
                                weight: 1.5,
                                opacity: 1,
                                fillOpacity: 0.8
                            });

                            marker.bindPopup(`
                                <div style="font-family: sans-serif; color: #002244;">
                                    <strong style="font-size: 14px;">${tel.friendly_username || 'Visitor'}</strong><br>
                                    <span style="font-size: 12px; color: #475569;">${label}</span><br>
                                    <span style="font-size: 11px; color: #64748b;">Channel: ${tel.current_visit?.channel_group || 'Direct'}</span>
                                </div>
                            `);
                            markersGroup.addLayer(marker);
                        }
                    }
                });

                if (currentMapMode === 'heatmap' && heatPoints.length > 0 && typeof L.heatLayer === 'function') {
                    heatLayer = L.heatLayer(heatPoints, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 17,
                        gradient: { 0.2: '#002244', 0.5: '#38bdf8', 0.8: '#fb4f14', 1.0: '#ff6b2b' }
                    }).addTo(map);
                }
            }

            const uniqueUsers = new Set();
            const sessionsSet = new Set();
            let conversionsCount = 0;
            let mappedCount = 0;

            logs.forEach(log => {
                const tel = log.telemetry || {};
                if (tel.user_id) uniqueUsers.add(tel.user_id);
                if (tel.session_id) sessionsSet.add(tel.session_id);
                if (tel.is_conversion || tel.conversion) conversionsCount++;

                const loc = tel.location || {};
                if ((loc.provided && loc.provided.latitude) || (loc.detected && loc.detected.lat)) mappedCount++;
            });

            document.getElementById('stat-unique').textContent = uniqueUsers.size;
            document.getElementById('stat-sessions').textContent = sessionsSet.size || logs.length;
            document.getElementById('stat-conversions').textContent = conversionsCount;
            document.getElementById('stat-mapped').textContent = mappedCount;

            const tbody = document.getElementById('logTableBody');
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding:30px;">No telemetry logs match your active filter rules and date range.</td></tr>';
                return;
            }

            tbody.innerHTML = logs.map((log, index) => {
                const tel = log.telemetry || {};
                const handle = tel.friendly_username || 'Unknown Visitor';
                const userId = tel.user_id || 'pb_anon';
                const channel = tel.current_visit?.channel_group || tel.first_touch?.channel_group || 'Direct';
                const channelClass = getChannelClass(channel);
                const meta = tel.telemetry || tel.browser || {};
                const isConv = tel.is_conversion || tel.conversion;

                const loc = tel.location || {};
                const det = loc.detected || {};
                const prov = loc.provided || {};
                
                let locStr = 'Unknown';
                if (prov && prov.latitude) locStr = `GPS (${prov.latitude.toFixed(2)}, ${prov.longitude.toFixed(2)})`;
                else if (det && det.city) locStr = `${det.city}, ${det.country_code || det.country}`;

                const hwInfo = `${meta.os_name || 'OS'} • ${meta.browser_name || 'Browser'} • ${meta.device_category || 'Desktop'}`;

                const cids = tel.current_visit?.click_ids || {};
                let tagsHtml = '';
                if (isConv) {
                    tagsHtml += `<span class="converted-badge">🎯 CONVERTED</span> `;
                }
                for (let k in cids) {
                    tagsHtml += `<span class="tag" style="color:#fb4f14; background:rgba(251,79,20,0.15);">${k}: ${cids[k]}</span>`;
                }
                const utms = tel.current_visit?.utms || {};
                for (let u in utms) {
                    tagsHtml += `<span class="tag">${u}: ${utms[u]}</span>`;
                }
                if (!tagsHtml) tagsHtml = '<span class="tag">Organic</span>';

                return `
                    <tr>
                        <td>
                            <div class="friendly-badge"><span class="avatar-dot"></span>${handle}</div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-family: var(--font-mono);">${userId.substring(0, 18)}...</div>
                        </td>
                        <td>
                            <div style="font-size: 13px; font-weight: 600;">Visits: ${tel.visit_count || 1}</div>
                            <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">${(tel.session_id || 'sess').substring(0, 12)}...</div>
                        </td>
                        <td style="font-size: 12px; font-family: var(--font-mono); color: var(--text-secondary);">${hwInfo}</td>
                        <td style="font-weight: 600; font-size: 13px;">${locStr}</td>
                        <td><span class="channel-badge ${channelClass}">${channel}</span></td>
                        <td>${tagsHtml}</td>
                        <td><button class="btn-view" onclick="openJourney(${index})">Inspect Telemetry</button></td>
                    </tr>
                `;
            }).join('');
        }

        function openJourney(index) {
            const log = filteredLogs[index];
            const tel = log.telemetry || {};
            const meta = tel.telemetry || tel.browser || {};
            document.getElementById('modalHandle').textContent = tel.friendly_username || 'User Telemetry Details';
            document.getElementById('modalUserId').textContent = `User ID: ${tel.user_id || 'Unknown'} | Session ID: ${tel.session_id || 'N/A'}`;

            const first = tel.first_touch || {};
            const loc = tel.location || {};
            const conv = tel.conversion;

            let treeHtml = `
                ${conv ? `
                <div class="timeline-item">
                    <div class="timeline-title" style="color:#10b981;">🎯 Conversion Pixel Triggered</div>
                    <div class="timeline-detail" style="border-color:#10b981;">
                        Conversion Name: ${conv.name || 'Lead Submitted'}<br>
                        Conversion Value: $${conv.value || 0}<br>
                        Triggered Timestamp: ${conv.timestamp || log.received_at}
                    </div>
                </div>` : ''}
                <div class="timeline-item">
                    <div class="timeline-title">🖥️ Hardware, OS & Browser Details</div>
                    <div class="timeline-detail">
                        OS: ${meta.os_name || 'N/A'} (${meta.os_version || 'N/A'}) | Browser: ${meta.browser_name || 'N/A'}<br>
                        Device Category: ${meta.device_category || 'Desktop'} | Screen: ${meta.screen_resolution}<br>
                        Timezone: ${meta.timezone} | Network: ${meta.connection_type || '4g'}
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-title">🌍 Location Intelligence</div>
                    <div class="timeline-detail">
                        Detected IP Location: ${JSON.stringify(loc.detected || {})}<br>
                        User-Provided GPS Location: ${JSON.stringify(loc.provided || 'None Provided')}
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-title">🎯 First Touch Acquisition (${first.channel_group || 'Direct'})</div>
                    <div class="timeline-detail">
                        Timestamp: ${first.timestamp || 'N/A'}<br>
                        Page: ${first.page || 'N/A'}<br>
                        UTMs: ${JSON.stringify(first.utms || {})}<br>
                        Click IDs: ${JSON.stringify(first.click_ids || {})}
                    </div>
                </div>
            `;
            document.getElementById('modalTimeline').innerHTML = treeHtml;
            document.getElementById('journeyModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('journeyModal').classList.remove('active');
        }

        function exportScreenCSV() {
            if (!filteredLogs.length) return alert('No active log rows on screen to export.');
            let csv = 'Timestamp (UTC),Friendly Handle,User ID,Session ID,Is Converted,Channel Grouping,IP Address,City,Country,Location Type,First Touch Source,First Touch Campaign,Page URL\n';
            filteredLogs.forEach(l => {
                const t = l.telemetry || {};
                const loc = t.location || {};
                const city = loc.provided?.latitude ? 'GPS Pin' : (loc.detected?.city || 'Unknown');
                const country = loc.detected?.country || 'Unknown';
                const type = loc.provided?.latitude ? 'User Provided GPS' : 'Server Detected IP';
                const first = t.first_touch || {};
                const utms = first.utms || {};
                const isConv = (t.is_conversion || t.conversion) ? 'TRUE' : 'FALSE';
                csv += `"${l.received_at}","${t.friendly_username}","${t.user_id}","${t.session_id || ''}","${isConv}","${t.current_visit?.channel_group || 'Direct'}","${l.ip_address}","${city}","${country}","${type}","${utms.utm_source || 'direct'}","${utms.utm_campaign || 'direct'}","${t.current_visit?.page || ''}"\n`;
            });
            downloadCSVBlob(csv, 'poboy_filtered_screen_report.csv');
        }

        function exportFullRawCSV() {
            if (!rawLogs.length) return alert('No raw logs available for export.');
            let csv = 'Server Received At,Server Timestamp,User ID,Friendly Handle,Session ID,Session Number,Visit Count,Is Converted,Channel Grouping,IP Address,Detected Country,Detected Region,Detected City,Detected Lat,Detected Lon,GPS Granted,GPS Lat,GPS Lon,First Touch Source,First Touch Medium,First Touch Campaign,gclid,fbclid,msclkid,ttclid,OS Name,Browser Name,Device Category,Screen Resolution,Timezone\n';
            
            rawLogs.forEach(l => {
                const t = l.telemetry || {};
                const loc = t.location || {};
                const det = loc.detected || {};
                const prov = loc.provided || {};
                const first = t.first_touch || {};
                const firstUtm = first.utms || {};
                const cids = t.current_visit?.click_ids || {};
                const meta = t.telemetry || {};

                csv += `"${l.received_at}",` +
                       `"${l.server_timestamp}",` +
                       `"${t.user_id}",` +
                       `"${t.friendly_username}",` +
                       `"${t.session_id || ''}",` +
                       `"${t.session_number || 1}",` +
                       `"${t.visit_count || 1}",` +
                       `"${(t.is_conversion || t.conversion) ? 'TRUE' : 'FALSE'}",` +
                       `"${t.current_visit?.channel_group || 'Direct'}",` +
                       `"${l.ip_address}",` +
                       `"${det.country || ''}",` +
                       `"${det.region || ''}",` +
                       `"${det.city || ''}",` +
                       `"${det.lat || 0}",` +
                       `"${det.lon || 0}",` +
                       `"${prov.latitude ? 'TRUE' : 'FALSE'}",` +
                       `"${prov.latitude || ''}",` +
                       `"${prov.longitude || ''}",` +
                       `"${firstUtm.utm_source || 'direct'}",` +
                       `"${firstUtm.utm_medium || 'none'}",` +
                       `"${firstUtm.utm_campaign || 'direct'}",` +
                       `"${cids.gclid || ''}",` +
                       `"${cids.fbclid || ''}",` +
                       `"${cids.msclkid || ''}",` +
                       `"${cids.ttclid || ''}",` +
                       `"${meta.os_name || ''}",` +
                       `"${meta.browser_name || ''}",` +
                       `"${meta.device_category || ''}",` +
                       `"${meta.screen_resolution || ''}",` +
                       `"${meta.timezone || ''}"\n`;
            });

            downloadCSVBlob(csv, 'poboy_full_enterprise_dataset.csv');
        }

        function downloadCSVBlob(csvContent, filename) {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            a.click();
        }

        async function runSimulation(type) {
            let lat = 40.7128; let lon = -74.0060; let city = 'New York'; let country = 'United States'; let isProvided = false;
            let channelGroup = 'Paid Search (Google)';
            let clickIds = { gclid: 'g_sim_9988' };
            let isConv = false;
            let convObj = null;

            if (type === 'conversion') {
                isConv = true;
                convObj = { name: 'Quote Form Submitted', value: 250, timestamp: new Date().toISOString() };
            } else if (type === 'gps') {
                lat = 34.0522; lon = -118.2437; city = 'Los Angeles'; country = 'United States'; isProvided = true; channelGroup = 'Direct'; clickIds = {};
            }

            const testPayload = {
                user_id: 'pb_sim_' + Math.random().toString(36).substring(7),
                friendly_username: 'PoBoyTester-' + Math.floor(1000 + Math.random() * 9000),
                session_id: 'sess_' + Math.random().toString(36).substring(7),
                session_number: 1,
                visit_count: 1,
                is_conversion: isConv,
                conversion: convObj,
                first_touch: { timestamp: new Date().toISOString(), page: window.location.href, channel_group: channelGroup, click_ids: clickIds },
                last_touch: { timestamp: new Date().toISOString(), page: window.location.href, channel_group: channelGroup, click_ids: clickIds },
                location: {
                    detected: { country, country_code: 'US', city, lat, lon, source: 'Simulated IP' },
                    provided: isProvided ? { latitude: lat, longitude: lon, accuracy_meters: 15, granted: true } : null
                },
                telemetry: {
                    os_name: 'Windows', os_version: '11', browser_name: 'Google Chrome', browser_version: '127.0', device_category: 'Desktop',
                    screen_resolution: '2560x1440', viewport_size: '1920x1080', device_pixel_ratio: 2,
                    hardware_concurrency: 8, device_memory_gb: 16, connection_type: '4g', timezone: 'America/New_York'
                }
            };

            const res = await fetch('log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(testPayload)
            });

            if (res.ok) {
                document.getElementById('simResult').textContent = `✅ Simulated visit for ${testPayload.friendly_username}! Reloading logs...`;
                setTimeout(loadLogs, 800);
            }
        }

        renderRulesUI();
        setDateRange('all');
    </script>
<?php endif; ?>
</body>
</html>
