---
source_url: https://cursor.com/docs/agent/chat/checkpoints.md
source_type: llms-txt
content_hash: sha256:ebc2004c483ed5eafe87850e08b095023c5019cf45293655f7d1a31b986c9d49
sitemap_url: https://cursor.com/llms.txt
fetch_method: html
---

export const meta = {
title: "Checkpoints",
description: "Save and restore chat checkpoints for version control."
};
# Checkpoints
Checkpoints are automatic snapshots of Agent's changes to your codebase. They let you undo Agent modifications if needed.
[](/docs-static/images/chat/restore-checkpoint.mp4)
## Restoring checkpoints
Two ways to restore:
1. \*\*From input box\*\*: Click `Restore Checkpoint` button on previous requests
2. \*\*From message\*\*: Click the + button when hovering over a message
Checkpoints are not version control. Use Git for permanent history.
## How they work
- Stored locally, separate from Git
- Track only Agent changes (not manual edits)
- Cleaned up automatically
Manual edits aren't tracked. Only use checkpoints for Agent changes.
## FAQ

No. They're separate from Git history.
{" "}
For the current session and recent history. Automatically cleaned up.

No. They're created automatically by Cursor.
{" "}
---
## Sitemap
[Overview of all docs pages](/llms.txt)
