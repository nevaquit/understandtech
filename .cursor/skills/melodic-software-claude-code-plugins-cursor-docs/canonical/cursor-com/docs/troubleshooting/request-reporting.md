---
source_url: https://cursor.com/docs/troubleshooting/request-reporting
source_type: llms-txt
content_hash: sha256:7e04ea761d5d29c54dbd7b203e0aa1e5e75013984d3ecc7ccd92f948596fbcf5
sitemap_url: https://cursor.com/llms.txt
fetch_method: markdown
---

# Getting a Request ID

When the Cursor team is investigating a technical issue, we may ask for a "request ID".

## What is a request ID?

A request ID uniquely identifies each request to Cursor in our internal systems.

Format example: `8f2a5b91-4d3e-47c6-9f12-5e8d94ca7d23`

## How do I find a request ID?

Request IDs are limited when Privacy Mode is enabled. Disable Privacy Mode when reporting issues.

Note: Business plan users have Privacy Mode enabled by default by their organization's admin.

### Getting your current request ID

To report an issue with your current or recent conversation:

With the conversation open in the Chat sidebar, use the context menu (top right) and select `Copy Request ID`.

![Request ID popup](/docs-static/images/requestIDpopup.png)

Send the copied request ID to us via the forum or email as requested.

### Getting a request ID from a previous action

Retrieve historical request IDs using the `Report AI Action` command:

1. Open command palette `⌘⇧P`
2. Type `Report AI Action`
3. Select the `Report AI Action` option

This displays your recent AI actions across Chat, CMD+K, and Apply.

![Request ID list](/docs-static/images/requestIDlist.png)

Select the action by matching its time and feature. Copy the request ID and send it to us.


---

## Sitemap

[Overview of all docs pages](/llms.txt)
