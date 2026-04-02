<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USS PAPERCLIP — LCARS Interface</title>
<link href="https://fonts.googleapis.com/css2?family=Antonio:wght@400;700&family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
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

  .starfield {
    position: absolute;
    inset: 0;
  }

  .star {
    position: absolute;
    background: #fff;
    border-radius: 50%;
    animation: twinkle var(--dur) ease-in-out infinite alternate;
  }

  @keyframes twinkle {
    0% { opacity: 0.2; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1.2); }
  }

  @keyframes warpStreaks {
    0% { transform: translateX(0); opacity: 0; }
    10% { opacity: 1; }
    100% { transform: translateX(400px); opacity: 0; }
  }

  .warp-streak {
    position: absolute;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--lcars-lightblue), transparent);
    animation: warpStreaks var(--dur) linear infinite;
    opacity: 0;
  }

  .ship-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 5;
  }

  .ship-svg {
    width: 280px;
    height: 200px;
    filter: drop-shadow(0 0 20px rgba(153, 204, 255, 0.4));
  }

  .ship-glow {
    animation: engineGlow 2s ease-in-out infinite alternate;
  }

  @keyframes engineGlow {
    0% { filter: drop-shadow(0 0 8px rgba(102, 136, 204, 0.6)); }
    100% { filter: drop-shadow(0 0 24px rgba(153, 204, 255, 0.9)); }
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
    .ship-svg { width: 180px; height: 130px; }
  }

  .no-alerts {
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--lcars-green);
    padding: 8px;
  }
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
    <div class="viewscreen">
      <div class="viewscreen-label">MAIN VIEWSCREEN — FORWARD</div>
      <div class="starfield" id="starfield"></div>
      <div class="ship-container ship-glow">
        <svg class="ship-svg" viewBox="0 0 280 200" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="140" cy="70" rx="70" ry="30" fill="none" stroke="#99CCFF" stroke-width="1.5" opacity="0.8"/>
          <ellipse cx="140" cy="70" rx="60" ry="24" fill="#0d1a2d" stroke="#6688CC" stroke-width="1"/>
          <ellipse cx="140" cy="70" rx="50" ry="18" fill="none" stroke="#334466" stroke-width="0.5"/>
          <ellipse cx="140" cy="65" rx="12" ry="6" fill="none" stroke="#99CCFF" stroke-width="1"/>
          <ellipse cx="140" cy="65" rx="6" ry="3" fill="#99CCFF" opacity="0.4"/>
          <path d="M135 95 L130 130 L150 130 L145 95" fill="#0d1a2d" stroke="#6688CC" stroke-width="1"/>
          <ellipse cx="140" cy="140" rx="25" ry="14" fill="#0d1a2d" stroke="#6688CC" stroke-width="1"/>
          <ellipse cx="140" cy="150" rx="8" ry="5" fill="#336699" stroke="#99CCFF" stroke-width="0.5">
            <animate attributeName="opacity" values="0.4;0.9;0.4" dur="3s" repeatCount="indefinite"/>
          </ellipse>
          <line x1="125" y1="130" x2="70" y2="145" stroke="#6688CC" stroke-width="2"/>
          <line x1="155" y1="130" x2="210" y2="145" stroke="#6688CC" stroke-width="2"/>
          <rect x="45" y="140" width="50" height="10" rx="5" fill="#0d1a2d" stroke="#6688CC" stroke-width="1"/>
          <rect x="185" y="140" width="50" height="10" rx="5" fill="#0d1a2d" stroke="#6688CC" stroke-width="1"/>
          <circle cx="50" cy="145" r="4" fill="#CC4444" opacity="0.8">
            <animate attributeName="opacity" values="0.5;1;0.5" dur="1.5s" repeatCount="indefinite"/>
          </circle>
          <circle cx="230" cy="145" r="4" fill="#CC4444" opacity="0.8">
            <animate attributeName="opacity" values="0.5;1;0.5" dur="1.5s" repeatCount="indefinite" begin="0.3s"/>
          </circle>
          <rect x="55" y="142" width="35" height="6" rx="3" fill="#6688CC" opacity="0.5">
            <animate attributeName="opacity" values="0.3;0.7;0.3" dur="2s" repeatCount="indefinite"/>
          </rect>
          <rect x="195" y="142" width="35" height="6" rx="3" fill="#6688CC" opacity="0.5">
            <animate attributeName="opacity" values="0.3;0.7;0.3" dur="2s" repeatCount="indefinite" begin="0.5s"/>
          </rect>
          <text x="140" y="75" text-anchor="middle" fill="#99CCFF" font-family="Orbitron, sans-serif" font-size="6" letter-spacing="2" opacity="0.7">NCC-2026</text>
        </svg>
      </div>
      <div class="viewscreen-stats">
        <div class="viewscreen-stat">CREW: <span x-text="stats.agentsTotal + ' ABOARD / ' + stats.agentsActive + ' ON DUTY'"></span></div>
        <div class="viewscreen-stat">MISSIONS: <span x-text="stats.inProgress + ' ACTIVE'"></span></div>
        <div class="viewscreen-stat">COMPLETED: <span x-text="stats.done"></span></div>
        <div class="viewscreen-stat" x-show="stats.blocked > 0" style="color: var(--lcars-red)">⚠ ALERTS: <span x-text="stats.blocked"></span></div>
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
      createStarfield();
      this.lastSync = new Date().toLocaleTimeString();
      // Auto-refresh every 60 seconds
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

function createStarfield() {
  const sf = document.getElementById('starfield');
  for (let i = 0; i < 120; i++) {
    const star = document.createElement('div');
    star.className = 'star';
    const size = Math.random() * 2.5 + 0.5;
    star.style.cssText = `
      width: ${size}px; height: ${size}px;
      top: ${Math.random() * 100}%;
      left: ${Math.random() * 100}%;
      --dur: ${Math.random() * 3 + 1.5}s;
      animation-delay: ${Math.random() * 3}s;
    `;
    sf.appendChild(star);
  }
  for (let i = 0; i < 15; i++) {
    const streak = document.createElement('div');
    streak.className = 'warp-streak';
    const w = Math.random() * 80 + 40;
    streak.style.cssText = `
      width: ${w}px;
      top: ${Math.random() * 100}%;
      left: ${Math.random() * 60 - 10}%;
      --dur: ${Math.random() * 4 + 3}s;
      animation-delay: ${Math.random() * 6}s;
    `;
    sf.appendChild(streak);
  }
}
</script>
</body>
</html>
