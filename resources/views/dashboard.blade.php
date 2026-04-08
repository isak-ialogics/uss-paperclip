<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USS PAPERCLIP — LCARS Interface</title>
<link href="https://fonts.googleapis.com/css2?family=Antonio:wght@400;700&family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  :root {
    --lcars-orange: #FF9900;
    --lcars-peach: #FF9966;
    --lcars-lavender: #CC99CC;
    --lcars-periwinkle: #9999FF;
    --lcars-blue: #6688CC;
    --lcars-lightblue: #99CCFF;
    --lcars-mauve: #CC6699;
    --lcars-tan: #FFCC99;
    --lcars-red: #CC4444;
    --lcars-green: #44CC44;
    --lcars-yellow: #FFFF66;
    --lcars-bg: #000000;
    --lcars-text: #FF9900;
    --lcars-radius: 24px;
    --lcars-elbow: 80px;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--lcars-bg);
    color: var(--lcars-text);
    font-family: 'Antonio', sans-serif;
    overflow-x: hidden;
    min-height: 100vh;
  }

  .lcars-frame {
    display: grid;
    grid-template-columns: 180px 1fr;
    grid-template-rows: auto 1fr auto;
    min-height: 100vh;
    gap: 3px;
  }

  .lcars-sidebar {
    grid-row: 1 / -1;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .sidebar-top {
    background: var(--lcars-orange);
    height: 60px;
    border-radius: 0 0 var(--lcars-elbow) 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #000;
    letter-spacing: 2px;
  }

  .sidebar-block {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .sidebar-btn {
    background: var(--lcars-lavender);
    padding: 8px 12px;
    font-family: 'Antonio', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    text-align: right;
    transition: filter 0.15s;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
  }
  .sidebar-btn:hover { filter: brightness(1.3); }
  .sidebar-btn:nth-child(2) { background: var(--lcars-periwinkle); }
  .sidebar-btn:nth-child(3) { background: var(--lcars-blue); }
  .sidebar-btn:nth-child(4) { background: var(--lcars-peach); }
  .sidebar-btn:nth-child(5) { background: var(--lcars-tan); }
  .sidebar-btn:nth-child(6) { background: var(--lcars-mauve); }
  .sidebar-btn:nth-child(7) { background: var(--lcars-periwinkle); }

  .sidebar-spacer {
    flex: 1;
    background: var(--lcars-blue);
    min-height: 40px;
  }

  .sidebar-bottom {
    background: var(--lcars-orange);
    height: 60px;
    border-radius: 0 var(--lcars-elbow) 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #000;
    letter-spacing: 2px;
  }

  .lcars-header {
    display: flex;
    align-items: stretch;
    gap: 3px;
    height: 60px;
  }

  .header-elbow {
    background: var(--lcars-orange);
    width: 120px;
    border-radius: 0 0 0 var(--lcars-elbow);
    flex-shrink: 0;
  }

  .header-bar {
    background: var(--lcars-orange);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    border-radius: 0 0 var(--lcars-radius) 0;
  }

  .ship-name {
    font-family: 'Orbitron', sans-serif;
    font-size: 22px;
    font-weight: 900;
    color: #000;
    letter-spacing: 4px;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .stardate {
    font-family: 'Share Tech Mono', monospace;
    font-size: 14px;
    color: #000;
    letter-spacing: 1px;
  }

  .refresh-status {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: rgba(0,0,0,0.6);
    letter-spacing: 1px;
  }

  .lcars-main {
    padding: 16px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto 1fr auto;
    gap: 16px;
    overflow-y: auto;
  }

  .viewscreen {
    grid-column: 1 / -1;
    background: #0a0a1a;
    border: 2px solid var(--lcars-blue);
    border-radius: var(--lcars-radius);
    height: 280px;
    position: relative;
    overflow: hidden;
  }

  .viewscreen-label {
    position: absolute;
    top: 12px;
    left: 16px;
    font-family: 'Orbitron', sans-serif;
    font-size: 10px;
    font-weight: 700;
    color: var(--lcars-lightblue);
    letter-spacing: 3px;
    text-transform: uppercase;
    z-index: 10;
  }

  .viewscreen canvas {
    position: absolute;
    inset: 0;
    width: 100% !important;
    height: 100% !important;
  }

  .viewscreen-stats {
    position: absolute;
    bottom: 12px;
    left: 16px;
    right: 16px;
    display: flex;
    gap: 20px;
    z-index: 10;
  }

  .viewscreen-stat {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--lcars-lightblue);
  }

  .viewscreen-stat span {
    color: var(--lcars-orange);
    font-weight: bold;
  }

  .panel {
    background: #0d0d1a;
    border: 1px solid var(--lcars-blue);
    border-radius: var(--lcars-radius);
    padding: 16px;
    position: relative;
  }

  .panel-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--lcars-blue);
  }

  .panel-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--lcars-orange);
    flex-shrink: 0;
  }

  .panel-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--lcars-lightblue);
  }

  .crew-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .crew-member {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(102, 136, 204, 0.08);
    border-radius: 12px;
    border: 1px solid rgba(102, 136, 204, 0.15);
    transition: all 0.2s;
  }
  .crew-member:hover {
    background: rgba(102, 136, 204, 0.15);
    border-color: var(--lcars-blue);
  }

  .crew-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
    font-weight: 900;
    color: #000;
    flex-shrink: 0;
    position: relative;
  }

  .crew-avatar .status-ring {
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    border: 2px solid transparent;
  }
  .crew-avatar .status-ring.running {
    border-color: var(--lcars-green);
    animation: pulseRing 2s ease-in-out infinite;
  }
  .crew-avatar .status-ring.idle {
    border-color: rgba(255, 153, 0, 0.4);
  }

  @keyframes pulseRing {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
  }

  .crew-info { flex: 1; min-width: 0; }
  .crew-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--lcars-tan);
    letter-spacing: 1px;
  }
  .crew-role {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: var(--lcars-lavender);
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .crew-status {
    font-family: 'Share Tech Mono', monospace;
    font-size: 9px;
    margin-top: 2px;
  }
  .crew-status.running { color: var(--lcars-green); }
  .crew-status.idle, .crew-status.paused { color: var(--lcars-orange); }

  .systems-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .system-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: rgba(102, 136, 204, 0.06);
    border-radius: 10px;
    border-left: 3px solid var(--lcars-orange);
  }

  .system-status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .system-status-indicator.online { background: var(--lcars-green); box-shadow: 0 0 6px var(--lcars-green); }
  .system-status-indicator.planned { background: var(--lcars-yellow); }
  .system-status-indicator.standby { background: var(--lcars-lavender); }
  .system-status-indicator.offline { background: var(--lcars-red); }

  .system-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--lcars-peach);
    flex: 1;
    letter-spacing: 1px;
  }

  .system-detail {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: var(--lcars-lavender);
    text-transform: uppercase;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  .stat-card {
    text-align: center;
    padding: 12px 8px;
    background: rgba(102, 136, 204, 0.06);
    border-radius: 12px;
    border: 1px solid rgba(102, 136, 204, 0.12);
  }

  .stat-value {
    font-family: 'Orbitron', sans-serif;
    font-size: 28px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 4px;
  }

  .stat-label {
    font-family: 'Share Tech Mono', monospace;
    font-size: 9px;
    color: var(--lcars-lavender);
    text-transform: uppercase;
    letter-spacing: 2px;
  }

  .alert-panel {
    grid-column: 1 / -1;
  }

  .alert-panel .panel-header { border-bottom-color: var(--lcars-red); }
  .alert-panel .panel-dot { background: var(--lcars-red); animation: alertPulse 1s ease-in-out infinite; }

  @keyframes alertPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
  }

  .alert-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .alert-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: rgba(204, 68, 68, 0.08);
    border-radius: 10px;
    border-left: 3px solid var(--lcars-red);
  }

  .alert-id {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--lcars-red);
    flex-shrink: 0;
    min-width: 80px;
  }

  .alert-title {
    font-size: 13px;
    color: var(--lcars-tan);
    flex: 1;
  }

  .alert-priority {
    font-family: 'Share Tech Mono', monospace;
    font-size: 9px;
    padding: 2px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .alert-priority.critical { background: rgba(204, 68, 68, 0.4); color: var(--lcars-red); }
  .alert-priority.high { background: rgba(204, 68, 68, 0.3); color: var(--lcars-red); }
  .alert-priority.medium { background: rgba(255, 153, 0, 0.2); color: var(--lcars-orange); }
  .alert-priority.low { background: rgba(153, 204, 255, 0.1); color: var(--lcars-lightblue); }

  .log-panel {
    grid-column: 1 / -1;
  }

  .mission-log {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--lcars-lightblue);
    line-height: 1.7;
    max-height: 120px;
    overflow-y: auto;
  }

  .mission-log::-webkit-scrollbar { width: 4px; }
  .mission-log::-webkit-scrollbar-track { background: transparent; }
  .mission-log::-webkit-scrollbar-thumb { background: var(--lcars-blue); border-radius: 4px; }

  .log-entry { padding: 2px 0; }
  .log-status { color: var(--lcars-orange); }
  .log-agent { color: var(--lcars-lavender); }
  .log-title { color: var(--lcars-lightblue); }

  .lcars-footer {
    display: flex;
    align-items: stretch;
    gap: 3px;
    height: 40px;
  }

  .footer-elbow {
    background: var(--lcars-orange);
    width: 120px;
    border-radius: var(--lcars-elbow) 0 0 0;
    flex-shrink: 0;
  }

  .footer-bar {
    background: var(--lcars-orange);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    border-radius: var(--lcars-radius) 0 0 0;
  }

  .footer-text {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: #000;
    letter-spacing: 2px;
  }

  .red-alert-overlay {
    display: none;
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 100;
    animation: redFlash 1.5s ease-in-out infinite;
  }
  .red-alert-overlay.active { display: block; }

  @keyframes redFlash {
    0%, 100% { box-shadow: inset 0 0 60px rgba(204, 68, 68, 0); }
    50% { box-shadow: inset 0 0 60px rgba(204, 68, 68, 0.15); }
  }

  .scan-line {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(153, 204, 255, 0.1), transparent);
    z-index: 50;
    animation: scanDown 8s linear infinite;
    pointer-events: none;
  }

  @keyframes scanDown {
    0% { top: -2px; }
    100% { top: 100vh; }
  }

  @media (max-width: 900px) {
    .lcars-frame { grid-template-columns: 1fr; }
    .lcars-sidebar { display: none; }
    .lcars-main { grid-template-columns: 1fr; }
    .crew-grid { grid-template-columns: 1fr; }
    .viewscreen { height: 220px; }
  }

  .no-alerts {
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--lcars-green);
    padding: 8px;
  }

  /* Project Status Panel */
  .project-status-panel {
    grid-column: 1 / -1;
  }

  .project-status-panel .panel-header { border-bottom-color: var(--lcars-periwinkle); }
  .project-status-panel .panel-dot { background: var(--lcars-periwinkle); }

  .project-table {
    width: 100%;
    border-collapse: collapse;
  }

  .project-table th {
    font-family: 'Orbitron', sans-serif;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--lcars-lavender);
    text-align: left;
    padding: 6px 10px;
    border-bottom: 1px solid rgba(153, 153, 255, 0.2);
  }

  .project-table td {
    padding: 8px 10px;
    border-bottom: 1px solid rgba(102, 136, 204, 0.08);
    vertical-align: middle;
  }

  .project-table tr:last-child td { border-bottom: none; }

  .project-table tr:hover td {
    background: rgba(153, 153, 255, 0.05);
  }

  .proj-status-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
    vertical-align: middle;
    flex-shrink: 0;
  }
  .proj-status-dot.live    { background: var(--lcars-green); box-shadow: 0 0 6px var(--lcars-green); }
  .proj-status-dot.planned { background: var(--lcars-yellow); }
  .proj-status-dot.desktop { background: var(--lcars-lavender); }

  .proj-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--lcars-tan);
    letter-spacing: 1px;
    white-space: nowrap;
  }

  .proj-url a {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--lcars-lightblue);
    text-decoration: none;
    letter-spacing: 0.5px;
    transition: color 0.15s;
  }
  .proj-url a:hover { color: var(--lcars-periwinkle); text-decoration: underline; }

  .proj-url-na {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: rgba(153, 204, 255, 0.35);
    letter-spacing: 0.5px;
  }

  .proj-status-badge {
    font-family: 'Share Tech Mono', monospace;
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 6px;
    white-space: nowrap;
  }
  .proj-status-badge.live    { background: rgba(68, 204, 68, 0.15); color: var(--lcars-green); }
  .proj-status-badge.planned { background: rgba(255, 255, 102, 0.1); color: var(--lcars-yellow); }
  .proj-status-badge.desktop { background: rgba(204, 153, 204, 0.15); color: var(--lcars-lavender); }
