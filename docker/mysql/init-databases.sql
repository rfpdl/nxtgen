-- The test and e2e databases, created on a fresh volume so `make test` and `make e2e` work without
-- a setup step.
CREATE DATABASE IF NOT EXISTS nxtgen_test  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE IF NOT EXISTS nxtgen_e2e   CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- The PATTERN grant, and it is the line this file exists for.
--
-- `artisan test --parallel` gives every worker its OWN database — nxtgen_test_test_1 through
-- nxtgen_test_test_12 on a twelve-core machine — created and dropped on each run. MySQL privileges
-- are per database, so `GRANT ALL ON nxtgen_test.*` says nothing about nxtgen_test_test_1. Without
-- this, every worker fails at setup and you get hundreds of copies of "Access denied for user
-- 'nxtgen'@'%' to database 'nxtgen_test_test_1'", which names the symptom and not the cause. Palo
-- lost an afternoon to exactly this.
--
-- The backslash before the underscore matters: `_` is a single-character WILDCARD in a GRANT
-- pattern, so an unescaped `nxtgen_test%` would also match databases nobody intended.
GRANT ALL PRIVILEGES ON `nxtgen\_test%`.* TO 'nxtgen'@'%';
GRANT ALL PRIVILEGES ON `nxtgen\_e2e%`.*  TO 'nxtgen'@'%';
FLUSH PRIVILEGES;
