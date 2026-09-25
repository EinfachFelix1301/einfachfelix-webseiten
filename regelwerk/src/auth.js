// PBKDF2 password hashing + session helpers (Web Crypto)

const ITER = 100_000;
const HASH_LEN = 32;

function bufToB64(buf) {
  const bytes = new Uint8Array(buf);
  let bin = "";
  for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
  return btoa(bin);
}
function b64ToBuf(b64) {
  const bin = atob(b64);
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return bytes.buffer;
}

export async function hashPassword(password, saltB64) {
  const salt = saltB64
    ? new Uint8Array(b64ToBuf(saltB64))
    : crypto.getRandomValues(new Uint8Array(16));
  const key = await crypto.subtle.importKey(
    "raw",
    new TextEncoder().encode(password),
    "PBKDF2",
    false,
    ["deriveBits"]
  );
  const bits = await crypto.subtle.deriveBits(
    { name: "PBKDF2", salt, iterations: ITER, hash: "SHA-256" },
    key,
    HASH_LEN * 8
  );
  return { hash: bufToB64(bits), salt: bufToB64(salt.buffer) };
}

export async function verifyPassword(password, hash, salt) {
  const result = await hashPassword(password, salt);
  // constant-time compare
  if (result.hash.length !== hash.length) return false;
  let diff = 0;
  for (let i = 0; i < hash.length; i++) {
    diff |= result.hash.charCodeAt(i) ^ hash.charCodeAt(i);
  }
  return diff === 0;
}

export function newSessionToken() {
  const b = crypto.getRandomValues(new Uint8Array(32));
  return bufToB64(b.buffer).replace(/[+/=]/g, (c) =>
    c === "+" ? "-" : c === "/" ? "_" : ""
  );
}

const SESSION_COOKIE = "doj_session";
const SESSION_TTL_DAYS = 14;

export function sessionCookie(token, host) {
  const maxAge = SESSION_TTL_DAYS * 86400;
  return `${SESSION_COOKIE}=${token}; Path=/; Max-Age=${maxAge}; HttpOnly; Secure; SameSite=Lax`;
}

export function clearCookie() {
  return `${SESSION_COOKIE}=; Path=/; Max-Age=0; HttpOnly; Secure; SameSite=Lax`;
}

export function readSessionToken(req) {
  const cookie = req.headers.get("cookie") || "";
  const m = cookie.match(/(?:^|;\s*)doj_session=([^;]+)/);
  return m ? m[1] : null;
}

export async function getUser(env, req) {
  const token = readSessionToken(req);
  if (!token) return null;
  const row = await env.DB.prepare(
    `SELECT u.id, u.username, u.role, u.display_name AS displayName, s.expires_at
     FROM sessions s JOIN users u ON u.id = s.user_id
     WHERE s.token = ?`
  )
    .bind(token)
    .first();
  if (!row) return null;
  if (row.expires_at < Date.now()) {
    await env.DB.prepare(`DELETE FROM sessions WHERE token = ?`).bind(token).run();
    return null;
  }
  return { id: row.id, username: row.username, role: row.role, displayName: row.displayName || null };
}

export async function createSession(env, userId) {
  const token = newSessionToken();
  const now = Date.now();
  const exp = now + SESSION_TTL_DAYS * 86400_000;
  await env.DB.prepare(
    `INSERT INTO sessions (token, user_id, expires_at, created_at) VALUES (?, ?, ?, ?)`
  )
    .bind(token, userId, exp, now)
    .run();
  return token;
}

export async function ensureBootstrapAdmin(env) {
  // Create initial admin if no users exist. Password comes from the secret
  // BOOTSTRAP_ADMIN_PASSWORD (wrangler secret put) - without it nothing is created.
  const password = env.BOOTSTRAP_ADMIN_PASSWORD;
  if (!password) return null;
  const row = await env.DB.prepare(`SELECT COUNT(*) AS n FROM users`).first();
  if (row && row.n > 0) return null;
  const { hash, salt } = await hashPassword(password);
  await env.DB.prepare(
    `INSERT INTO users (username, password_hash, password_salt, role, created_at) VALUES (?, ?, ?, 'admin', ?)`
  )
    .bind("admin", hash, salt, Date.now())
    .run();
  return { username: "admin" };
}