</style>
</head>
<body x-data="lcarsApp()" x-init="init()">

<div class="red-alert-overlay" :class="{ active: hasAlerts }"></div>
<div class="scan-line"></div>

<div class="lcars-frame">
  <!-- LEFT SIDEBAR -->
  <div class="lcars-sidebar">
    <div class="sidebar-top">LCARS 47</div>
    <div class="sidebar-block">
      <button class="sidebar-btn">VIEWSCREEN</button>
      <button class="sidebar-btn">CREW</button>
      <button class="sidebar-btn">SYSTEMS</button>
      <button class="sidebar-btn">ALERTS</button>
      <button class="sidebar-btn">STATS</button>
      <button class="sidebar-btn" @click="toggleRedAlert()">RED ALERT</button>
      <button class="sidebar-btn" @click="refreshData()">REFRESH</button>
      <div class="sidebar-spacer"></div>
    </div>
    <div class="sidebar-bottom">NCC-2026</div>
  </div>

  <!-- HEADER -->
  <div class="lcars-header">
    <div class="header-elbow"></div>
    <div class="header-bar">
      <div class="ship-name">USS PAPERCLIP</div>
      <div class="header-right">
        <div class="refresh-status" x-text="refreshStatus"></div>
        <div class="stardate" x-text="stardate"></div>
      </div>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="lcars-main">

    <!-- VIEWSCREEN -->
    <div class="viewscreen" id="viewscreen-container">
      <div class="viewscreen-label">MAIN VIEWSCREEN — FORWARD</div>
      <div class="viewscreen-stats">
        <div class="viewscreen-stat">CREW: <span x-text="stats.agentsTotal + ' ABOARD / ' + stats.agentsActive + ' ON DUTY'"></span></div>
        <div class="viewscreen-stat">MISSIONS: <span x-text="stats.inProgress + ' ACTIVE'"></span></div>
        <div class="viewscreen-stat">COMPLETED: <span x-text="stats.done"></span></div>
        <div class="viewscreen-stat" x-show="stats.blocked > 0" style="color: var(--lcars-red)">ALERTS: <span x-text="stats.blocked"></span></div>
      </div>
    </div>

    <!-- CREW ROSTER -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-dot"></div>
        <div class="panel-title">Bridge Crew</div>
      </div>
      <div class="crew-grid">
        <template x-for="agent in agents" :key="agent.id">
          <div class="crew-member">
            <div class="crew-avatar" :style="'background: ' + agentColor(agent)">
              <div class="status-ring" :class="agent.status === 'running' ? 'running' : 'idle'"></div>
              <span x-text="agentInitials(agent)"></span>
            </div>
            <div class="crew-info">
              <div class="crew-name" x-text="agent.name"></div>
              <div class="crew-role" x-text="(agent.title || agent.role || '').toUpperCase()"></div>
              <div class="crew-status" :class="agent.status === 'running' ? 'running' : 'idle'"
                   x-text="agent.status === 'running' ? '● ON DUTY' : '○ STANDBY'"></div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- SHIP SYSTEMS / MISSION STATS -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-dot" style="background: var(--lcars-periwinkle)"></div>
        <div class="panel-title">Ship Systems</div>
      </div>
      <div class="systems-list">
        <template x-for="project in projects" :key="project.id">
          <div class="system-item">
            <div class="system-status-indicator" :class="projectStatusClass(project.status)"></div>
            <div class="system-name" x-text="project.name"></div>
            <div class="system-detail" x-text="projectStatusLabel(project.status)"></div>
          </div>
        </template>
      </div>
      <div style="margin-top: 16px;">
        <div class="panel-header">
          <div class="panel-dot" style="background: var(--lcars-peach)"></div>
          <div class="panel-title">Mission Stats</div>
        </div>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value" style="color: var(--lcars-green)" x-text="stats.done"></div>
            <div class="stat-label">Missions Complete</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: var(--lcars-lightblue)" x-text="stats.inProgress"></div>
            <div class="stat-label">Active Missions</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: var(--lcars-orange)" x-text="stats.todo"></div>
            <div class="stat-label">Queued Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: var(--lcars-lavender)" x-text="stats.agentsActive + '/' + stats.agentsTotal"></div>
            <div class="stat-label">Crew Active</div>
          </div>
        </div>
      </div>
    </div>

    <!-- GOLEM INC PROJECT STATUS -->
    <div class="panel project-status-panel">
      <div class="panel-header">
        <div class="panel-dot"></div>
        <div class="panel-title">Golem Inc — Fleet Deployment Status</div>
      </div>
      <table class="project-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>URL</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="proj-name">Route Optimiser</td>
            <td class="proj-url"><a href="https://routes.mplace.co.za" target="_blank" rel="noopener">routes.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">USS Paperclip Dashboard</td>
            <td class="proj-url"><a href="https://dashboard.mplace.co.za" target="_blank" rel="noopener">dashboard.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">Server Monitor</td>
            <td class="proj-url"><a href="https://monitor.mplace.co.za" target="_blank" rel="noopener">monitor.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">Vigil</td>
            <td class="proj-url"><a href="https://vigil.mplace.co.za" target="_blank" rel="noopener">vigil.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">RivalWatch</td>
            <td class="proj-url"><a href="https://rivalwatch.mplace.co.za" target="_blank" rel="noopener">rivalwatch.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">TenderOps</td>
            <td class="proj-url"><a href="https://tenderops.mplace.co.za" target="_blank" rel="noopener">tenderops.mplace.co.za</a></td>
            <td><span class="proj-status-dot live"></span><span class="proj-status-badge live">Live</span></td>
          </tr>
          <tr>
            <td class="proj-name">DesktopFactory</td>
            <td class="proj-url-na">N/A — Desktop Game</td>
            <td><span class="proj-status-dot desktop"></span><span class="proj-status-badge desktop">Desktop</span></td>
          </tr>
          <tr>
            <td class="proj-name">Sentinel</td>
            <td class="proj-url-na">N/A</td>
            <td><span class="proj-status-dot planned"></span><span class="proj-status-badge planned">Planned</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- RED ALERTS -->
    <div class="panel alert-panel">
      <div class="panel-header">
        <div class="panel-dot"></div>
        <div class="panel-title">Red Alerts — Blocked Issues</div>
      </div>
      <div class="alert-list">
        <template x-if="blockedIssues.length === 0">
          <div class="no-alerts">ALL SYSTEMS NOMINAL — NO ACTIVE ALERTS</div>
        </template>
        <template x-for="issue in blockedIssues" :key="issue.id">
          <div class="alert-item">
            <div class="alert-id" x-text="issue.identifier"></div>
            <div class="alert-title" x-text="issue.title"></div>
            <div class="alert-priority" :class="issue.priority" x-text="issue.priority || 'medium'"></div>
          </div>
        </template>
      </div>
    </div>

    <!-- MISSION LOG -->
    <div class="panel log-panel">
      <div class="panel-header">
        <div class="panel-dot" style="background: var(--lcars-lightblue)"></div>
        <div class="panel-title">Mission Log — Recent Activity</div>
      </div>
      <div class="mission-log">
        <template x-for="issue in recentIssues" :key="issue.id">
          <div class="log-entry">
            <span class="log-status" x-text="'[' + (issue.status || '').toUpperCase().replace('_', '-') + ']'"></span>
            <span class="log-agent" x-text="' ' + (issue.identifier || '') + ' '"></span>
            — <span class="log-title" x-text="issue.title"></span>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="lcars-footer">
    <div class="footer-elbow"></div>
    <div class="footer-bar">
      <div class="footer-text">UNITED FEDERATION OF PAPERCLIP — STARFLEET COMMAND</div>
      <div class="footer-text" x-text="'LAST SYNC: ' + lastSync"></div>
    </div>
  </div>
