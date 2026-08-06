<?php
/**
 * Po'Boy Server Side Analytics - Universal Attribution Dashboard v0.9.0-beta
 * New Orleans French Quarter Warm Amber & Cajun Crimson Theme
 * GitHub: github.com/dadelonglegs/poboy
 */

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
    <title>Po'Boy's Data Layer - Universal Attribution Engine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg-dark: #0d0905;
            --bg-card: #18110a;
            --bg-card-hover: #261b11;
            --border-color: rgba(245, 158, 11, 0.15);
            --primary: #f59e0b;
            --primary-gradient: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            --accent-gold: #fbbf24;
            --accent-cajun: #ef4444;
            --accent-cyan: #06b6d4;
            --accent-green: #10b981;
            --text-primary: #fef3c7;
            --text-secondary: #d97706;
            --text-muted: #92400e;
            --radius-lg: 16px;
            --radius-md: 10px;
            --font-sans: 'Outfit', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-dark);
            color: #fef3c7;
            font-family: var(--font-sans);
            line-height: 1.5;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .login-container {
            max-width: 440px;
            margin: 100px auto;
            padding: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(245, 158, 11, 0.1);
            text-align: center;
        }
        .login-logo {
            font-size: 32px;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .form-group { margin-top: 24px; text-align: left; }
        .form-group label { display: block; font-size: 13px; color: #fde68a; margin-bottom: 8px; }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: #080503;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: #fef3c7;
            font-size: 15px;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25); }
        .btn-primary {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--radius-md);
            color: #000;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: transform 0.1s, opacity 0.2s;
        }
        .btn-primary:hover { opacity: 0.95; }
        .btn-primary:active { transform: scale(0.99); }
        .error-msg { color: #ef4444; font-size: 13px; margin-top: 12px; }

        .header-bar {
            background: rgba(24, 17, 10, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand { font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: #fef3c7; }
        .brand-badge {
            font-size: 11px;
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(245, 158, 11, 0.4);
            font-weight: 600;
        }
        .user-nav { display: flex; align-items: center; gap: 16px; }
        .logout-link { color: #fde68a; text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .logout-link:hover { color: #ffffff; }

        .container { max-width: 1440px; margin: 32px auto; padding: 0 24px; }

        .date-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        }
        .metric-title { font-size: 12px; color: #fde68a; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .metric-value { font-size: 34px; font-weight: 800; margin-top: 8px; color: #ffffff; }
        .metric-sub { font-size: 12px; color: var(--accent-green); margin-top: 6px; font-weight: 500; }

        .filter-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .preset-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .chip {
            padding: 6px 14px;
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 12px;
            color: #fde68a;
            cursor: pointer;
            transition: all 0.2s;
        }
        .chip:hover, .chip.active { background: rgba(245, 158, 11, 0.25); color: #ffffff; border-color: var(--primary); }

        .rules-container { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
        .rule-row {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            background: #080503;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .rule-select, .rule-input {
            padding: 8px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: #fef3c7;
            font-size: 13px;
            outline: none;
        }
        .rule-select { cursor: pointer; }
        .btn-remove-rule {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-add-rule {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .map-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
            overflow: hidden;
        }
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        #map {
            width: 100%;
            height: 420px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: #080503;
            z-index: 1;
        }

        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .panel-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th {
            background: rgba(8, 5, 3, 0.8);
            padding: 14px 20px;
            font-size: 12px;
            color: #fde68a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
        }
        .data-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        .data-table tr:hover { background: var(--bg-card-hover); }

        .friendly-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-mono);
            font-weight: 600;
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .avatar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); }
        .tag {
            display: inline-block;
            font-size: 11px;
            font-family: var(--font-mono);
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.06);
            color: #fde68a;
            margin-right: 4px;
        }
        .channel-badge {
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
        }
        .channel-organic-search { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .channel-organic-social { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .channel-paid-search { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
        .channel-referral { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .channel-direct { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }

        .converted-badge {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.4);
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .btn-view {
            padding: 6px 12px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: #fef3c7;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-view:hover { background: var(--primary); border-color: var(--primary); color: #000; }

        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-backdrop.active { display: flex; }
        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .close-btn { font-size: 24px; cursor: pointer; color: #fde68a; }
        .close-btn:hover { color: white; }

        .timeline-tree { position: relative; padding-left: 24px; margin-top: 20px; border-left: 2px dashed var(--border-color); }
        .timeline-item { position: relative; margin-bottom: 24px; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -31px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }
        .timeline-title { font-weight: 700; font-size: 15px; color: var(--accent-gold); }
        .timeline-detail { background: #080503; padding: 14px; border-radius: 8px; margin-top: 8px; font-size: 13px; font-family: var(--font-mono); color: #fde68a; }

        .simulator-box {
            background: #080503;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-top: 32px;
        }
    </style>
</head>
<body>

<?php if (!$isAuthenticated): ?>
    <div class="login-container">
        <div class="login-logo">Po'Boy's Data Layer</div>
        <p style="color: #fde68a; font-size: 14px; font-weight: 500;">Universal Attribution Engine</p>
        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Po'Boy Dashboard Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required autofocus>
            </div>
            <?php if ($loginError): ?>
                <div class="error-msg"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn-primary">Access Po'Boy Dashboard</button>
        </form>
    </div>
<?php else: ?>
    <header class="header-bar">
        <div class="brand">
            🥪 Po'Boy Server Side Analytics <span class="brand-badge">v0.9.0 Beta</span>
        </div>
        <div class="user-nav">
            <span style="font-size: 13px; color: #fde68a;">GitHub: github.com/dadelonglegs/poboy</span>
            <a href="?logout=1" class="logout-link">Logout</a>
        </div>
    </header>

    <div class="container">
        <!-- DATE RANGE SELECTOR BAR -->
        <div class="date-bar">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #fef3c7;">📅 Date Range Explorer</h3>
                <p style="font-size: 12px; color: #fde68a;">Select timeframes for real-time telemetry analytics & data export</p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <div class="preset-chips" style="margin-bottom: 0;">
                    <div class="chip" onclick="setDateRange('today')">Today</div>
                    <div class="chip" onclick="setDateRange('yesterday')">Yesterday</div>
                    <div class="chip" onclick="setDateRange('7days')">Last 7 Days</div>
                    <div class="chip" onclick="setDateRange('30days')">Last 30 Days</div>
                    <div class="chip" onclick="setDateRange('all')" id="chip-all">All Time</div>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="date" id="startDate" class="rule-input">
                    <span style="color: #92400e; font-size: 12px;">to</span>
                    <input type="date" id="endDate" class="rule-input">
                    <button onclick="loadLogs()" class="btn-view" style="padding: 8px 14px; background: var(--primary); color:#000;">Apply</button>
                </div>
            </div>
        </div>

        <!-- METRICS OVERVIEW -->
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
                <div class="metric-value" id="stat-mapped">0</div>
                <div class="metric-sub">IP GeoIP & GPS Pins</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Layered Filter Rules</div>
                <div class="metric-value" id="stat-rules-count" style="color: var(--accent-gold);">0</div>
                <div class="metric-sub">Active criteria</div>
            </div>
        </div>

        <!-- LAYERED FILTER PANEL -->
        <div class="filter-panel">
            <div class="filter-header">
                <div>
                    <h2 style="font-size: 18px; font-weight: 700;">🎛️ GA4-Rivaling Telemetry & Parameter Query Builder</h2>
                    <p style="font-size: 13px; color: #fde68a;">Filter visitors by Hardware (RAM/CPU/Retina), Network speed, Sessions, Location, Click IDs, and UTMs</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button onclick="clearAllFilters()" class="btn-view" style="padding: 8px 14px;">🗑️ Clear All Rules</button>
                </div>
            </div>

            <div style="font-size: 12px; color: #fde68a; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Quick Segment Presets:</div>
            <div class="preset-chips">
                <div class="chip" onclick="applyPreset('converted')">🎯 Converted Leads</div>
                <div class="chip" onclick="applyPreset('paid')">⚡ Paid Campaigns (gclid / fbclid)</div>
                <div class="chip" onclick="applyPreset('organic')">🌿 Organic Search</div>
                <div class="chip" onclick="applyPreset('social')">💬 Organic Social</div>
                <div class="chip" onclick="applyPreset('returning')">🔄 Returning Visitors (Visits > 1)</div>
                <div class="chip" onclick="applyPreset('retina')">🖥️ Retina Displays (DPR >= 2)</div>
                <div class="chip" onclick="applyPreset('gps')">🎯 High-Precision GPS Pins</div>
            </div>

            <div class="rules-container" id="rulesContainer"></div>
            <button onclick="addFilterRule()" class="btn-add-rule">+ Add Layered Filter Rule</button>
        </div>

        <!-- MAP CARD WITH HEATMAP TOGGLE -->
        <div class="map-card">
            <div class="map-header">
                <div>
                    <h2 style="font-size: 18px; font-weight: 700;">🌍 Visitor Location Map & Density Visualizer</h2>
                    <p style="font-size: 13px; color: #fde68a;">Live geographical map updating synchronously with active layer filters</p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 12px; color: #fde68a;">Visualization Mode:</span>
                    <button id="btnMapPins" onclick="setMapMode('pins')" class="btn-view" style="background: var(--primary); color:#000;">📍 Pin Markers</button>
                    <button id="btnMapHeat" onclick="setMapMode('heatmap')" class="btn-view" style="background: rgba(245, 158, 11, 0.12);">🔥 Heatmap Mode</button>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <!-- VISITOR STREAM TABLE -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 style="font-size: 18px; font-weight: 700;">Enterprise Visitor Telemetry Stream</h2>
                    <p style="font-size: 13px; color: #fde68a;">Filtered visitor logs matching your custom rules and date range</p>
                </div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button onclick="exportScreenCSV()" class="btn-view" style="padding: 10px 16px; background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: rgba(245, 158, 11, 0.4);">📥 Export Screen View (Filtered CSV)</button>
                    <button onclick="exportFullRawCSV()" class="btn-primary" style="width: auto; margin-top: 0; padding: 10px 18px; background: var(--primary-gradient);">🌐 Export Full Raw Dataset (All Fields)</button>
                    <button onclick="loadLogs()" class="btn-view" style="padding: 10px 16px;">🔄 Refresh Logs</button>
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
                    <tr><td colspan="7" style="text-align: center; color: #92400e; padding: 30px;">Loading analytics logs...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- SIMULATOR -->
        <div class="simulator-box">
            <h3 style="font-size: 16px; margin-bottom: 8px; color: var(--accent-gold);">🧪 Po'Boy Telemetry Testing Sandbox</h3>
            <p style="font-size: 13px; color: #fde68a; margin-bottom: 16px;">Simulate incoming visits and GTM conversion triggers with full GA4-rivaling telemetry.</p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button onclick="runSimulation('paid')" class="btn-primary" style="width: auto; margin-top: 0; padding: 0 20px;">Fire Visit (Paid Google Campaign)</button>
                <button onclick="runSimulation('conversion')" class="btn-primary" style="width: auto; margin-top: 0; padding: 0 20px; background: linear-gradient(135deg, #10b981, #059669);">Fire Conversion Event (GTM Pixel)</button>
                <button onclick="runSimulation('gps')" class="btn-primary" style="width: auto; margin-top: 0; padding: 0 20px; background: linear-gradient(135deg, #06b6d4, #0284c7);">Fire Visit (GPS Provided Pin)</button>
            </div>
            <div id="simResult" style="margin-top: 12px; font-size: 13px; font-family: var(--font-mono); color: var(--accent-green);"></div>
        </div>
    </div>

    <!-- JOURNEY MODAL -->
    <div class="modal-backdrop" id="journeyModal">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 style="font-size: 20px; color: #fef3c7;" id="modalHandle">User Journey & Telemetry Inspector</h3>
                    <p style="font-size: 13px; color: #fde68a;" id="modalUserId">ID: --</p>
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

        function initMap() {
            if (map) return;
            map = L.map('map').setView([20, 0], 2);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
                maxZoom: 18
            }).addTo(map);
            markersGroup = L.layerGroup().addTo(map);
        }

        function setMapMode(mode) {
            currentMapMode = mode;
            document.getElementById('btnMapPins').style.background = mode === 'pins' ? 'var(--primary)' : 'rgba(245, 158, 11, 0.12)';
            document.getElementById('btnMapPins').style.color = mode === 'pins' ? '#000' : '#fef3c7';
            document.getElementById('btnMapHeat').style.background = mode === 'heatmap' ? 'var(--primary)' : 'rgba(245, 158, 11, 0.12)';
            document.getElementById('btnMapHeat').style.color = mode === 'heatmap' ? '#000' : '#fef3c7';
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
                container.innerHTML = '<div style="font-size: 13px; color: #92400e; font-style: italic;">No active filter rules. Click "+ Add Layered Filter Rule" or select a preset above!</div>';
                return;
            }

            container.innerHTML = activeRules.map((rule, idx) => {
                const fieldOptions = AVAILABLE_FIELDS.map(f => `<option value="${f.id}" ${f.id === rule.field ? 'selected' : ''}>${f.name}</option>`).join('');
                return `
                    <div class="rule-row">
                        <span style="font-size: 12px; font-weight: 700; color: var(--accent-gold); font-family: var(--font-mono);">${idx === 0 ? 'WHERE' : 'AND'}</span>
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
            else if (type === 'retina') addFilterRule('device_pixel_ratio', 'greater_than', '1.5');
            else if (type === 'gps') addFilterRule('location_type', 'equals', 'GPS Provided');
        }

        function matchesRule(log, rule) {
            const tel = log.telemetry || {};
            const loc = tel.location || {};
            const current = tel.current_visit || {};
            const utms = current.utms || {};
            const cids = current.click_ids || {};
            const meta = tel.telemetry || tel.browser || {};

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
            if (c.includes('referral')) return 'channel-referral';
            return 'channel-direct';
        }

        function renderDashboard(logs) {
            initMap();
            markersGroup.clearLayers();
            if (heatLayer) {
                map.removeLayer(heatLayer);
                heatLayer = null;
            }

            const uniqueUsers = new Set();
            const sessionsSet = new Set();
            let conversionsCount = 0;
            let mappedCount = 0;
            const heatPoints = [];

            logs.forEach(log => {
                const tel = log.telemetry || {};
                if (tel.user_id) uniqueUsers.add(tel.user_id);
                if (tel.session_id) sessionsSet.add(tel.session_id);
                if (tel.is_conversion || tel.conversion) conversionsCount++;

                const loc = tel.location || {};
                const provided = loc.provided;
                const detected = loc.detected;

                let lat = null; let lon = null; let isGps = false; let label = '';

                if (provided && provided.latitude && provided.longitude) {
                    lat = provided.latitude; lon = provided.longitude; isGps = true; mappedCount++;
                    label = `🎯 User-Provided GPS: ${provided.latitude.toFixed(4)}, ${provided.longitude.toFixed(4)}`;
                } else if (detected && detected.lat && detected.lon) {
                    lat = detected.lat; lon = detected.lon; mappedCount++;
                    label = `🌐 Detected IP: ${detected.city || ''}, ${detected.country || ''}`;
                }

                if (lat !== null && lon !== null && (lat !== 0 || lon !== 0)) {
                    heatPoints.push([lat, lon, isGps ? 1.0 : 0.6]);

                    if (currentMapMode === 'pins') {
                        const color = isGps ? '#10b981' : '#f59e0b';
                        const marker = L.circleMarker([lat, lon], {
                            radius: isGps ? 8 : 6,
                            fillColor: color,
                            color: '#ffffff',
                            weight: 1.5,
                            opacity: 1,
                            fillOpacity: 0.8
                        });

                        marker.bindPopup(`
                            <div style="font-family: sans-serif; color: #0b0f19;">
                                <strong style="font-size: 14px;">${tel.friendly_username || 'Visitor'}</strong><br>
                                <span style="font-size: 12px; color: #475569;">${label}</span><br>
                                <span style="font-size: 11px; color: #64748b;">Channel: ${tel.current_visit?.channel_group || 'Direct'}</span><br>
                                <span style="font-size: 11px; color: #94a3b8;">Seen: ${log.received_at}</span>
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
                    gradient: { 0.2: '#06b6d4', 0.5: '#f59e0b', 0.8: '#dc2626', 1.0: '#ef4444' }
                }).addTo(map);
            }

            document.getElementById('stat-unique').textContent = uniqueUsers.size;
            document.getElementById('stat-sessions').textContent = sessionsSet.size || logs.length;
            document.getElementById('stat-conversions').textContent = conversionsCount;
            document.getElementById('stat-mapped').textContent = mappedCount;

            const tbody = document.getElementById('logTableBody');
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: #92400e; padding:30px;">No telemetry logs match your active filter rules and date range.</td></tr>';
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
                    tagsHtml += `<span class="tag" style="color:#fbbf24; background:rgba(245,158,11,0.15);">${k}: ${cids[k]}</span>`;
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
                            <div style="font-size: 11px; color: #92400e; margin-top: 4px; font-family: var(--font-mono);">${userId.substring(0, 18)}...</div>
                        </td>
                        <td>
                            <div style="font-size: 13px; font-weight: 600;">Visits: ${tel.visit_count || 1}</div>
                            <div style="font-size: 11px; color: #92400e; font-family: var(--font-mono);">${(tel.session_id || 'sess').substring(0, 12)}...</div>
                        </td>
                        <td style="font-size: 12px; font-family: var(--font-mono); color: #fde68a;">${hwInfo}</td>
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
                        OS: ${meta.os_name || 'N/A'} (${meta.os_version || 'N/A'}) | Browser: ${meta.browser_name || 'N/A'} (${meta.browser_version || 'N/A'})<br>
                        Device Category: ${meta.device_category || 'Desktop'} | Screen: ${meta.screen_resolution} (${meta.viewport_size})<br>
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
            let csv = 'Server Received At,Server Timestamp,User ID,Friendly Handle,Session ID,Session Number,Visit Count,Is Converted,Conversion Name,Conversion Value,Channel Grouping,IP Address,Detected Country,Detected Region,Detected City,Detected Lat,Detected Lon,GPS Granted,GPS Lat,GPS Lon,First Touch Source,First Touch Medium,First Touch Campaign,First Touch Timestamp,Last Touch Source,Last Touch Medium,gclid,fbclid,msclkid,ttclid,li_fat_id,OS Name,OS Version,Browser Name,Browser Version,Device Category,Screen Resolution,Viewport Size,Device Pixel Ratio,RAM (GB),CPU Cores,Connection Type,Downlink Speed (Mbps),RTT Ping (ms),Timezone,Page Title,Page Location,Page Path,Page Hash,Is Iframe\n';
            
            rawLogs.forEach(l => {
                const t = l.telemetry || {};
                const loc = t.location || {};
                const det = loc.detected || {};
                const prov = loc.provided || {};
                const first = t.first_touch || {};
                const last = t.last_touch || {};
                const firstUtm = first.utms || {};
                const lastUtm = last.utms || {};
                const cids = t.current_visit?.click_ids || {};
                const meta = t.telemetry || {};
                const conv = t.conversion || {};

                csv += `"${l.received_at}",` +
                       `"${l.server_timestamp}",` +
                       `"${t.user_id}",` +
                       `"${t.friendly_username}",` +
                       `"${t.session_id || ''}",` +
                       `"${t.session_number || 1}",` +
                       `"${t.visit_count || 1}",` +
                       `"${(t.is_conversion || t.conversion) ? 'TRUE' : 'FALSE'}",` +
                       `"${conv.name || ''}",` +
                       `"${conv.value || 0}",` +
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
                       `"${first.timestamp || ''}",` +
                       `"${lastUtm.utm_source || 'direct'}",` +
                       `"${lastUtm.utm_medium || 'none'}",` +
                       `"${cids.gclid || ''}",` +
                       `"${cids.fbclid || ''}",` +
                       `"${cids.msclkid || ''}",` +
                       `"${cids.ttclid || ''}",` +
                       `"${cids.li_fat_id || ''}",` +
                       `"${meta.os_name || ''}",` +
                       `"${meta.os_version || ''}",` +
                       `"${meta.browser_name || ''}",` +
                       `"${meta.browser_version || ''}",` +
                       `"${meta.device_category || ''}",` +
                       `"${meta.screen_resolution || ''}",` +
                       `"${meta.viewport_size || ''}",` +
                       `"${meta.device_pixel_ratio || 1}",` +
                       `"${meta.device_memory_gb || ''}",` +
                       `"${meta.hardware_concurrency || ''}",` +
                       `"${meta.connection_type || ''}",` +
                       `"${meta.downlink_mbps || ''}",` +
                       `"${meta.rtt_ping_ms || ''}",` +
                       `"${meta.timezone || ''}",` +
                       `"${(meta.page_title || '').replace(/"/g, '""')}",` +
                       `"${meta.page_location || ''}",` +
                       `"${meta.page_path || ''}",` +
                       `"${meta.page_hash || ''}",` +
                       `"${meta.is_iframe ? 'TRUE' : 'FALSE'}"\n`;
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
