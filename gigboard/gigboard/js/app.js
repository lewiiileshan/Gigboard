/* ==========================================================
   GigBoard KE - shared logic (every page loads this first)

   For now the "database" is the browser's localStorage, so the
   prototype works without a server. In the Laravel build:
     - the arrays in `db` become MySQL tables (see gigboard_schema.sql)
     - the functions in the "Data actions" section become controllers
     - renderShell() becomes a Blade layout
   ========================================================== */

/* ---------- Constants ---------- */
const CATEGORIES = [
  'Graphic Design', 'Writing & Translation', 'Data Entry',
  'Photo & Video', 'Web & Software', 'Social Media', 'Errands & Delivery'
];

const CAT_ICON = {
  'Graphic Design': '🎨',
  'Writing & Translation': '✍️',
  'Data Entry': '🗂️',
  'Photo & Video': '📷',
  'Web & Software': '💻',
  'Social Media': '📣',
  'Errands & Delivery': '🛵'
};

// Nairobi areas with a position on the stylised map (x, y in %)
const AREAS = {
  'Westlands': { x: 36, y: 28 },
  'Lavington': { x: 22, y: 46 },
  'Kilimani': { x: 36, y: 62 },
  'CBD': { x: 52, y: 46 },
  'Upper Hill': { x: 50, y: 66 },
  'Kasarani': { x: 70, y: 18 },
  'Embakasi': { x: 78, y: 66 },
  'Karen': { x: 20, y: 84 }
};

const DB_KEY = 'gigboard_db_v1';
const SESSION_KEY = 'gigboard_session_v1';

/* ---------- Small helpers ---------- */
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

