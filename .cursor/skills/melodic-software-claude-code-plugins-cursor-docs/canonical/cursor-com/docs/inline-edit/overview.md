---
source_url: https://cursor.com/docs/inline-edit/overview
source_type: llms-txt
content_hash: sha256:dd7f21bad7f7f01a5af1df72a91a9cec3807418a6b6f3e03e4a88a849526be58
sitemap_url: https://cursor.com/llms.txt
fetch_method: markdown
---

# Inline Edit

Inline Edit lets you edit code or ask questions directly in your editor with Cmd+K, which opens an input field where your selected code and instructions create your request.

![Inline Edit empty state](/docs-static/images/inline-edit/empty.png)

## Modes

### Edit Selection

With code selected, Cmd+K edits that specific code based on your instructions.

![Inline Edit selection](/docs-static/images/inline-edit/selection.png)

Without selection, Cursor generates new code at your cursor position. The AI includes relevant surrounding code for context. For example, triggering on a function name includes the entire function.

### Quick Question

Press Opt+Return in the inline editor to ask questions about selected code.

After getting an answer, type "do it" or similar wording to convert the suggestion into code. This lets you explore ideas before implementing.

![Inline Edit quick question](/docs-static/images/inline-edit/qq.png)

### Send to Chat

For multi-file edits or advanced features, use Cmd+L to send selected code to [Chat](https://cursor.com/docs/agent/modes.md#agent). This provides multi-file editing, detailed explanations, and advanced AI capabilities.

[Media](/docs-static/images/inline-edit/send-to-chat.mp4)

## Follow-up instructions

After each edit, refine results by adding instructions and pressing Return. The AI updates changes based on your feedback.

## Default context

Inline Edit includes default context to improve code generation beyond any [@ mentions](https://cursor.com/docs/context/mentions/files-and-folders.md) you add.

This includes related files, recently viewed code, and relevant information. Cursor prioritizes the most relevant context for better results.


---

## Sitemap

[Overview of all docs pages](/llms.txt)
