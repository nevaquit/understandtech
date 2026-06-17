# Source

- **Repository:** https://github.com/VoltAgent/awesome-agent-skills
- **License:** MIT (see LICENSE)
- **Installed:** 2026-06-15
- **Contents:** Curated catalog (1400+ skills) vendored as `reference/README.md`; router guidance in `SKILL.md`
- **Browse online:** https://officialskills.sh

To refresh the catalog:

```bash
git clone --depth 1 https://github.com/VoltAgent/awesome-agent-skills.git tmp/awesome-agent-skills
cp tmp/awesome-agent-skills/README.md .cursor/skills/voltagent-awesome-agent-skills/reference/README.md
rm -rf tmp/awesome-agent-skills
```

Individual skills install from their upstream repos into `.cursor/skills/` (project) or `~/.cursor/skills/` (global). Review source before use — see Security Notice in the catalog.
