ALTER TABLE users
  ADD COLUMN default_policy TINYINT NOT NULL DEFAULT 0;

ALTER TABLE groups
  ADD COLUMN default_policy TINYINT NOT NULL DEFAULT 0;