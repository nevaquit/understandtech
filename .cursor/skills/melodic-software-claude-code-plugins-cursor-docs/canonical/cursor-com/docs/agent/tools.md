---
source_url: https://cursor.com/docs/agent/tools.md
source_type: llms-txt
content_hash: sha256:257dce9aea2d58e16d233202626b36fb726158fdea8c81613fcc307834be43dc
sitemap_url: https://cursor.com/llms.txt
fetch_method: html
---

export const meta = {
title: "Tools",
description: "Explore all tools available to Agent including search, edit, terminal, and MCP capabilities. Configure auto-apply, auto-run, and security guardrails for autonomous operations."
};
# Tools
A list of all tools available to modes within the [Agent](/docs/agent/overview).
To understand how tool calling works under the hood, see our [tool calling fundamentals](/learn/tool-calling).

There is no limit on the number of tool calls Agent can make during a task.
Agent will continue using tools as needed to complete your request.
## Search
Tools used to search your codebase and the web to find relevant information.

Intelligently read the content of a file. Also supports image files (e.g., PNG, JPG, GIF, WebP, SVG) and includes them in the conversation context for analysis by vision-capable models.

Read the structure of a directory without reading file contents.

Perform semantic searches within your [indexed
codebase](/docs/context/codebase-indexing). Finds code by meaning, not just
exact matches.

Search for exact keywords or patterns within files.

Find files by name using fuzzy matching.

Generate search queries and perform web searches.

Retrieve specific [rules](/docs/context/rules) based on type and
description.
## Edit
Tools used to make specific edits to your files and codebase.

Suggest edits to files and [apply](/docs/agent/apply) them automatically.

Delete files autonomously (can be disabled in settings).
## Run
Chat can interact with your terminal.

Execute terminal commands and monitor output.
By default, Cursor uses the first terminal profile available.
To set your preferred terminal profile:
1. Open Command Palette (`Cmd/Ctrl+Shift+P`)
2. Search for "Terminal: Select Default Profile"
3. Choose your desired profile
## MCP
Chat can use configured MCP servers to interact with external services, such as databases or 3rd party APIs.

Toggle available MCP servers. Respects auto-run configuration.
  
Learn more about [Model Context Protocol](/docs/context/mcp) and explore available servers in the [MCP directory](/docs/context/mcp/directory).
## Advanced options

Automatically apply edits without manual confirmation.

Automatically execute terminal commands and accept edits. Useful for running test suites and verifying changes.

Configure allow lists to specify which tools can execute automatically. Allow lists provide better security by explicitly defining permitted operations.

Automatically resolve linter errors and warnings when encountered by Agent.
---
## Sitemap
[Overview of all docs pages](/llms.txt)