// Escape text before putting it into HTML (stops users injecting <script>)
function esc(text) {
  return String(text ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function ksh(amount) {
  return 'KSh ' + Number(amount || 0).toLocaleString('en-KE');
}

function stars(n) {
  const r = Math.round(n || 0);
  return '★'.repeat(r) + '☆'.repeat(5 - r);
}

function initials(name) {
  return String(name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function timeAgo(ts) {
  const mins = Math.floor((Date.now() - ts) / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return mins + ' min ago';
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return hrs + ' h ago';
  const days = Math.floor(hrs / 24);
  return days + (days === 1 ? ' day ago' : ' days ago');
}

function toast(message, type) {
  let el = $('#toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = message;
  el.className = 'show ' + (type || '');
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => { el.className = ''; }, 2600);
}

/* ==========================================================
   Seed data - demo accounts and sample gigs
   Demo logins (password for all: demo1234)
     client@demo.com      Wanjiru Kamau (client)
     freelancer@demo.com  Grace Wambui  (freelancer)
   ========================================================== */
function defaultSettings() {
  return { notifyApplications: true, notifyPayments: true, notifyTips: false };
}

function seedData() {
  const now = Date.now();
  const day = 86400000;
  const user = (id, name, email, role, location, bio, skills, rate, ago) => ({
    id, name, email, role, location, bio, skills, rate,
    password: 'demo1234', phone: '07' + (10000000 + id * 1234567).toString().slice(0, 8),
    createdAt: now - ago * day, settings: defaultSettings()
  });

  const users = [
    user(1, 'Wanjiru Kamau', 'client@demo.com', 'client', 'Kilimani', 'I run a small cafe and boutique in Nairobi and post design and delivery gigs.', [], 0, 90),
    user(2, 'Brian Otieno', 'brian@demo.com', 'client', 'CBD', 'Operations manager at a logistics startup.', [], 0, 60),
    user(3, 'Grace Wambui', 'freelancer@demo.com', 'freelancer', 'Westlands', 'Web designer and developer with 3 years building small business sites.', ['Web design', 'Laravel', 'Logo design', 'Social media'], 1500, 120),
    user(4, 'Ian Tarus', 'ian@demo.com', 'freelancer', 'CBD', 'Photographer and video editor for products and events.', ['Photography', 'Video editing'], 1200, 80),
    user(5, 'Faith Njeri', 'faith@demo.com', 'client', 'Westlands', 'Owner of an online clothing shop.', [], 0, 45),
    user(6, 'Kevin Mwangi', 'kevin@demo.com', 'freelancer', 'Kasarani', 'Graphic designer: logos, flyers and brand kits.', ['Logo design', 'Posters', 'Branding'], 1000, 70),
    user(7, 'Mercy Achieng', 'mercy@demo.com', 'freelancer', 'Embakasi', 'Writer and translator (English and Swahili).', ['Copywriting', 'Translation'], 800, 50),
    user(8, 'Peter Mutua', 'peter@demo.com', 'freelancer', 'Lavington', 'Full-stack developer.', ['PHP', 'JavaScript', 'MySQL'], 1800, 100)
  ];

  const gig = (id, clientId, category, title, desc, budget, location, status, ago, extra) => Object.assign({
    id, clientId, category, title, desc, budget, location, status,
    deadline: '', hiredAppId: null, paid: false, payRef: '', createdAt: now - ago * day
  }, extra || {});

  const gigs = [
    gig(1, 1, 'Graphic Design', 'Logo for a Kilimani cafe', 'Simple modern logo with a coffee-cup motif. Two revisions included. Deliver PNG and vector files.', 3500, 'Kilimani', 'open', 1),
    gig(2, 2, 'Writing & Translation', 'Translate flyer from English to Swahili', 'One page promotional flyer. Natural, friendly Swahili. Needed within 3 days.', 1200, 'Remote', 'open', 2),
    gig(3, 5, 'Data Entry', 'Enter 200 survey forms into Excel', 'Scanned forms will be shared by email. Accuracy matters more than speed.', 2500, 'Remote', 'open', 2),
    gig(4, 5, 'Photo & Video', 'Product photos for 15 clothing items', 'White background, 3 angles per item. Studio space available in Westlands.', 4000, 'Westlands', 'open', 3),
    gig(5, 2, 'Errands & Delivery', 'Deliver documents to Upper Hill', 'Two sealed envelopes from CBD to Upper Hill. Same day delivery.', 600, 'Upper Hill', 'open', 0),
    gig(6, 1, 'Web & Software', 'One-page website for a salon', 'Mobile friendly landing page with services, prices and a WhatsApp button.', 8000, 'Remote', 'open', 4),
    gig(7, 1, 'Social Media', 'Instagram content calendar for one month', '12 post ideas with captions and hashtags for a cafe.', 3000, 'Remote', 'in_progress', 6, { hiredAppId: 104 }),
    gig(8, 1, 'Graphic Design', 'Wedding brochure design', 'A5 brochure, 4 pages, print ready.', 5000, 'Remote', 'completed', 25, { hiredAppId: 105, paid: true, payRef: 'SJK3L9XQ2P' }),
    gig(9, 1, 'Photo & Video', 'Shop signboard and interior photos', '10 edited photos of the new shop for social media.', 2000, 'CBD', 'completed', 12, { hiredAppId: 106 }),
    gig(10, 2, 'Web & Software', 'Fix contact form on company website', 'The form does not send emails. PHP site.', 2200, 'Remote', 'open', 1),
    gig(11, 5, 'Errands & Delivery', 'Pick up a parcel in Karen', 'Collect a parcel and deliver to Westlands.', 900, 'Karen', 'open', 0),
    gig(12, 2, 'Photo & Video', 'Event photography, 3 hours', 'Small corporate event in Embakasi. Edited photos within 48 hours.', 6500, 'Embakasi', 'open', 2)
  ];

  const app = (id, gigId, freelancerId, price, proposal, status, ago) => ({
    id, gigId, freelancerId, price, proposal, status, createdAt: now - ago * day
  });

  const applications = [
    app(101, 1, 6, 3200, 'I design logos for local cafes and can send three concepts within two days.', 'pending', 1),
    app(102, 6, 3, 7500, 'I have built several small business sites and can deliver within 5 days.', 'pending', 3),
    app(103, 6, 8, 6800, 'Full-stack developer, I can finish this in 4 days with a WhatsApp link.', 'pending', 3),
    app(104, 7, 3, 2800, 'I will prepare 12 posts with captions and hashtags in your cafe style.', 'accepted', 5),
    app(105, 8, 3, 4500, 'Print-ready brochure, files in PDF and source.', 'accepted', 24),
    app(106, 9, 4, 1800, 'I will visit tomorrow with my camera and deliver edits the same week.', 'accepted', 11),
    app(107, 8, 6, 4800, 'I can design this using your wedding colours.', 'rejected', 24)
  ];

  const reviews = [
    { id: 201, gigId: 8, reviewerId: 1, revieweeId: 3, rating: 5, comment: 'Delivered early and exactly what I wanted.', createdAt: now - 20 * day },
    { id: 202, gigId: 8, reviewerId: 3, revieweeId: 1, rating: 5, comment: 'Clear brief and paid promptly.', createdAt: now - 19 * day }
  ];

  return { users, gigs, applications, reviews, nextId: 300 };
}

/* ---------- Load / save the database ---------- */
let db = loadDB();

function loadDB() {
  try {
    const raw = localStorage.getItem(DB_KEY);
    if (raw) return JSON.parse(raw);
  } catch (e) { /* storage blocked: fall through to fresh data */ }
  const fresh = seedData();
  saveDB(fresh);
  return fresh;
}

function saveDB(data) {
  try { localStorage.setItem(DB_KEY, JSON.stringify(data || db)); } catch (e) { /* ignore */ }
}

function resetDemoData() {
  db = seedData();
  saveDB();
}

function newId() {
  db.nextId += 1;
  return db.nextId;
}

/* ==========================================================
   Authentication (demo only)
   Real build: Laravel Auth, passwords hashed with bcrypt.
   Here passwords are stored as plain text because this is a
   throwaway demo database in the browser.
   ========================================================== */
function userById(id) { return db.users.find(u => u.id === Number(id)); }

function me() {
  try {
    const id = localStorage.getItem(SESSION_KEY);
    return id ? userById(id) : null;
  } catch (e) { return null; }
}

function requireLogin() {
  const user = me();
  if (!user) { location.href = 'login.html'; return null; }
  return user;
}

function login(email, password) {
  const user = db.users.find(u => u.email.toLowerCase() === String(email).trim().toLowerCase());
  if (!user || user.password !== password) return { ok: false, error: 'Wrong email or password.' };
  localStorage.setItem(SESSION_KEY, String(user.id));
  return { ok: true, user };
}

function register(form) {
  const email = form.email.trim().toLowerCase();
  if (form.name.trim().length < 2) return { ok: false, error: 'Please enter your full name.' };
  if (!/^\S+@\S+\.\S+$/.test(email)) return { ok: false, error: 'Enter a valid email address.' };
  if (form.password.length < 6) return { ok: false, error: 'Password must be at least 6 characters.' };
  if (form.password !== form.confirm) return { ok: false, error: 'Passwords do not match.' };
  if (db.users.some(u => u.email.toLowerCase() === email)) return { ok: false, error: 'That email is already registered.' };

  const user = {
    id: newId(), name: form.name.trim(), email, phone: form.phone.trim(), password: form.password,
    role: form.role, location: 'Nairobi', bio: '', skills: [], rate: 0,
    createdAt: Date.now(), settings: defaultSettings()
  };
  db.users.push(user);
  saveDB();
  localStorage.setItem(SESSION_KEY, String(user.id));
  return { ok: true, user };
}

function logout() {
  try { localStorage.removeItem(SESSION_KEY); } catch (e) { /* ignore */ }
  location.href = 'login.html';
}

/* ==========================================================
   Data queries
   ========================================================== */
function getGig(id) { return db.gigs.find(g => g.id === Number(id)); }
function gigApplications(gigId) { return db.applications.filter(a => a.gigId === gigId); }
function hiredApp(gig) { return gig.hiredAppId ? db.applications.find(a => a.id === gig.hiredAppId) : null; }
function hasApplied(gigId, userId) { return db.applications.some(a => a.gigId === gigId && a.freelancerId === userId); }
function gigsOfClient(userId) { return db.gigs.filter(g => g.clientId === userId).sort((a, b) => b.createdAt - a.createdAt); }
function applicationsOf(userId) { return db.applications.filter(a => a.freelancerId === userId).sort((a, b) => b.createdAt - a.createdAt); }
function openGigs() { return db.gigs.filter(g => g.status === 'open').sort((a, b) => b.createdAt - a.createdAt); }

// Reviews stay hidden until BOTH people on a gig have reviewed each other
function reviewsRevealed(gigId) { return db.reviews.filter(r => r.gigId === gigId).length >= 2; }

function reviewsReceived(userId) {
  return db.reviews.filter(r => r.revieweeId === userId && reviewsRevealed(r.gigId));
}

function userRating(userId) {
  const list = reviewsReceived(userId);
  if (!list.length) return { avg: 0, count: 0 };
  return { avg: list.reduce((sum, r) => sum + r.rating, 0) / list.length, count: list.length };
}

function ratingLabel(userId) {
  const r = userRating(userId);
  return r.count ? `<span class="stars">★</span> ${r.avg.toFixed(1)} (${r.count})` : 'No reviews yet';
}

/* ==========================================================
   Data actions - each one returns { ok, error }
   ========================================================== */
function createGig(clientId, data) {
  if (data.title.trim().length < 5) return { ok: false, error: 'Give the gig a clearer title (at least 5 characters).' };
  if (data.desc.trim().length < 20) return { ok: false, error: 'Describe the work in at least 20 characters.' };
  if (!(Number(data.budget) >= 100)) return { ok: false, error: 'Budget must be at least KSh 100.' };
  const gig = {
    id: newId(), clientId, category: data.category, title: data.title.trim(), desc: data.desc.trim(),
    budget: Number(data.budget), location: data.location, deadline: data.deadline || '',
    status: 'open', hiredAppId: null, paid: false, payRef: '', createdAt: Date.now()
  };
  db.gigs.push(gig);
  saveDB();
  return { ok: true, gig };
}

function applyToGig(gigId, freelancerId, price, proposal) {
  const gig = getGig(gigId);
  if (!gig || gig.status !== 'open') return { ok: false, error: 'This gig is no longer open.' };
  if (hasApplied(gigId, freelancerId)) return { ok: false, error: 'You already applied to this gig.' };
  if (!(Number(price) >= 100)) return { ok: false, error: 'Enter your price (at least KSh 100).' };
  if (proposal.trim().length < 20) return { ok: false, error: 'Write a short proposal (at least 20 characters).' };
  db.applications.push({
    id: newId(), gigId, freelancerId, price: Number(price), proposal: proposal.trim(),
    status: 'pending', createdAt: Date.now()
  });
  saveDB();
  return { ok: true };
}

function hireApplicant(appId) {
  const app = db.applications.find(a => a.id === appId);
  const gig = app && getGig(app.gigId);
  if (!gig || gig.status !== 'open') return { ok: false, error: 'This gig can no longer be assigned.' };
  gigApplications(gig.id).forEach(a => { a.status = a.id === appId ? 'accepted' : 'rejected'; });
  gig.status = 'in_progress';
  gig.hiredAppId = appId;
  saveDB();
  return { ok: true };
}

function completeGig(gigId) {
  const gig = getGig(gigId);
  if (!gig || gig.status !== 'in_progress') return { ok: false, error: 'This gig is not in progress.' };
  gig.status = 'completed';
  saveDB();
  return { ok: true };
}

function setPayment(gigId, paid, ref) {
  const gig = getGig(gigId);
  if (!gig || gig.status !== 'completed') return { ok: false, error: 'Payment can be set once the gig is completed.' };
  gig.paid = paid;
  gig.payRef = paid ? (ref || '').trim().toUpperCase() : '';
  saveDB();
  return { ok: true };
}

function addReview(gigId, reviewerId, rating, comment) {
  const gig = getGig(gigId);
  const hired = gig && hiredApp(gig);
  if (!gig || gig.status !== 'completed' || !hired) return { ok: false, error: 'You can review after the gig is completed.' };
  if (db.reviews.some(r => r.gigId === gigId && r.reviewerId === reviewerId)) return { ok: false, error: 'You already reviewed this gig.' };
  if (!(rating >= 1 && rating <= 5)) return { ok: false, error: 'Pick a star rating first.' };
  const revieweeId = reviewerId === gig.clientId ? hired.freelancerId : gig.clientId;
  db.reviews.push({ id: newId(), gigId, reviewerId, revieweeId, rating, comment: comment.trim(), createdAt: Date.now() });
  saveDB();
  return { ok: true };
}

/* ==========================================================
   Page building blocks
   ========================================================== */
const STATUS = {
  open: { label: 'Open', cls: 'warn' },
  in_progress: { label: 'In progress', cls: '' },
  completed: { label: 'Completed', cls: 'ok' }
};

function statusTag(gig) {
  const s = STATUS[gig.status];
  return `<span class="tag ${s.cls}">${s.label}</span>`;
}

function gigCard(g) {
  const client = userById(g.clientId);
  return `
    <a class="card gig-card" href="gig.html?id=${g.id}">
      <div class="row">
        <span class="tag">${CAT_ICON[g.category] || ''} ${esc(g.category)}</span>
        <span class="price">${ksh(g.budget)}</span>
      </div>
      <h3>${esc(g.title)}</h3>
      <p class="muted clamp">${esc(g.desc)}</p>
      <div class="row meta">
        <span>📍 ${esc(g.location)}</span>
        <span>${esc(client ? client.name : '')} · ${timeAgo(g.createdAt)}</span>
      </div>
    </a>`;
}

function emptyState(icon, text) {
  return `<div class="empty"><span class="ico">${icon}</span>${text}</div>`;
}

// Navigation items per role. `mobile: true` = shown in the bottom bar on phones.
const NAV = {
  freelancer: [
    { key: 'dashboard', label: 'Home', icon: '🏠', href: 'dashboard.html', mobile: true },
    { key: 'gigs', label: 'Browse', icon: '🔍', href: 'gigs.html', mobile: true },
    { key: 'nearby', label: 'Nearby', icon: '📍', href: 'nearby.html', mobile: true },
    { key: 'activity', label: 'Activity', icon: '📋', href: 'activity.html', mobile: true },
    { key: 'payments', label: 'Payments', icon: '💳', href: 'payments.html', mobile: false },
    { key: 'profile', label: 'Profile', icon: '👤', href: 'profile.html', mobile: true },
    { key: 'settings', label: 'Settings', icon: '⚙️', href: 'settings.html', mobile: false }
  ],
  client: [
    { key: 'dashboard', label: 'Home', icon: '🏠', href: 'dashboard.html', mobile: true },
    { key: 'post', label: 'Post Gig', icon: '➕', href: 'post-gig.html', mobile: true },
    { key: 'activity', label: 'My Gigs', icon: '📋', href: 'activity.html', mobile: true },
    { key: 'payments', label: 'Payments', icon: '💳', href: 'payments.html', mobile: true },
    { key: 'profile', label: 'Profile', icon: '👤', href: 'profile.html', mobile: true },
    { key: 'settings', label: 'Settings', icon: '⚙️', href: 'settings.html', mobile: false }
  ]
};

/*
  Builds the common page frame (sidebar on desktop, top bar and
  bottom tabs on phones) inside <div id="root">.
  `activeKey` can be a string, or an object like { client: 'activity', freelancer: 'gigs' }.
  Returns the logged in user, or null if we redirected to the login page.
*/
function renderShell(activeKey, title) {
  const user = requireLogin();
  if (!user) return null;

  const active = typeof activeKey === 'object' ? activeKey[user.role] : activeKey;
  const items = NAV[user.role];
  const sideLink = i => `<a href="${i.href}" class="${i.key === active ? 'active' : ''}"><span>${i.icon}</span>${i.label}</a>`;
  const tabLink = i => `<a href="${i.href}" class="${i.key === active ? 'active' : ''}"><span class="ico">${i.icon}</span>${i.label}</a>`;

  $('#root').innerHTML = `
    <div class="shell">
      <aside class="sidebar">
        <a class="brand" href="dashboard.html">Gig<span>Board</span> KE</a>
        <nav>${items.map(sideLink).join('')}</nav>
        <div class="side-user">
          <div class="avatar">${esc(initials(user.name))}</div>
          <div class="grow">
            <div style="font-weight:600">${esc(user.name)}</div>
            <a class="muted small" href="#" onclick="logout();return false;">Log out</a>
          </div>
        </div>
      </aside>
      <div class="main-col">
        <header class="topbar">
          <a class="brand" href="dashboard.html">Gig<span>Board</span></a>
          <div class="topbar-title">${esc(title)}</div>
          <a href="profile.html" class="avatar" title="Profile">${esc(initials(user.name))}</a>
        </header>
        <main id="view" class="view"></main>
      </div>
      <nav class="bottom-nav">${items.filter(i => i.mobile).map(tabLink).join('')}</nav>
    </div>`;
  document.title = title + ' · GigBoard KE';
  return user;
}

function setView(html) { $('#view').innerHTML = html; }

// Role guard for pages that only one role should open
function onlyFor(user, role, message) {
  if (user.role === role) return true;
  setView(emptyState('🔒', message) + `<p style="text-align:center"><a class="btn ghost" href="dashboard.html">Back to home</a></p>`);
  return false;
}
