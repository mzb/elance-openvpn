ALTER TABLE users
  ADD COLUMN group_id INTEGER REFERENCES groups(id) ON DELETE SET NULL;
