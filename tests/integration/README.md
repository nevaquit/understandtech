# Integration Tests

Lightweight integration smoke (no Moodle core required in CI):

```bash
node tests/integration/worker-health.mjs
```

Runs in `.github/workflows/test.yml` on every push/PR.
