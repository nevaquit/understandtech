# block_portfolio

Learner portfolio block — aggregates exam readiness from CertMaster with completed labs and quiz assessments.

## Configuration

Add the block to a dashboard or course page, then set:

| Setting | Purpose |
|---------|---------|
| **Block title** | Optional custom header |
| **Certification** | CertMaster framework for readiness % (required) |

## Data sources

| Section | Source |
|---------|--------|
| Exam readiness | `local_certmaster\api::get_user_readiness()` |
| Completed labs | Successful `mod_ctfflag` submissions |
| Completed assessments | Latest finished `quiz` attempts (one per quiz, max 10) |

The block does not store learner data; it reads existing activity records at render time.

## Dependencies

- `local_certmaster` — certification readiness API

## Tests

```bash
vendor/bin/phpunit moodle-plugins/block_portfolio/tests/api_test.php
```

(Moodle PHPUnit bootstrap required — runs in CI validate stage syntax checks.)
