-- Track previous version for diff highlighting
ALTER TABLE laws ADD COLUMN prev_html TEXT;
ALTER TABLE laws ADD COLUMN prev_updated_at INTEGER;

-- Public-submitted proposals for new/changed laws
CREATE TABLE IF NOT EXISTS law_proposals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT,                     -- target law slug (NULL = new law)
  kind TEXT NOT NULL,            -- 'edit' | 'new' | 'delete'
  proposer_name TEXT NOT NULL,
  proposer_contact TEXT,         -- discord/email/etc.
  current_html TEXT,             -- snapshot of law at proposal time (for edits)
  proposed_title TEXT,
  proposed_category TEXT,
  proposed_html TEXT,
  summary TEXT,                  -- "Why this change?"
  status TEXT NOT NULL DEFAULT 'pending',  -- pending|approved|rejected
  created_at INTEGER NOT NULL,
  reviewed_at INTEGER,
  reviewed_by TEXT,
  review_note TEXT
);

CREATE INDEX IF NOT EXISTS idx_proposals_status ON law_proposals(status);
CREATE INDEX IF NOT EXISTS idx_proposals_slug ON law_proposals(slug);
CREATE INDEX IF NOT EXISTS idx_proposals_created ON law_proposals(created_at DESC);
