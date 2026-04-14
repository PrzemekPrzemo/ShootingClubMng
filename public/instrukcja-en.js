// English translations for section contents
window.SECTIONS = window.SECTIONS || {};
window.SECTIONS.en = {

intro: `
<h1>Shooting club management system</h1>
<p class="lead">Shootero is a complete system for managing athletes, licenses, competitions, trainings and club finances at shooting clubs.</p>
<div class="cards">
  <div class="card"><div class="card-icon">👥</div><h3>5 user types</h3><p>Club administrator, board, instructor, judge and athlete — each with dedicated permissions.</p></div>
  <div class="card"><div class="card-icon">🏆</div><h3>Competition management</h3><p>Create competitions, online registration, start-list generator with CSV import, PDF protocols.</p></div>
  <div class="card"><div class="card-icon">💰</div><h3>Club finances</h3><p>Membership fees, PZSS fees, online payments (Przelewy24), configurable fee tariff per class.</p></div>
  <div class="card"><div class="card-icon">🎯</div><h3>Athlete portal</h3><p>Every athlete has their own panel for managing data, medical exams and sign-ups.</p></div>
</div>
<h2>How to use this manual</h2>
<ol>
  <li>Pick your role from the left menu</li>
  <li>See mock screens simulating real UI</li>
  <li>Go to <strong>Scenarios</strong> for step-by-step common workflows</li>
  <li>Open demo: <a href="https://portal.shootero.pl/demo?token=5fe8f35414dc82d72ddc64b7fd58621d" target="_blank">portal.shootero.pl/demo</a></li>
</ol>
`,

login: `
<h1>Logging in</h1>
<p class="lead">The system has two login screens — both accept staff and athletes alike.</p>
<div class="info-box"><strong>💡 Good news:</strong> You don't need to remember which URL is for whom. Both work for all user types.</div>
<h2>A. Universal login screen</h2>
<div class="screen">
  <div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
    <span class="screen-url">portal.shootero.pl/auth/login</span>
  </div>
  <div class="screen-body" style="max-width:400px; margin:0 auto;">
    <div style="text-align:center; margin-bottom:1.5rem;"><div style="font-size:2.5rem; color:#D4A373;">●</div><h3>SHOOTERO</h3></div>
    <div class="sim-field"><label class="sim-label">Login</label>
      <input type="text" class="sim-input" placeholder="Login, email, PESEL or license number" readonly>
      <div style="font-size:.7rem; color:#64748b; margin-top:.25rem;">Athletes: log in with email / PESEL (first-time password = PESEL)</div>
    </div>
    <div class="sim-field"><label class="sim-label">Password</label>
      <input type="password" class="sim-input" value="••••••••" readonly>
    </div>
    <button class="sim-btn" style="width:100%; padding:.75rem;">Sign in</button>
  </div>
</div>
<h2>B. Portal login with toggle</h2>
<div class="screen">
  <div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
    <span class="screen-url">portal.shootero.pl/portal/login</span>
  </div>
  <div class="screen-body" style="max-width:400px; margin:0 auto;">
    <div style="display:flex; gap:.25rem; margin-bottom:1rem;">
      <button class="sim-btn" style="flex:1;">👤 Athlete</button>
      <button class="sim-btn secondary" style="flex:1;">🛡 Staff</button>
    </div>
    <div class="sim-field"><label class="sim-label">Email</label><input type="email" class="sim-input" value="jan@example.pl" readonly></div>
    <div class="sim-field"><label class="sim-label">Password</label><input type="password" class="sim-input" placeholder="Password or PESEL (first login)" readonly></div>
    <button class="sim-btn" style="width:100%; padding:.75rem;">Sign in</button>
  </div>
</div>
<h2>Athlete first login</h2>
<div class="steps">
  <div class="step"><div class="step-content"><h4>Go to /portal/login</h4><p>Or universal /auth/login</p></div></div>
  <div class="step"><div class="step-content"><h4>Login = email, PESEL or license #</h4><p>Any value from your club profile</p></div></div>
  <div class="step"><div class="step-content"><h4>Password = your PESEL</h4><p>Your 11-digit PESEL number</p></div></div>
  <div class="step"><div class="step-content"><h4>Change password</h4><p>Min 8 chars, cannot be PESEL</p></div></div>
</div>
<h2>Password reset</h2>
<ul>
  <li><strong>Athlete:</strong> <code>/portal/reset-password</code> (requires email + PESEL)</li>
  <li><strong>Staff:</strong> contact club administrator</li>
</ul>
`,

admin: `
<h1>Club Administrator</h1>
<p class="lead">Full control over your club — athletes, finances, competitions, configuration.</p>
<h2>Club dashboard</h2>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/dashboard</span></div>
<div class="screen-body">
  <div class="sim-nav" style="flex-wrap:wrap;">
    <span class="sim-nav-item active">Dashboard</span><span class="sim-nav-item">Athletes</span><span class="sim-nav-item">Licenses</span><span class="sim-nav-item">Finances</span><span class="sim-nav-item">Competitions</span><span class="sim-nav-item">Start lists</span><span class="sim-nav-item">Judges</span><span class="sim-nav-item">PZSS fees</span><span class="sim-nav-item">Equipment</span><span class="sim-nav-item">Trainings</span><span class="sim-nav-item">Calendar</span><span class="sim-nav-item">Reports</span><span class="sim-nav-item">Settings</span>
  </div>
  <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
    <div class="sim-card"><div style="color:#94a3b8; font-size:.85rem;">Active athletes</div><div style="font-size:1.8rem; color:#D4A373; font-weight:700;">78</div></div>
    <div class="sim-card"><div style="color:#94a3b8; font-size:.85rem;">Fees collected 2026</div><div style="font-size:1.8rem; color:#4ade80; font-weight:700;">23,450 PLN</div></div>
    <div class="sim-card"><div style="color:#94a3b8; font-size:.85rem;">Expiring licenses (30 days)</div><div style="font-size:1.8rem; color:#fbbf24; font-weight:700;">4</div></div>
  </div>
</div></div>
<h2>Administrator permissions</h2>
<p>Club administrator has access to <strong>all 15 modules</strong>:</p>
<ul><li>Dashboard, Athletes, Licenses, Finances</li><li>Competitions, Start lists, Judges, PZSS fees</li><li>Equipment, Trainings, Announcements, Calendar</li><li>Reports, Settings, Security</li></ul>
<div class="info-box"><strong>💡 Permissions:</strong> In <code>Settings → Role permissions</code> you can customize what Board, Instructor and Judge see.</div>
`,

zarzad: `
<h1>Club Board</h1>
<p class="lead">Board members have access to all club modules <strong>except</strong> Security.</p>
<h2>What Board sees</h2>
<table class="matrix">
<tr><th>Module</th><th>Permissions</th></tr>
<tr><td>Dashboard</td><td class="yes">✓ Full</td></tr>
<tr><td>Athletes</td><td class="yes">✓ Add, edit, remove</td></tr>
<tr><td>Licenses</td><td class="yes">✓ Full management</td></tr>
<tr><td>Finances</td><td class="yes">✓ Payments, reports, outstanding</td></tr>
<tr><td>Competitions</td><td class="yes">✓ Create, results, protocols</td></tr>
<tr><td>Judges</td><td class="yes">✓ Judge registry</td></tr>
<tr><td>PZSS fees</td><td class="yes">✓ Fee management</td></tr>
<tr><td>Equipment</td><td class="yes">✓ Club + member weapons</td></tr>
<tr><td>Trainings, Announcements, Calendar</td><td class="yes">✓ Full</td></tr>
<tr><td>Reports</td><td class="yes">✓ Data export</td></tr>
<tr><td>Settings</td><td class="yes">✓ Club configuration</td></tr>
<tr><td>Security</td><td class="no">✗ Administrator only</td></tr>
</table>
<h2>Typical board tasks</h2>
<ul>
<li>Review club finances and outstanding fees</li>
<li>Approve documents (medical exams, licenses)</li>
<li>Communication: announcements to all athletes</li>
<li>Generate PZSS financial reports</li>
<li>Oversight of club strategy</li>
</ul>
`,

instruktor: `
<h1>Instructor</h1>
<p class="lead">Trainers and instructors. Work with their athletes — trainings, competitions, equipment.</p>
<h2>Instructor menu</h2>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/dashboard</span></div>
<div class="screen-body">
  <div class="sim-nav">
    <span class="sim-nav-item active">Dashboard</span><span class="sim-nav-item">Athletes</span><span class="sim-nav-item">Licenses</span><span class="sim-nav-item">Competitions</span><span class="sim-nav-item">Start lists</span><span class="sim-nav-item">Equipment</span><span class="sim-nav-item">Trainings</span><span class="sim-nav-item">Calendar</span><span class="sim-nav-item">Reports</span>
  </div>
  <p style="color:#94a3b8; margin-top:1rem;">No access to: Finances, PZSS fees, Judges, Settings, Security</p>
</div></div>
<h2>Typical instructor tasks</h2>
<h3>Schedule a training</h3>
<div class="steps">
<div class="step"><div class="step-content"><h4>Go to Trainings</h4><p><code>/trainings/create</code></p></div></div>
<div class="step"><div class="step-content"><h4>Date, time, duration</h4><p>Set training parameters</p></div></div>
<div class="step"><div class="step-content"><h4>Discipline</h4><p>Select training type</p></div></div>
<div class="step"><div class="step-content"><h4>Assigned athletes</h4><p>They register via their portal</p></div></div>
</div>
<h3>Enter competition results</h3>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/competitions/15/results</span></div>
<div class="screen-body">
  <div class="sim-card"><div class="sim-card-header">Air pistol 40 shots</div>
  <table class="sim-table">
  <thead><tr><th>Athlete</th><th>S1</th><th>S2</th><th>S3</th><th>S4</th><th>Total</th></tr></thead>
  <tbody>
  <tr><td>Kowalski Jan</td><td>94</td><td>97</td><td>95</td><td>96</td><td><strong style="color:#4ade80;">382</strong></td></tr>
  <tr><td>Nowak Anna</td><td>91</td><td>93</td><td>94</td><td>92</td><td><strong>370</strong></td></tr>
  <tr><td>Wójcik Piotr</td><td>88</td><td>90</td><td>89</td><td>91</td><td><strong>358</strong></td></tr>
  </tbody></table>
  <button class="sim-btn" style="margin-top:1rem;">Save results</button></div>
</div></div>
`,

sedzia: `
<h1>Judge</h1>
<p class="lead">Minimal menu — only Competitions and Calendar.</p>
<h2>What the judge sees</h2>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/dashboard</span></div>
<div class="screen-body"><div class="sim-nav"><span class="sim-nav-item active">Dashboard</span><span class="sim-nav-item">Competitions</span><span class="sim-nav-item">Calendar</span></div></div></div>
<h2>Typical judge tasks</h2>
<ul>
<li>Enter competition results (<code>/competitions/{id}/results</code>)</li>
<li>Enter series per event (<code>/competitions/{id}/events/{eid}/series</code>)</li>
<li>Generate classification rankings</li>
<li>Export PDF protocols</li>
</ul>
<div class="info-box"><strong>ℹ System judge vs PZSS license:</strong> The system distinguishes between the <strong>system role</strong> <code>judge</code> (login access to enter results) and the <strong>PZSS judge license</strong> (document in <code>/judges</code> registry). An athlete can have a PZSS license without a user account, and vice versa.</div>
<h2>Club judge registry</h2>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/judges</span></div>
<div class="screen-body">
  <div class="sim-card"><div class="sim-card-header">Judge registry <span class="sim-badge info">3 licenses</span></div>
  <table class="sim-table">
  <thead><tr><th>Athlete</th><th>Class</th><th>Discipline</th><th>License #</th><th>Valid until</th><th>PomZSS fee</th></tr></thead>
  <tbody>
  <tr><td>Kowalski Jan 👤</td><td><span class="sim-badge info">II</span></td><td>Pistol</td><td>LS-123/2024</td><td>31.12.2026 <span class="sim-badge success">in 260 days</span></td><td><span class="sim-badge success">✓ 2026</span></td></tr>
  <tr><td>Nowak Anna</td><td><span class="sim-badge info">III</span></td><td>Rifle</td><td>LS-456/2024</td><td>31.12.2026 <span class="sim-badge success">in 260 days</span></td><td><span class="sim-badge danger">Unpaid</span></td></tr>
  </tbody></table></div>
</div></div>
`,

zawodnik: `
<h1>Athlete (portal)</h1>
<p class="lead">Every club member has their own portal to manage data, medical exams, register for competitions and pay fees.</p>
<h2>Athlete portal dashboard</h2>
<div class="screen"><div class="screen-header"><span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
<span class="screen-url">portal.shootero.pl/portal</span></div>
<div class="screen-body">
  <div class="sim-nav">
    <span class="sim-nav-item active">🏠 Dashboard</span><span class="sim-nav-item">👤 Profile</span><span class="sim-nav-item">🏥 Medical</span><span class="sim-nav-item">🏆 Competitions</span><span class="sim-nav-item">📊 Results</span><span class="sim-nav-item">💰 Fees</span><span class="sim-nav-item">🔫 Weapons</span><span class="sim-nav-item">🎯 Trainings</span>
  </div>
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
    <div class="sim-card"><div class="sim-card-header">Upcoming competitions</div>
    <p style="font-size:.9rem;">🏆 <strong>Polish Championship - Bydgoszcz</strong><br><span style="color:#94a3b8;">June 12, 2026</span></p>
    <button class="sim-btn" style="font-size:.85rem;">Register</button></div>
    <div class="sim-card"><div class="sim-card-header">Document status</div>
    <p>Athlete license <span class="sim-badge success">Active</span></p>
    <p>Medical exam <span class="sim-badge warning">expires in 23 days</span></p>
    <p>Fee 2026 <span class="sim-badge danger">Unpaid</span></p></div>
  </div>
</div></div>
<h2>Portal features</h2>
<table class="matrix">
<tr><th>Section</th><th>URL</th><th>Function</th></tr>
<tr><td>Dashboard</td><td><code>/portal</code></td><td>Home page with shortcuts</td></tr>
<tr><td>Profile</td><td><code>/portal/profile</code></td><td>View your data</td></tr>
<tr><td>Edit contact</td><td><code>/portal/profile/edit</code></td><td>Phone, address (only)</td></tr>
<tr><td>Medical exams</td><td><code>/portal/exams</code></td><td>Upload PDF/JPG/PNG (max 5MB)</td></tr>
<tr><td>Competitions</td><td><code>/portal/competitions</code></td><td>Available events list</td></tr>
<tr><td>Register</td><td><code>/portal/competitions/{id}/register</code></td><td>Pick events</td></tr>
<tr><td>Results</td><td><code>/portal/results</code></td><td>Your result history</td></tr>
<tr><td>Fees</td><td><code>/portal/fees</code></td><td>Outstanding + online payment</td></tr>
<tr><td>My weapons</td><td><code>/portal/weapons</code></td><td>Your weapon registry</td></tr>
<tr><td>Trainings</td><td><code>/portal/trainings</code></td><td>Enrol / unenrol</td></tr>
<tr><td>Change password</td><td><code>/portal/change-password</code></td><td>Change your password</td></tr>
</table>
<h2>First login</h2>
<div class="steps">
<div class="step"><div class="step-content"><h4>Open /portal/login</h4><p>Ask club admin for the address — you'll get a link</p></div></div>
<div class="step"><div class="step-content"><h4>Login = email, PESEL or license #</h4><p>Anything from your club profile</p></div></div>
<div class="step"><div class="step-content"><h4>Password = your PESEL</h4><p>11-digit PESEL number</p></div></div>
<div class="step"><div class="step-content"><h4>System forces password change</h4><p>Min 8 chars, cannot be PESEL</p></div></div>
</div>
<div class="info-box"><strong>⚠ Status blocks login:</strong> If your club status is <em>suspended</em> or <em>removed</em>, you cannot log in. Contact club office.</div>
`
};