</div>

<script>
function lcarsApp() {
  return {
    agents: @json($agents),
    projects: @json($projects),
    stats: @json($stats),
    blockedIssues: @json(array_values($blockedIssues)),
    recentIssues: @json(array_values($recentIssues)),
    stardate: '',
    lastSync: 'INITIALIZING',
    refreshStatus: '',
    hasAlerts: {{ count($blockedIssues) > 0 ? 'true' : 'false' }},
    redAlertManual: false,
    refreshInterval: null,

    init() {
      this.updateStardate();
      setInterval(() => this.updateStardate(), 60000);
      this.viewscreen = initViewscreen(this.stats, this.hasAlerts);
      this.lastSync = new Date().toLocaleTimeString();
      this.refreshInterval = setInterval(() => this.refreshData(), 60000);
    },

    updateStardate() {
      const now = new Date();
      const start = new Date(now.getFullYear(), 0, 1);
      const day = Math.floor((now - start) / 86400000) + 1;
      const fraction = Math.floor((now.getHours() * 60 + now.getMinutes()) / 1440 * 100);
      this.stardate = `STARDATE ${now.getFullYear()}.${String(day).padStart(3,'0')}.${String(fraction).padStart(2,'0')}`;
    },

    agentColor(agent) {
      const colors = {
        ceo: '#FF9900', cto: '#6688CC', cmo: '#CC99CC',
        engineer: '#99CCFF', qa: '#9999FF', doctor: '#CC6699',
      };
      const role = (agent.role || '').toLowerCase();
      return colors[role] || '#FFCC99';
    },

    agentInitials(agent) {
      return (agent.name || '').slice(0, 2).toUpperCase();
    },

    projectStatusClass(status) {
      const map = { in_progress: 'online', planned: 'planned', done: 'online', cancelled: 'offline', backlog: 'standby' };
      return map[status] || 'standby';
    },

    projectStatusLabel(status) {
      const map = { in_progress: 'ONLINE', planned: 'STANDBY', done: 'NOMINAL', cancelled: 'OFFLINE', backlog: 'AWAITING ORDERS' };
      return map[status] || 'UNKNOWN';
    },

    toggleRedAlert() {
      this.redAlertManual = !this.redAlertManual;
      this.hasAlerts = this.redAlertManual || this.blockedIssues.length > 0;
      if (this.viewscreen) this.viewscreen.update(this.stats, this.hasAlerts);
    },

    async refreshData() {
      this.refreshStatus = 'SYNCING...';
      try {
        const res = await fetch('/api/refresh', {
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        this.agents = data.agents;
        this.projects = data.projects;
        this.stats = data.stats;
        this.blockedIssues = data.blockedIssues;
        this.recentIssues = data.recentIssues;
        this.hasAlerts = this.redAlertManual || data.blockedIssues.length > 0;
        if (this.viewscreen) this.viewscreen.update(this.stats, this.hasAlerts);
        this.lastSync = new Date().toLocaleTimeString();
        this.refreshStatus = 'SYNC OK';
        setTimeout(() => { this.refreshStatus = ''; }, 3000);
      } catch (e) {
        this.refreshStatus = 'SYNC ERR';
        setTimeout(() => { this.refreshStatus = ''; }, 5000);
      }
    }
  };
}

function initViewscreen(stats, hasAlerts) {
  const container = document.getElementById('viewscreen-container');
  const scene = new THREE.Scene();
  scene.fog = new THREE.FogExp2(0x0a0a1a, 0.0008);

  const camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 2000);
  camera.position.set(0, 30, 120);
  camera.lookAt(0, 0, -50);

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setClearColor(0x0a0a1a);
  container.insertBefore(renderer.domElement, container.firstChild);

  // --- Starfield ---
  const starCount = 2000;
  const starGeo = new THREE.BufferGeometry();
  const starPos = new Float32Array(starCount * 3);
  const starSizes = new Float32Array(starCount);
  for (let i = 0; i < starCount; i++) {
    starPos[i * 3] = (Math.random() - 0.5) * 600;
    starPos[i * 3 + 1] = (Math.random() - 0.5) * 400;
    starPos[i * 3 + 2] = -Math.random() * 1500;
    starSizes[i] = Math.random() * 2 + 0.5;
  }
  starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
  starGeo.setAttribute('size', new THREE.BufferAttribute(starSizes, 1));
  const starMat = new THREE.PointsMaterial({ color: 0xffffff, size: 1.5, sizeAttenuation: true, transparent: true, opacity: 0.8 });
  const stars = new THREE.Points(starGeo, starMat);
  scene.add(stars);

  // --- Lighting ---
  const ambient = new THREE.AmbientLight(0x334466, 0.6);
  scene.add(ambient);
  const keyLight = new THREE.DirectionalLight(0x99ccff, 1.0);
  keyLight.position.set(50, 80, 100);
  scene.add(keyLight);
  const fillLight = new THREE.DirectionalLight(0x6688cc, 0.4);
  fillLight.position.set(-30, -20, 50);
  scene.add(fillLight);

  // --- Ship materials ---
  const hullMat = new THREE.MeshPhongMaterial({ color: 0x1a2a3d, specular: 0x4466aa, shininess: 60, emissive: 0x0a1020, emissiveIntensity: 0.3 });
  const trimMat = new THREE.MeshPhongMaterial({ color: 0x6688cc, emissive: 0x334466, emissiveIntensity: 0.5 });
  const nacelleMat = new THREE.MeshPhongMaterial({ color: 0x6688cc, emissive: 0x3366aa, emissiveIntensity: 0.8, transparent: true, opacity: 0.9 });
  const engineGlowMat = new THREE.MeshPhongMaterial({ color: 0x99ccff, emissive: 0x99ccff, emissiveIntensity: 1.5, transparent: true, opacity: 0.7 });
  const deflectorMat = new THREE.MeshPhongMaterial({ color: 0x336699, emissive: 0x4488bb, emissiveIntensity: 1.0, transparent: true, opacity: 0.8 });
  const runLightMat = new THREE.MeshBasicMaterial({ color: 0xcc4444 });

  const ship = new THREE.Group();

  // Saucer section
  const saucerGeo = new THREE.CylinderGeometry(22, 22, 3, 32);
  const saucer = new THREE.Mesh(saucerGeo, hullMat);
  saucer.rotation.x = 0;
  saucer.position.set(0, 6, 0);
  ship.add(saucer);

  // Saucer rim
  const rimGeo = new THREE.TorusGeometry(22, 0.4, 8, 32);
  const rim = new THREE.Mesh(rimGeo, trimMat);
  rim.rotation.x = Math.PI / 2;
  rim.position.set(0, 6, 0);
  ship.add(rim);

  // Bridge dome
  const bridgeGeo = new THREE.SphereGeometry(4, 16, 8, 0, Math.PI * 2, 0, Math.PI / 2);
  const bridge = new THREE.Mesh(bridgeGeo, new THREE.MeshPhongMaterial({ color: 0x99ccff, emissive: 0x4466aa, emissiveIntensity: 0.6, transparent: true, opacity: 0.7 }));
  bridge.position.set(0, 7.5, 0);
  ship.add(bridge);

  // Engineering hull (neck + body)
  const neckGeo = new THREE.CylinderGeometry(3, 4, 12, 8);
  const neck = new THREE.Mesh(neckGeo, hullMat);
  neck.position.set(0, 0, -12);
  ship.add(neck);

  const engGeo = new THREE.CylinderGeometry(5, 6, 20, 12);
  const eng = new THREE.Mesh(engGeo, hullMat);
  eng.rotation.x = Math.PI / 2;
  eng.position.set(0, -4, -22);
  ship.add(eng);

  // Deflector dish
  const defGeo = new THREE.SphereGeometry(4, 16, 8, 0, Math.PI * 2, 0, Math.PI / 2);
  const deflector = new THREE.Mesh(defGeo, deflectorMat);
  deflector.rotation.x = Math.PI / 2;
  deflector.position.set(0, -6, -32);
  ship.add(deflector);

  // Nacelle pylons
  const pylonGeo = new THREE.BoxGeometry(1, 1, 18);
  const pylonL = new THREE.Mesh(pylonGeo, trimMat);
  pylonL.position.set(-18, 0, -18);
  pylonL.rotation.z = -0.3;
  ship.add(pylonL);
  const pylonR = new THREE.Mesh(pylonGeo, trimMat);
  pylonR.position.set(18, 0, -18);
  pylonR.rotation.z = 0.3;
  ship.add(pylonR);

  // Nacelles
  const nacelleGeo = new THREE.CylinderGeometry(2.5, 2.5, 28, 12);
  const nacelleL = new THREE.Mesh(nacelleGeo, nacelleMat);
  nacelleL.rotation.x = Math.PI / 2;
  nacelleL.position.set(-22, 2, -20);
  ship.add(nacelleL);
  const nacelleR = new THREE.Mesh(nacelleGeo, nacelleMat);
  nacelleR.rotation.x = Math.PI / 2;
  nacelleR.position.set(22, 2, -20);
  ship.add(nacelleR);

  // Engine glow caps (rear of nacelles)
  const glowGeo = new THREE.SphereGeometry(2.8, 12, 8);
  const glowL = new THREE.Mesh(glowGeo, engineGlowMat.clone());
  glowL.position.set(-22, 2, -34);
  ship.add(glowL);
  const glowR = new THREE.Mesh(glowGeo, engineGlowMat.clone());
  glowR.position.set(22, 2, -34);
  ship.add(glowR);

  // Bussard collectors (front of nacelles)
  const bussardMat = new THREE.MeshBasicMaterial({ color: 0xcc4444, transparent: true, opacity: 0.8 });
  const bussardGeo = new THREE.SphereGeometry(2.6, 12, 8);
  const bussardL = new THREE.Mesh(bussardGeo, bussardMat.clone());
  bussardL.position.set(-22, 2, -6);
  ship.add(bussardL);
  const bussardR = new THREE.Mesh(bussardGeo, bussardMat.clone());
  bussardR.position.set(22, 2, -6);
  ship.add(bussardR);

  // Running lights
  const rlGeo = new THREE.SphereGeometry(0.6, 8, 8);
  const rlTop = new THREE.Mesh(rlGeo, runLightMat.clone());
  rlTop.position.set(0, 8.5, 0);
  ship.add(rlTop);

  ship.position.set(0, 0, 0);
  ship.rotation.x = -0.15;
  scene.add(ship);

  // --- Warp trail particles ---
  const trailCount = 300;
  const trailGeo = new THREE.BufferGeometry();
  const trailPos = new Float32Array(trailCount * 3);
  for (let i = 0; i < trailCount; i++) {
    trailPos[i * 3] = (Math.random() - 0.5) * 60;
    trailPos[i * 3 + 1] = (Math.random() - 0.5) * 40;
    trailPos[i * 3 + 2] = -35 - Math.random() * 120;
  }
  trailGeo.setAttribute('position', new THREE.BufferAttribute(trailPos, 3));
  const trailMat = new THREE.PointsMaterial({ color: 0x6688cc, size: 0.8, transparent: true, opacity: 0.5 });
  const trails = new THREE.Points(trailGeo, trailMat);
  scene.add(trails);

  // --- State ---
  let currentStats = { ...stats };
  let alertMode = hasAlerts;
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);
    const t = clock.getElapsedTime();
    const dt = clock.getDelta();

    // Move stars toward camera (warp effect)
    const positions = stars.geometry.attributes.position.array;
    const speed = 80;
    for (let i = 0; i < starCount; i++) {
      positions[i * 3 + 2] += speed * 0.016;
      if (positions[i * 3 + 2] > 200) {
        positions[i * 3 + 2] = -1500;
        positions[i * 3] = (Math.random() - 0.5) * 600;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 400;
      }
    }
    stars.geometry.attributes.position.needsUpdate = true;

    // Move warp trails
    const tp = trails.geometry.attributes.position.array;
    for (let i = 0; i < trailCount; i++) {
      tp[i * 3 + 2] += 150 * 0.016;
      if (tp[i * 3 + 2] > 10) {
        tp[i * 3 + 2] = -35 - Math.random() * 120;
        tp[i * 3] = (Math.random() - 0.5) * 60;
        tp[i * 3 + 1] = (Math.random() - 0.5) * 40;
      }
    }
    trails.geometry.attributes.position.needsUpdate = true;

    // Ship gentle movement
    ship.position.y = Math.sin(t * 0.5) * 1.5;
    ship.rotation.z = Math.sin(t * 0.3) * 0.02;

    // Engine glow based on crew activity
    const crewRatio = currentStats.agentsTotal > 0 ? currentStats.agentsActive / currentStats.agentsTotal : 0;
    const glowPulse = 0.6 + Math.sin(t * 3) * 0.3;
    const baseIntensity = 0.4 + crewRatio * 1.2;
    glowL.material.emissiveIntensity = baseIntensity * glowPulse;
    glowR.material.emissiveIntensity = baseIntensity * glowPulse;
    glowL.material.opacity = 0.4 + crewRatio * 0.5;
    glowR.material.opacity = 0.4 + crewRatio * 0.5;

    // Bussard collectors pulse with mission count
    const missionPulse = 0.5 + Math.sin(t * 2) * 0.3;
    const missionIntensity = Math.min(1, 0.3 + (currentStats.inProgress || 0) * 0.1);
    bussardL.material.opacity = missionIntensity * missionPulse + 0.2;
    bussardR.material.opacity = missionIntensity * missionPulse + 0.2;

    // Running light blink
    rlTop.material.opacity = Math.sin(t * 4) > 0 ? 1 : 0.2;

    // Red alert: tint nacelles and increase fog redness
    if (alertMode) {
      const alertPulse = 0.5 + Math.sin(t * 4) * 0.5;
      nacelleMat.emissive.setHex(0xaa2222);
      nacelleMat.emissiveIntensity = 0.4 + alertPulse * 0.8;
      scene.fog.color.setHex(0x1a0808);
      renderer.setClearColor(0x0a0505);
      trailMat.color.setHex(0xcc4444);
    } else {
      nacelleMat.emissive.setHex(0x3366aa);
      nacelleMat.emissiveIntensity = 0.8;
      scene.fog.color.setHex(0x0a0a1a);
      renderer.setClearColor(0x0a0a1a);
      trailMat.color.setHex(0x6688cc);
    }

    // Deflector pulse based on completed missions
    const doneRatio = Math.min(1, (currentStats.done || 0) / 50);
    deflector.material.emissiveIntensity = 0.5 + doneRatio * 1.0 + Math.sin(t * 1.5) * 0.3;

    renderer.render(scene, camera);
  }
  animate();

  // Resize handler
  const onResize = () => {
    const w = container.clientWidth;
    const h = container.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
  };
  window.addEventListener('resize', onResize);

  return {
    update(newStats, newAlerts) {
      currentStats = { ...newStats };
      alertMode = newAlerts;
    }
  };
}
</script>
</body>
</html>
