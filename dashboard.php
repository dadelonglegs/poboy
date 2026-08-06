<?php
/**
 * Po'Boy Server Side Analytics - GA4 Enterprise Analytics Suite v0.9.0-beta
 * GitHub: github.com/dadelonglegs/poboy
 */

require_once __DIR__ . '/config.php';
session_start();

if (function_exists('opcache_invalidate')) { @opcache_invalidate(__FILE__, true); }

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
    <title>Po'Boy Analytics - Google Analytics Enterprise Suite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #334155;
            --border-color: rgba(226, 232, 240, 0.1);
            --border-highlight: rgba(56, 189, 248, 0.3);
            --primary: #38bdf8;
            --primary-hover: #0284c7;
            --accent-indigo: #6366f1;
            --accent-green: #10b981;
            --accent-amber: #f59e0b;
            --accent-orange: #fb4f14;
            --accent-rose: #f43f5e;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --radius-lg: 12px;
            --radius-md: 8px;
            --font-sans: 'Inter', -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-page);
            color: var(--text-primary);
            font-family: var(--font-sans);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* LOGIN CONTAINER */
        .login-container {
            max-width: 440px;
            margin: 100px auto;
            padding: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .login-logo {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .login-sub { font-size: 13px; color: var(--text-secondary); margin-bottom: 24px; }
        .form-group { margin-top: 20px; text-align: left; }
        .form-group label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2); }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #0f172a;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: var(--primary-hover); color: #ffffff; }

        /* HEADER BAR */
        .ga4-header {
            background: #0f172a;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .ga4-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }
        .ga4-logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #38bdf8 0%, #6366f1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
        }
        .ga4-badge {
            font-size: 11px;
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary);
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .ga4-user-nav { display: flex; align-items: center; gap: 16px; font-size: 13px; color: var(--text-secondary); }
        .logout-link { color: var(--text-secondary); text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 6px; }
        .logout-link:hover { color: #ffffff; border-color: var(--primary); }

        /* NAVIGATION TABS BAR */
        .ga4-nav-tabs {
            background: #1e293b;
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }
        .nav-tab {
            padding: 14px 18px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-tab:hover { color: #ffffff; }
        .nav-tab.active { color: var(--primary); border-bottom-color: var(--primary); }

        /* MAIN CONTAINER */
        .ga4-body { flex: 1; padding: 24px; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* FILTER & DATE CONTROLS */
        .controls-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .preset-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .chip {
            padding: 6px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        .chip:hover, .chip.active { background: rgba(56, 189, 248, 0.15); color: var(--primary); border-color: var(--primary); }

        .date-input {
            padding: 7px 12px;
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 13px;
            outline: none;
        }
        .date-input:focus { border-color: var(--primary); }

        /* SCORECARDS GRID */
        .scorecards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .scorecard {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            position: relative;
        }
        .scorecard-label { font-size: 12px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .scorecard-value { font-size: 32px; font-weight: 800; color: #ffffff; margin: 8px 0; font-family: var(--font-mono); }
        .scorecard-trend { font-size: 12px; color: var(--accent-green); display: flex; align-items: center; gap: 4px; }

        /* GRID LAYOUT FOR CHARTS & TABLES */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .card-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .card-title { font-size: 16px; font-weight: 700; color: #ffffff; }
        .card-sub { font-size: 12px; color: var(--text-secondary); }

        /* PROGRESS BARS & BARS */
        .dim-row { margin-bottom: 14px; }
        .dim-info { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; font-weight: 500; }
        .dim-track { height: 8px; background: #0f172a; border-radius: 4px; overflow: hidden; }
        .dim-bar { height: 100%; background: linear-gradient(90deg, #38bdf8, #6366f1); border-radius: 4px; transition: width 0.4s ease; }

        /* DATA TABLES */
        .ga4-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .ga4-table th {
            padding: 12px 14px;
            background: #0f172a;
            color: var(--text-secondary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .ga4-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .ga4-table tr:hover td { background: var(--bg-card-hover); }

        /* BADGES */
        .handle-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: var(--primary);
            border-radius: 6px;
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
        }
        .pulse-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); display: inline-block; box-shadow: 0 0 8px var(--primary); }

        .badge-channel {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-paid { background: rgba(251, 79, 20, 0.15); color: #fb4f14; border: 1px solid rgba(251, 79, 20, 0.3); }
        .badge-organic { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-social { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-direct { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }

        .btn-action {
            padding: 5px 10px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: var(--primary);
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-action:hover { background: var(--primary); color: #0f172a; }

        /* MAP */
        #map { height: 440px; border-radius: var(--radius-md); border: 1px solid var(--border-color); }

        /* MODAL */
        .modal-bg {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-bg.active { display: flex; }
        .modal-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 90%; max-width: 860px;
            max-height: 85vh; overflow-y: auto;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
        }
        .modal-close { font-size: 20px; cursor: pointer; color: var(--text-secondary); }
        .modal-close:hover { color: #ffffff; }

        .tree-line { border-left: 2px solid var(--border-highlight); padding-left: 18px; margin-left: 8px; }
        .tree-node { position: relative; margin-bottom: 20px; }
        .tree-node::before {
            content: ''; position: absolute; left: -24px; top: 4px;
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--primary); box-shadow: 0 0 10px var(--primary);
        }
        .tree-box { background: #0f172a; padding: 14px 18px; border-radius: 8px; border: 1px solid var(--border-color); font-family: var(--font-mono); font-size: 12px; color: var(--text-secondary); line-height: 1.6; }

        /* RULES QUERY BUILDER */
        .rules-wrapper { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
        .rule-item { display: flex; gap: 10px; align-items: center; background: #0f172a; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-color); flex-wrap: wrap; }
    </style>
</head>
<body>

<?php if (!$isAuthenticated): ?>
    <div class="login-container">
        <div class="login-logo">Po'Boy Analytics</div>
        <div class="login-sub">Google Analytics Enterprise Suite & Attribution Engine</div>
        
        <?php if ($loginError): ?>
            <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.3); color: #fecdd3; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 16px;"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Access Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter password..." required autofocus>
            </div>
            <button type="submit" class="btn-primary">Unlock Analytics Suite ➔</button>
        </form>
    </div>
<?php else: ?>
    <header class="ga4-header">
        <div class="ga4-brand">
            <div class="ga4-logo-icon">📊</div>
            <span>Po'Boy Server Side Analytics</span>
            <span class="ga4-badge">v0.9.0-beta</span>
        </div>
        <div class="ga4-user-nav">
            <span>Repository: <a href="https://github.com/dadelonglegs/poboy" target="_blank" style="color: var(--primary); text-decoration: none;">dadelonglegs/poboy</a></span>
            <a href="?logout=1" class="logout-link">Logout</a>
        </div>
    </header>

    <!-- NAVIGATION TABS -->
    <nav class="ga4-nav-tabs">
        <button class="nav-tab active" onclick="showSection('overview')">🏠 Realtime Overview</button>
        <button class="nav-tab" onclick="showSection('userscope')">👤 User Scope (Visitor Lifetime)</button>
        <button class="nav-tab" onclick="showSection('sessionscope')">⏱️ Session Scope (30-Min Window)</button>
        <button class="nav-tab" onclick="showSection('eventscope')">⚡ Event Scope (Hits & Content)</button>
        <button class="nav-tab" onclick="showSection('location')">🗺️ Geographic Intelligence</button>
        <button class="nav-tab" onclick="showSection('query')">🎛️ Scoped Query Builder & Export</button>
    </nav>

    <main class="ga4-body">
        <!-- GLOBAL CONTROL BAR -->
        <div class="controls-bar">
            <div>
                <h3 style="font-size: 15px; font-weight: 700;">📅 Date Range Explorer</h3>
                <p style="font-size: 12px; color: var(--text-secondary);">Select timeline to inspect real-time dimensions</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <div class="preset-group">
                    <div class="chip" onclick="setDateFilter('today')">Today</div>
                    <div class="chip" onclick="setDateFilter('yesterday')">Yesterday</div>
                    <div class="chip" onclick="setDateFilter('7days')">Last 7 Days</div>
                    <div class="chip" onclick="setDateFilter('30days')">Last 30 Days</div>
                    <div class="chip active" onclick="setDateFilter('all')" id="chip-all">All Time</div>
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="date" id="startDate" class="date-input">
                    <span style="font-size: 12px; color: var(--text-muted);">to</span>
                    <input type="date" id="endDate" class="date-input">
                    <button onclick="fetchTelemetry()" class="btn-action" style="padding: 7px 14px; background: var(--primary); color:#0f172a;">Apply</button>
                </div>
            </div>
        </div>

        <!-- SCORECARDS GRID -->
        <div class="scorecards-grid">
            <div class="scorecard">
                <div class="scorecard-label">Active Users</div>
                <div class="scorecard-value" id="card-users">0</div>
                <div class="scorecard-trend"><span>⚡ Unique Fingerprinted Handles</span></div>
            </div>
            <div class="scorecard">
                <div class="scorecard-label">Total Sessions</div>
                <div class="scorecard-value" id="card-sessions">0</div>
                <div class="scorecard-trend"><span>⏱️ 30-Min Inactivity Windows</span></div>
            </div>
            <div class="scorecard">
                <div class="scorecard-label">Conversion Rate</div>
                <div class="scorecard-value" id="card-conversions" style="color: var(--accent-green);">0</div>
                <div class="scorecard-trend"><span>🎯 GTM Lead Submissions</span></div>
            </div>
            <div class="scorecard">
                <div class="scorecard-label">Locations Pinpointed</div>
                <div class="scorecard-value" id="card-locations" style="color: var(--primary);">0</div>
                <div class="scorecard-trend"><span>🌍 GeoIP & GPS Permission Coordinates</span></div>
            </div>
        </div>

        <!-- SECTION 1: REALTIME OVERVIEW -->
        <section id="sec-overview" class="section-block">
            <div class="reports-grid">
                <div class="card-panel">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Default Channel Grouping</div>
                            <div class="card-sub">Traffic acquisition by origin category</div>
                        </div>
                    </div>
                    <div id="chart-channels">Loading channel data...</div>
                </div>

                <div class="card-panel">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Device Category Breakdown</div>
                            <div class="card-sub">Desktop vs Mobile vs Tablet proportion</div>
                        </div>
                    </div>
                    <div id="chart-devices">Loading device data...</div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: ACQUISITION & ATTRIBUTION -->
        <!-- SECTION 2: USER SCOPE (VISITOR LIFETIME) -->
        <section id="sec-userscope" class="section-block" style="display: none;">
            <div class="card-panel" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div>
                        <div class="card-title">👤 User Scope: Visitor Lifetime & First-Touch Attribution Matrix</div>
                        <div class="card-sub">730-day persistent visitor identity, cumulative touch counts, and retained parameter vaults</div>
                    </div>
                </div>
                <table class="ga4-table">
                    <thead>
                        <tr>
                            <th>Acquisition Channel</th>
                            <th>First Touch Source / Campaign</th>
                            <th>Last Touch Source / Campaign</th>
                            <th>Click ID Vault (gclid, fbclid, etc.)</th>
                            <th>Users</th>
                        </tr>
                    </thead>
                    <tbody id="table-acquisition">
                        <tr><td colspan="5" style="text-align: center; color: var(--text-secondary);">Loading user scope dimensions...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECTION 3: SESSION SCOPE (30-MIN INACTIVITY WINDOW) -->
        <section id="sec-sessionscope" class="section-block" style="display: none;">
            <div class="reports-grid">
                <div class="card-panel">
                    <div class="card-header">
                        <div class="card-title">⏱️ Session Scope: Operating Systems & Browsers</div>
                        <div class="card-sub">Session hardware & browser dimensions across active 30-min windows</div>
                    </div>
                    <div id="chart-os-browser">Loading session OS & Browser dimensions...</div>
                </div>

                <div class="card-panel">
                    <div class="card-header">
                        <div class="card-title">🖥️ Session Scope: Screen Resolutions & Hardware Specs</div>
                        <div class="card-sub">Device memory (RAM), CPU cores, and connection types</div>
                    </div>
                    <div id="chart-hardware">Loading session hardware dimensions...</div>
                </div>
            </div>
        </section>

        <!-- SECTION 4: EVENT SCOPE (HITS & CONTENT) -->
        <section id="sec-eventscope" class="section-block" style="display: none;">
            <div class="card-panel">
                <div class="card-header">
                    <div>
                        <div class="card-title">Pages & Content Meta Dimension Explorer</div>
                        <div class="card-sub">Page titles, H1 headings, meta descriptions, and DOM density</div>
                    </div>
                </div>
                <table class="ga4-table">
                    <thead>
                        <tr>
                            <th>Page Title</th>
                            <th>Page Path / URL</th>
                            <th>H1 Heading</th>
                            <th>Meta Description</th>
                            <th>DOM Node Count</th>
                        </tr>
                    </thead>
                    <tbody id="table-pages">
                        <tr><td colspan="5" style="text-align: center; color: var(--text-secondary);">Loading page content dimensions...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECTION 5: GEOGRAPHIC INTELLIGENCE -->
        <section id="sec-location" class="section-block" style="display: none;">
            <div class="card-panel" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div>
                        <div class="card-title">Geographic Location Explorer</div>
                        <div class="card-sub">Live Leaflet Map with Pin Markers & Heatmap density options</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button id="btnPins" onclick="setMapMode('pins')" class="btn-action" style="background: var(--primary); color:#0f172a;">📍 Pin Markers</button>
                        <button id="btnHeat" onclick="setMapMode('heatmap')" class="btn-action">🔥 Heatmap Mode</button>
                    </div>
                </div>
                <div id="map"></div>
            </div>
        </section>

        <!-- SECTION 6: CUSTOM QUERY BUILDER & EXPORT STREAM -->
        <section id="sec-query" class="section-block" style="display: none;">
            <div class="card-panel" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div>
                        <div class="card-title">🎛️ GA4 Custom Dimension Query Builder</div>
                        <div class="card-sub">Filter telemetry across 35+ custom dimensions simultaneously</div>
                    </div>
                    <button onclick="clearRules()" class="btn-action">🗑️ Clear Rules</button>
                </div>

                <div class="preset-group" style="margin-bottom: 16px;">
                    <div class="chip" onclick="addPresetRule('converted')">🎯 Converted Leads</div>
                    <div class="chip" onclick="addPresetRule('paid')">⚡ Paid Campaigns (gclid / fbclid)</div>
                    <div class="chip" onclick="addPresetRule('organic')">🌿 Organic Search</div>
                    <div class="chip" onclick="addPresetRule('returning')">🔄 Returning Users</div>
                    <div class="chip" onclick="addPresetRule('gps')">🎯 GPS Pins</div>
                </div>

                <div id="rulesWrapper" class="rules-wrapper"></div>
                <button onclick="addRuleRow()" class="btn-action" style="width: 100%; border-style: dashed;">+ Add Dimension Rule</button>
            </div>

            <!-- VISITOR LOG STREAM -->
            <div class="card-panel">
                <div class="card-header">
                    <div>
                        <div class="card-title">Visitor Stream & Full Dimension Dataset</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="exportScreenCSV()" class="btn-action">📥 Export Screen View (CSV)</button>
                        <button onclick="exportFullRawCSV()" class="btn-action" style="background: var(--primary); color:#0f172a;">🌐 Export Enterprise Dataset (CSV)</button>
                        <button onclick="fetchTelemetry()" class="btn-action">🔄 Refresh</button>
                    </div>
                </div>

                <table class="ga4-table">
                    <thead>
                        <tr>
                            <th>Friendly Handle / User ID</th>
                            <th>Session & Visits</th>
                            <th>Hardware & Tech</th>
                            <th>Location</th>
                            <th>Channel</th>
                            <th>Click IDs & UTMs</th>
                            <th>Inspect</th>
                        </tr>
                    </thead>
                    <tbody id="table-stream">
                        <tr><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">Loading stream...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- INSPECTOR MODAL -->
    <div class="modal-bg" id="inspectorModal">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                <div>
                    <h3 style="font-size: 20px; color: #ffffff;" id="mHandle">Telemetry Inspector</h3>
                    <p style="font-size: 13px; color: var(--text-secondary);" id="mUserId">ID: --</p>
                </div>
                <span class="modal-close" onclick="closeModal()">✕</span>
            </div>
            <div class="tree-line" id="mTimeline"></div>
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

        const DIMENSION_FIELDS = [
            { id: 'friendly_username', name: 'Friendly Handle' },
            { id: 'user_id', name: 'User ID (UUID)' },
            { id: 'is_conversion', name: 'Conversion Triggered' },
            { id: 'channel_group', name: 'Channel Grouping' },
            { id: 'utm_source', name: 'UTM Source' },
            { id: 'utm_campaign', name: 'UTM Campaign' },
            { id: 'click_id', name: 'Click ID (gclid/fbclid)' },
            { id: 'country', name: 'Country' },
            { id: 'city', name: 'City' },
            { id: 'browser_name', name: 'Browser' },
            { id: 'os_name', name: 'Operating System' },
            { id: 'device_category', name: 'Device Category' }
        ];

        function showSection(secId) {
            document.querySelectorAll('.section-block').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
            document.getElementById(`sec-${secId}`).style.display = 'block';
            event.currentTarget.classList.add('active');

            if (secId === 'location') {
                setTimeout(initMap, 200);
            }
        }

        function initMap() {
            if (map) { map.invalidateSize(); return; }
            map = L.map('map').setView([20, 0], 2);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO', maxZoom: 18
            }).addTo(map);
            markersGroup = L.layerGroup().addTo(map);
            renderMapData(filteredLogs);
        }

        function setMapMode(mode) {
            currentMapMode = mode;
            document.getElementById('btnPins').style.background = mode === 'pins' ? 'var(--primary)' : 'rgba(255,255,255,0.05)';
            document.getElementById('btnPins').style.color = mode === 'pins' ? '#0f172a' : 'var(--text-secondary)';
            document.getElementById('btnHeat').style.background = mode === 'heatmap' ? 'var(--primary)' : 'rgba(255,255,255,0.05)';
            document.getElementById('btnHeat').style.color = mode === 'heatmap' ? '#0f172a' : 'var(--text-secondary)';
            renderMapData(filteredLogs);
        }

        function setDateFilter(range) {
            const today = new Date();
            const fmt = d => d.toISOString().split('T')[0];

            if (range === 'today') {
                document.getElementById('startDate').value = fmt(today);
                document.getElementById('endDate').value = fmt(today);
            } else if (range === 'yesterday') {
                const yest = new Date(today); yest.setDate(yest.getDate() - 1);
                document.getElementById('startDate').value = fmt(yest);
                document.getElementById('endDate').value = fmt(yest);
            } else if (range === '7days') {
                const d7 = new Date(today); d7.setDate(d7.getDate() - 7);
                document.getElementById('startDate').value = fmt(d7);
                document.getElementById('endDate').value = fmt(today);
            } else if (range === '30days') {
                const d30 = new Date(today); d30.setDate(d30.getDate() - 30);
                document.getElementById('startDate').value = fmt(d30);
                document.getElementById('endDate').value = fmt(today);
            } else if (range === 'all') {
                document.getElementById('startDate').value = '';
                document.getElementById('endDate').value = '';
            }
            fetchTelemetry();
        }

        async function fetchTelemetry() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            let url = 'log.php?action=fetch';
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;

            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Failed to fetch telemetry logs');
                rawLogs = await res.json();
                applyRules();
            } catch (err) {
                document.getElementById('table-stream').innerHTML = `<tr><td colspan="7" style="text-align:center; color:#f43f5e; padding:30px;">Error loading logs: ${err.message}</td></tr>`;
            }
        }

        function addRuleRow(fieldId = 'channel_group', op = 'equals', val = '') {
            const ruleId = 'r_' + Math.random().toString(36).substring(7);
            activeRules.push({ id: ruleId, field: fieldId, operator: op, value: val });
            renderRulesUI();
            applyRules();
        }

        function removeRuleRow(ruleId) {
            activeRules = activeRules.filter(r => r.id !== ruleId);
            renderRulesUI();
            applyRules();
        }

        function clearRules() {
            activeRules = [];
            renderRulesUI();
            applyRules();
        }

        function renderRulesUI() {
            const wrapper = document.getElementById('rulesWrapper');
            if (!activeRules.length) {
                wrapper.innerHTML = '<div style="font-size:13px; color:var(--text-muted); font-style:italic;">No active query rules. Click "+ Add Dimension Rule" or select a preset above.</div>';
                return;
            }

            wrapper.innerHTML = activeRules.map((r, idx) => {
                const opts = DIMENSION_FIELDS.map(f => `<option value="${f.id}" ${f.id === r.field ? 'selected' : ''}>${f.name}</option>`).join('');
                return `
                    <div class="rule-item">
                        <span style="font-size:11px; font-weight:700; color:var(--primary); font-family:var(--font-mono);">${idx === 0 ? 'WHERE' : 'AND'}</span>
                        <select class="date-input" onchange="updateRule('${r.id}', 'field', this.value)">${opts}</select>
                        <select class="date-input" onchange="updateRule('${r.id}', 'operator', this.value)">
                            <option value="equals" ${r.operator === 'equals' ? 'selected' : ''}>equals</option>
                            <option value="contains" ${r.operator === 'contains' ? 'selected' : ''}>contains</option>
                        </select>
                        <input type="text" class="date-input" style="flex:1; min-width:180px;" value="${r.value}" placeholder="Value..." onkeyup="updateRule('${r.id}', 'value', this.value)">
                        <button onclick="removeRuleRow('${r.id}')" class="btn-action" style="color:#f43f5e; border-color:rgba(244,63,94,0.3);">✕</button>
                    </div>
                `;
            }).join('');
        }

        function updateRule(ruleId, key, val) {
            const r = activeRules.find(item => item.id === ruleId);
            if (r) { r[key] = val; applyRules(); }
        }

        function addPresetRule(type) {
            activeRules = [];
            if (type === 'converted') addRuleRow('is_conversion', 'equals', 'true');
            else if (type === 'paid') addRuleRow('click_id', 'contains', 'gclid');
            else if (type === 'organic') addRuleRow('channel_group', 'equals', 'Organic Search');
            else if (type === 'returning') addRuleRow('visit_count', 'contains', '2');
            else if (type === 'gps') addRuleRow('city', 'contains', 'GPS');
        }

        function matchesRule(log, rule) {
            const t = log.telemetry || {};
            const loc = t.location || {};
            const cur = t.current_visit || {};
            const utms = cur.utms || {};
            const cids = cur.click_ids || {};
            const meta = t.telemetry || t.browser || {};

            let val = '';
            if (rule.field === 'friendly_username') val = t.friendly_username || '';
            else if (rule.field === 'user_id') val = t.user_id || '';
            else if (rule.field === 'is_conversion') val = (t.is_conversion || t.conversion) ? 'true' : 'false';
            else if (rule.field === 'channel_group') val = cur.channel_group || t.first_touch?.channel_group || '';
            else if (rule.field === 'utm_source') val = utms.utm_source || '';
            else if (rule.field === 'utm_campaign') val = utms.utm_campaign || '';
            else if (rule.field === 'click_id') val = Object.keys(cids).join(', ');
            else if (rule.field === 'country') val = loc.detected?.country || '';
            else if (rule.field === 'city') val = loc.detected?.city || '';
            else if (rule.field === 'browser_name') val = meta.browser_name || '';
            else if (rule.field === 'os_name') val = meta.os_name || '';
            else if (rule.field === 'device_category') val = meta.device_category || '';

            const strVal = String(val).toLowerCase();
            const targetVal = String(rule.value).toLowerCase();

            if (rule.operator === 'equals') return strVal === targetVal;
            if (rule.operator === 'contains') return strVal.includes(targetVal);
            return true;
        }

        function applyRules() {
            filteredLogs = rawLogs;
            if (activeRules.length) {
                filteredLogs = rawLogs.filter(log => activeRules.every(rule => matchesRule(log, rule)));
            }
            renderAllSections(filteredLogs);
        }

        function getChannelBadge(channel) {
            const c = (channel || '').toLowerCase();
            if (c.includes('organic search')) return 'badge-organic';
            if (c.includes('social')) return 'badge-social';
            if (c.includes('paid')) return 'badge-paid';
            return 'badge-direct';
        }

        function renderAllSections(logs) {
            const uniqueUsers = new Set();
            const sessionsSet = new Set();
            let convCount = 0;
            let locCount = 0;

            const channelMap = {};
            const deviceMap = {};
            const osBrowserMap = {};
            const hwMap = {};
            const pagesMap = {};
            const acqList = [];

            logs.forEach(log => {
                const t = log.telemetry || {};
                if (t.user_id) uniqueUsers.add(t.user_id);
                if (t.session_id) sessionsSet.add(t.session_id);
                if (t.is_conversion || t.conversion) convCount++;

                const loc = t.location || {};
                if ((loc.provided && loc.provided.latitude) || (loc.detected && loc.detected.lat)) locCount++;

                const cur = t.current_visit || {};
                const ch = cur.channel_group || t.first_touch?.channel_group || 'Direct';
                channelMap[ch] = (channelMap[ch] || 0) + 1;

                const meta = t.telemetry || t.browser || {};
                const dev = meta.device_category || 'Desktop';
                deviceMap[dev] = (deviceMap[dev] || 0) + 1;

                const osBr = `${meta.os_name || 'OS'} / ${meta.browser_name || 'Browser'}`;
                osBrowserMap[osBr] = (osBrowserMap[osBr] || 0) + 1;

                const screenRes = meta.screen_resolution || '1920x1080';
                hwMap[screenRes] = (hwMap[screenRes] || 0) + 1;

                const title = meta.page_title || 'Untitled Page';
                pagesMap[title] = (pagesMap[title] || { count: 0, path: meta.page_path || '/', h1: meta.heading_h1 || 'N/A', desc: meta.description || 'N/A', nodes: meta.dom_nodes_count || 120 });
                pagesMap[title].count++;
            });

            document.getElementById('card-users').textContent = uniqueUsers.size;
            document.getElementById('card-sessions').textContent = sessionsSet.size || logs.length;
            document.getElementById('card-conversions').textContent = convCount;
            document.getElementById('card-locations').textContent = locCount;

            const total = logs.length || 1;

            // Overview Channel Bar Chart
            let chHtml = '';
            for (let c in channelMap) {
                const pct = Math.round((channelMap[c] / total) * 100);
                chHtml += `
                    <div class="dim-row">
                        <div class="dim-info"><span>${c}</span><span>${channelMap[c]} (${pct}%)</span></div>
                        <div class="dim-track"><div class="dim-bar" style="width:${pct}%;"></div></div>
                    </div>
                `;
            }
            document.getElementById('chart-channels').innerHTML = chHtml || '<div style="color:var(--text-muted);">No channel dimensions</div>';

            // Overview Device Bar Chart
            let devHtml = '';
            for (let d in deviceMap) {
                const pct = Math.round((deviceMap[d] / total) * 100);
                devHtml += `
                    <div class="dim-row">
                        <div class="dim-info"><span>${d}</span><span>${deviceMap[d]} (${pct}%)</span></div>
                        <div class="dim-track"><div class="dim-bar" style="width:${pct}%; background:linear-gradient(90deg, #6366f1, #38bdf8);"></div></div>
                    </div>
                `;
            }
            document.getElementById('chart-devices').innerHTML = devHtml || '<div style="color:var(--text-muted);">No device dimensions</div>';

            // OS & Browser
            let osHtml = '';
            for (let ob in osBrowserMap) {
                const pct = Math.round((osBrowserMap[ob] / total) * 100);
                osHtml += `
                    <div class="dim-row">
                        <div class="dim-info"><span>${ob}</span><span>${osBrowserMap[ob]} (${pct}%)</span></div>
                        <div class="dim-track"><div class="dim-bar" style="width:${pct}%; background:linear-gradient(90deg, #10b981, #38bdf8);"></div></div>
                    </div>
                `;
            }
            document.getElementById('chart-os-browser').innerHTML = osHtml || '<div style="color:var(--text-muted);">No OS dimensions</div>';

            // Hardware
            let hwHtml = '';
            for (let h in hwMap) {
                const pct = Math.round((hwMap[h] / total) * 100);
                hwHtml += `
                    <div class="dim-row">
                        <div class="dim-info"><span>${h} Resolution</span><span>${hwMap[h]} (${pct}%)</span></div>
                        <div class="dim-track"><div class="dim-bar" style="width:${pct}%; background:linear-gradient(90deg, #f59e0b, #fb4f14);"></div></div>
                    </div>
                `;
            }
            document.getElementById('chart-hardware').innerHTML = hwHtml || '<div style="color:var(--text-muted);">No hardware dimensions</div>';

            // Acquisition Table
            const tbodyAcq = document.getElementById('table-acquisition');
            if (logs.length === 0) {
                tbodyAcq.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px;">No acquisition dimensions match filter.</td></tr>';
            } else {
                tbodyAcq.innerHTML = logs.slice(0, 50).map(l => {
                    const t = l.telemetry || {};
                    const cur = t.current_visit || {};
                    const first = t.first_touch || {};
                    const last = t.last_touch || {};
                    const cids = cur.click_ids || {};
                    const ch = cur.channel_group || 'Direct';
                    const chClass = getChannelBadge(ch);
                    const cidKeys = Object.keys(cids).map(k => `${k}: ${cids[k]}`).join(', ') || 'None';
                    return `
                        <tr>
                            <td><span class="badge-channel ${chClass}">${ch}</span></td>
                            <td style="font-family:var(--font-mono);">${first.utms?.utm_source || 'direct'} / ${first.utms?.utm_campaign || 'direct'}</td>
                            <td style="font-family:var(--font-mono);">${last.utms?.utm_source || 'direct'} / ${last.utms?.utm_campaign || 'direct'}</td>
                            <td style="font-family:var(--font-mono); color:var(--primary);">${cidKeys}</td>
                            <td><strong>1</strong></td>
                        </tr>
                    `;
                }).join('');
            }

            // Pages & Content Table
            const tbodyPages = document.getElementById('table-pages');
            let pKeys = Object.keys(pagesMap);
            if (pKeys.length === 0) {
                tbodyPages.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px;">No page telemetry dimensions.</td></tr>';
            } else {
                tbodyPages.innerHTML = pKeys.map(k => {
                    const item = pagesMap[k];
                    return `
                        <tr>
                            <td><strong>${k}</strong></td>
                            <td style="font-family:var(--font-mono); color:var(--primary);">${item.path}</td>
                            <td>${item.h1}</td>
                            <td style="color:var(--text-secondary);">${item.desc}</td>
                            <td><span class="chip">${item.nodes} nodes</span></td>
                        </tr>
                    `;
                }).join('');
            }

            // Stream Table
            const tbodyStream = document.getElementById('table-stream');
            if (logs.length === 0) {
                tbodyStream.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:30px;">No telemetry logs match date filter.</td></tr>';
            } else {
                tbodyStream.innerHTML = logs.map((log, index) => {
                    const t = log.telemetry || {};
                    const handle = t.friendly_username || 'Visitor';
                    const uid = t.user_id || 'pb_anon';
                    const ch = t.current_visit?.channel_group || 'Direct';
                    const chClass = getChannelBadge(ch);
                    const meta = t.telemetry || t.browser || {};
                    const loc = t.location || {};
                    const prov = loc.provided || {};
                    const det = loc.detected || {};

                    let locStr = 'Unknown';
                    if (prov && prov.latitude) locStr = `GPS (${prov.latitude.toFixed(2)}, ${prov.longitude.toFixed(2)})`;
                    else if (det && det.city) locStr = `${det.city}, ${det.country_code || det.country}`;

                    const hw = `${meta.os_name || 'OS'} • ${meta.browser_name || 'Browser'} • ${meta.device_category || 'Desktop'}`;
                    const cids = t.current_visit?.click_ids || {};
                    let tags = '';
                    if (t.is_conversion || t.conversion) tags += `<span class="chip" style="background:rgba(16,185,129,0.2); color:#10b981;">🎯 CONVERTED</span> `;
                    for (let k in cids) tags += `<span class="chip" style="color:var(--primary);">${k}: ${cids[k]}</span> `;
                    if (!tags) tags = '<span class="chip">Organic</span>';

                    return `
                        <tr>
                            <td>
                                <div class="handle-badge"><span class="pulse-dot"></span>${handle}</div>
                                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-top:3px;">${uid.substring(0,18)}...</div>
                            </td>
                            <td>
                                <div>Visits: ${t.visit_count || 1}</div>
                                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono);">${(t.session_id || 'sess').substring(0,12)}...</div>
                            </td>
                            <td style="font-size:12px; font-family:var(--font-mono); color:var(--text-secondary);">${hw}</td>
                            <td style="font-weight:600;">${locStr}</td>
                            <td><span class="badge-channel ${chClass}">${ch}</span></td>
                            <td>${tags}</td>
                            <td><button class="btn-action" onclick="inspectRow(${index})">Inspect</button></td>
                        </tr>
                    `;
                }).join('');
            }

            renderMapData(logs);
        }

        function renderMapData(logs) {
            if (!map || !markersGroup) return;
            markersGroup.clearLayers();
            if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

            const heatPoints = [];
            logs.forEach(log => {
                const t = log.telemetry || {};
                const loc = t.location || {};
                const prov = loc.provided; const det = loc.detected;
                let lat = null; let lon = null; let isGps = false;

                if (prov && prov.latitude && prov.longitude) {
                    lat = prov.latitude; lon = prov.longitude; isGps = true;
                } else if (det && det.lat && det.lon) {
                    lat = det.lat; lon = det.lon;
                }

                if (lat !== null && lon !== null && (lat !== 0 || lon !== 0)) {
                    heatPoints.push([lat, lon, isGps ? 1.0 : 0.6]);
                    if (currentMapMode === 'pins') {
                        const marker = L.circleMarker([lat, lon], {
                            radius: isGps ? 8 : 6,
                            fillColor: isGps ? '#10b981' : '#38bdf8',
                            color: '#ffffff', weight: 1.5, fillOpacity: 0.8
                        });
                        marker.bindPopup(`<strong>${t.friendly_username}</strong><br>${isGps ? 'GPS Provided Pin' : (det.city + ', ' + det.country)}`);
                        markersGroup.addLayer(marker);
                    }
                }
            });

            if (currentMapMode === 'heatmap' && heatPoints.length > 0 && typeof L.heatLayer === 'function') {
                heatLayer = L.heatLayer(heatPoints, {
                    radius: 25, blur: 15, maxZoom: 17,
                    gradient: { 0.2: '#38bdf8', 0.5: '#6366f1', 0.8: '#f59e0b', 1.0: '#f43f5e' }
                }).addTo(map);
            }
        }

        function inspectRow(index) {
            const log = filteredLogs[index];
            const rawPayload = log.telemetry || {};
            const t = rawPayload.telemetry || rawPayload;
            
            const uScope = t.user_scope || {};
            const sScope = t.session_scope || {};
            const eScope = t.event_scope || {};

            const ident = t.identity || uScope;
            const sess = t.session || sScope;
            const attr = t.attribution || {};
            const loc = t.location || sScope.location || {};
            const meta = t.meta || eScope.meta || {};
            const social = t.social || eScope.social || {};
            const schema = t.schema || eScope.schema || {};
            const dom = t.dom_metrics || eScope.dom_metrics || {};
            const dev = t.device || sScope.device || {};
            const perf = t.performance || eScope.performance || {};
            const params = t.parameters || {};
            const conv = t.conversion || rawPayload.conversion;
            const serv = log.server_telemetry || {};
            const geoDet = log.location?.detected || {};

            const friendlyHandle = ident.friendly_handle || rawPayload.friendly_username || t.friendly_username || 'User Telemetry Inspector';
            const userId = ident.user_id || rawPayload.user_id || t.user_id || 'N/A';
            const sessionId = sess.session_id || rawPayload.session_id || t.session_id || 'N/A';

            document.getElementById('mHandle').textContent = friendlyHandle;
            document.getElementById('mUserId').textContent = `User ID: ${userId} | Session ID: ${sessionId}`;

            const pageTitle = meta.page_title || rawPayload.page_title || t.page_title || 'N/A';
            const pageLocation = meta.page_location || rawPayload.page_location || t.page_location || 'N/A';
            const pagePath = meta.page_path || rawPayload.page_path || 'N/A';
            const osName = dev.os_name || rawPayload.os_name || 'N/A';
            const browserName = dev.browser_name || rawPayload.browser_name || 'N/A';
            const screenRes = dev.screen_resolution || rawPayload.screen_resolution || 'N/A';
            const viewportSize = dev.viewport_size || rawPayload.viewport_size || 'N/A';
            const ramGb = dev.device_memory_gb || rawPayload.device_memory_gb || 'N/A';
            const cpuCores = dev.hardware_concurrency || rawPayload.hardware_concurrency || 'N/A';

            const detectedCity = loc.city || geoDet.city || 'Unknown';
            const detectedCountry = loc.country || geoDet.country || 'Unknown';
            const detectedRegion = loc.region || geoDet.region || '';
            const latVal = loc.latitude || geoDet.lat || 'N/A';
            const lonVal = loc.longitude || geoDet.lon || 'N/A';
            const locSource = loc.location_source || (loc.latitude ? 'gps_permission_granted' : 'GeoIP Service');
            const permStatus = loc.permission_status || (loc.latitude ? 'granted' : 'prompt');

            let html = `
                ${conv ? `
                <div class="tree-node">
                    <div style="font-weight:700; color:#10b981; margin-bottom:4px;">🎯 Conversion Event Triggered</div>
                    <div class="tree-box">
                        <strong>Event Name:</strong> ${conv.name || 'Lead Submission'}<br>
                        <strong>Value:</strong> $${conv.value || 0}<br>
                        <strong>Triggered At:</strong> ${conv.timestamp || log.received_at}
                    </div>
                </div>` : ''}

                <!-- 1. USER SCOPE -->
                <div class="tree-node">
                    <div style="font-weight:800; color:var(--primary); font-size:14px; margin-bottom:4px;">👤 1. USER SCOPE (730-Day Long-Lived Visitor Identity)</div>
                    <div class="tree-box" style="border-color:rgba(56, 189, 248, 0.3); background:rgba(56, 189, 248, 0.05);">
                        <strong>Friendly Handle:</strong> ${friendlyHandle}<br>
                        <strong>User ID (UUID):</strong> ${userId}<br>
                        <strong>Total Touch / Visit Count:</strong> ${uScope.visit_count || sess.visit_count || rawPayload.visit_count || t.visit_count || 1}<br>
                        <strong>First Visit:</strong> ${uScope.is_first_visit ? 'Yes (New Visitor)' : 'No'} | <strong>Returning User:</strong> ${uScope.is_returning_user ? 'Yes' : 'No'}<br>
                        <strong>First Touch Source:</strong> ${uScope.first_touch?.utms?.utm_source || attr.first_touch_source || 'direct'} | <strong>First Touch Campaign:</strong> ${uScope.first_touch?.utms?.utm_campaign || attr.first_touch_campaign || 'direct'}<br>
                        <strong>Retained Parameter Vault:</strong> ${JSON.stringify(uScope.param_vault || params.vault || rawPayload.vault_params || {})}
                    </div>
                </div>

                <!-- 2. SESSION SCOPE -->
                <div class="tree-node">
                    <div style="font-weight:800; color:var(--accent-amber); font-size:14px; margin-bottom:4px;">⏱️ 2. SESSION SCOPE (30-Minute Active Window)</div>
                    <div class="tree-box" style="border-color:rgba(245, 158, 11, 0.3); background:rgba(245, 158, 11, 0.05);">
                        <strong>Session ID:</strong> ${sessionId}<br>
                        <strong>Session Number:</strong> ${sScope.session_number || sess.session_number || 1} | <strong>Session Page Views:</strong> ${sScope.session_page_views || sess.session_page_views || 1}<br>
                        <strong>Session Start Time:</strong> ${sScope.session_start_time || sess.start_time || 'N/A'}<br>
                        <strong>Channel Grouping:</strong> ${sScope.channel_group || attr.channel_group || rawPayload.channel_group || 'Direct'}<br>
                        <strong>Hardware Diagnostics:</strong> ${dev.category || 'Desktop'} • OS: ${osName} • Browser: ${browserName} • Screen: ${screenRes} (${viewportSize}) • RAM: ${ramGb} GB • CPU: ${cpuCores} Cores • Connection: ${dev.connection_type || 'unknown'}<br>
                        <strong>Location Intelligence:</strong> IP: ${log.ip_address || '127.0.0.1'} • City: ${detectedCity} • Region: ${detectedRegion} • Country: ${detectedCountry} • Lat/Lon: (${latVal}, ${lonVal}) • Timezone: ${loc.timezone || 'America/Toronto'} (Offset: ${loc.timezone_offset_hours ?? -4} hrs) • Source: ${locSource}
                    </div>
                </div>

                <!-- 3. EVENT SCOPE -->
                <div class="tree-node">
                    <div style="font-weight:800; color:#ffffff; font-size:14px; margin-bottom:4px;">⚡ 3. EVENT SCOPE (Per-Hit / Pageview Level)</div>
                    <div class="tree-box" style="border-color:rgba(255, 255, 255, 0.2);">
                        <strong>Event Name:</strong> ${eScope.event_name || 'page_view'} | <strong>Timestamp:</strong> ${eScope.event_timestamp || log.received_at}<br>
                        <strong>Page Title:</strong> ${pageTitle}<br>
                        <strong>Page Location (URL):</strong> ${pageLocation}<br>
                        <strong>Page Path:</strong> ${pagePath} | <strong>Referrer:</strong> ${eScope.referrer || 'direct'}<br>
                        <strong>UTM Parameters:</strong> ${JSON.stringify(eScope.utms || attr.utms || {})}<br>
                        <strong>Click IDs:</strong> ${JSON.stringify(eScope.click_ids || attr.click_ids || {})}<br>
                        <strong>Meta Description:</strong> ${meta.description || 'N/A'} | <strong>Heading H1:</strong> ${meta.heading_h1 || 'N/A'}<br>
                        <strong>Social OpenGraph:</strong> og:title: "${social.og_title || 'N/A'}" | og:image: "${social.og_image || 'N/A'}"<br>
                        <strong>Schema.org JSON-LD Types:</strong> ${schema.types_list || 'None detected'}<br>
                        <strong>DOM Node Metrics:</strong> H1: ${dom.total_h1_count ?? 0} | H2: ${dom.total_h2_count ?? 0} | Links: ${dom.total_links_count ?? 0} | Images: ${dom.total_images_count ?? 0} | Forms: ${dom.total_forms_count ?? 0} | Nodes: ${dom.dom_nodes_count ?? 0}<br>
                        <strong>Performance Diagnostics:</strong> Client Script: ${perf.execution_time_ms || 0.4} ms | Server Exec: ${serv.execution_time_ms || 0} ms | Host: ${serv.server_hostname || 'N/A'} (PHP ${serv.php_version || '8.x'})
                    </div>
                </div>

                <!-- 4. RAW DATA LAYER JSON -->
                <div class="tree-node">
                    <div style="font-weight:700; color:var(--primary); margin-bottom:4px;">🔍 4. Raw JSON Data Layer Push Payload</div>
                    <div class="tree-box" style="overflow-x:auto;">
                        <pre style="margin:0; font-family:var(--font-mono); font-size:11px; color:var(--primary);">${JSON.stringify(rawPayload, null, 2)}</pre>
                    </div>
                </div>
            `;

            document.getElementById('mTimeline').innerHTML = html;
            document.getElementById('inspectorModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('inspectorModal').classList.remove('active');
        }

        function exportScreenCSV() {
            if (!filteredLogs.length) return alert('No logs on screen to export.');
            let csv = 'Timestamp,Handle,User ID,Session ID,Converted,Channel,IP,City,Country,Location Type,Page URL\n';
            filteredLogs.forEach(l => {
                const t = l.telemetry || {};
                const loc = t.location || {};
                const city = loc.provided?.latitude ? 'GPS Pin' : (loc.detected?.city || 'Unknown');
                const isConv = (t.is_conversion || t.conversion) ? 'TRUE' : 'FALSE';
                csv += `"${l.received_at}","${t.friendly_username}","${t.user_id}","${t.session_id || ''}","${isConv}","${t.current_visit?.channel_group || 'Direct'}","${l.ip_address}","${city}","${loc.detected?.country || 'Unknown'}","${loc.provided?.latitude ? 'GPS' : 'IP'}","${t.current_visit?.page || ''}"\n`;
            });
            downloadCSV(csv, 'poboy_screen_dataset.csv');
        }

        function exportFullRawCSV() {
            if (!rawLogs.length) return alert('No raw logs to export.');
            let csv = 'Timestamp,User ID,Handle,Session ID,Converted,Channel,IP,Country,City,gclid,fbclid,OS,Browser,Device,Resolution,Timezone,Page Title,Page Path\n';
            rawLogs.forEach(l => {
                const t = l.telemetry || {};
                const loc = t.location || {};
                const det = loc.detected || {};
                const cids = t.current_visit?.click_ids || {};
                const meta = t.telemetry || {};
                csv += `"${l.received_at}","${t.user_id}","${t.friendly_username}","${t.session_id || ''}","${(t.is_conversion || t.conversion) ? 'TRUE' : 'FALSE'}","${t.current_visit?.channel_group || 'Direct'}","${l.ip_address}","${det.country || ''}","${det.city || ''}","${cids.gclid || ''}","${cids.fbclid || ''}","${meta.os_name || ''}","${meta.browser_name || ''}","${meta.device_category || ''}","${meta.screen_resolution || ''}","${meta.timezone || ''}","${(meta.page_title || '').replace(/"/g, '""')}","${meta.page_path || ''}"\n`;
            });
            downloadCSV(csv, 'poboy_full_enterprise_dataset.csv');
        }

        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            a.click();
        }

        setDateFilter('all');
    </script>
<?php endif; ?>
</body>
</html>
