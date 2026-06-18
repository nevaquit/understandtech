# Agent instructions

This repository follows the understandtech.app v2.0 platform architecture.

- Read `.cursorrules` before making changes
- Read `docs/white-paper.md` for architecture
- Read `docs/playbook.md` for build phases and Cursor prompts
- Use project skills `/understandtech-platform`, `/understandtech-cert-research-content`, and `/understandtech-cert-content` in Agent chat — **for net-new certification content, always run `/understandtech-cert-research-content` before `/understandtech-cert-content`** (gap memo + citations + artifact plan before generating lessons, GIFT, practice exams, or labs)
- Use `/lms-workflow` (or `/lms-enterprise-ai-master-skill`) for LMS and AI orchestration tasks
- For general engineering workflows (spec, TDD, review, ship), use `/addyosmani-agent-skills` ([addyosmani/agent-skills](https://github.com/addyosmani/agent-skills))
- For vibe-coding patterns, harness engineering, and tool catalogs, use `/taskade-awesome-vibe-coding` ([taskade/awesome-vibe-coding](https://github.com/taskade/awesome-vibe-coding))
- For brainstorming → plan → TDD → subagent execution workflows, use `/obra-superpowers` ([obra/superpowers](https://github.com/obra/superpowers))
- For Karpathy-inspired coding discipline (simplicity, surgical diffs, verifiable goals), use `/forrestchang-andrej-karpathy-skills` — also always applied via `.cursor/rules/karpathy-guidelines.mdc` ([forrestchang/andrej-karpathy-skills](https://github.com/forrestchang/andrej-karpathy-skills))
- To discover and install external Agent Skills (1400+ curated listings), use `/voltagent-awesome-agent-skills` ([VoltAgent/awesome-agent-skills](https://github.com/VoltAgent/awesome-agent-skills))
- For visual design QA (audit + fix loop on live UI), use `/garrytan-gstack-design-review`; for plan-time audits use `/garrytan-gstack-plan-design-review` ([garrytan/gstack](https://github.com/garrytan/gstack/tree/main/design-review))

Moodle core is never committed. Secrets never appear in source.
