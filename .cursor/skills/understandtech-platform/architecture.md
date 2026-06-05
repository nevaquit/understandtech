# Architecture Quick Reference

Extracted from `docs/white-paper.md` v2.0. Read the full white paper for diagrams and business context.

## Product

understandtech.app combines:

- **Skool.com** community-first engagement
- **Loom.com** cinematic async video instruction
- **CompTIA CertMaster** structured certification readiness

Built on customized Moodle 4.5 LTS with AI augmentation.

## v2.0 architectural improvements

1. Database isolated from web compute
2. Video and AI workloads on Cloudflare edge
3. Nginx + PHP-FPM as async web engine
4. Lean plugin monorepo with zero-inbound CI/CD (self-hosted runners)
5. LLM calls externalized via Cloudflare AI Gateway Worker

## Topology

```
Learner → Cloudflare Edge (WAF, Stream, Workers)
              ↓
         AI Gateway Worker → Anthropic / OpenAI
              ↓
         Azure Origin VM (Nginx + PHP-FPM + Moodle plugins)
              ↓
         PgBouncer → Azure PostgreSQL
         Redis cache
         Azure Key Vault (secrets)
```

## Custom plugins (this repo)

| Plugin | Type | Purpose |
|--------|------|---------|
| `theme_understandtech` | theme | Boost child, brand UI |
| `local_certmaster` | local | Certification paths |
| `local_aitutor` | local | AI tutoring (via Worker) |
| `local_aigrading` | local | AI-assisted grading |
| `mod_ctfflag` | mod | Hands-on lab flags |
| `block_examreadiness` | block | Exam readiness dashboard |
| `block_portfolio` | block | Learner portfolio |

## Security highlights

- JWT-signed video URLs (60-second expiry)
- AI Gateway rate limiting and audit logging
- No PII in LLM prompts without redaction
- SOC 2 evidence collection via automated tooling

## Brand

- Navy: `#0B1F3A`
- Gold: `#C9A227`
- Teal: `#1A8A7D`
