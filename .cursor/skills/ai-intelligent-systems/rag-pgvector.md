# RAG with pgvector — Course-Isolated Context

## Principles

- **One course per retrieval query** — `courseid` filter mandatory
- **Never index** quiz questions, answers, lab flags, or instructor-only banks
- **Chunk + hash** for idempotent re-index on content update

## Schema sketch (Moodle plugin or shared schema)

```sql
-- Enable once per database (ops/migration, not in Moodle install.xml if managed separately)
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE mdl_aitutor_embeddings (
    id BIGSERIAL PRIMARY KEY,
    courseid BIGINT NOT NULL,
    contextid BIGINT,
    chunk_hash CHAR(64) NOT NULL,
    content_text TEXT NOT NULL,
    source_type VARCHAR(32) NOT NULL,
    embedding vector(1536),
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,
    UNIQUE (courseid, chunk_hash)
);

CREATE INDEX ON mdl_aitutor_embeddings USING hnsw (embedding vector_cosine_ops);
CREATE INDEX ON mdl_aitutor_embeddings (courseid);
```

Adjust `vector(1536)` to match embedding model dimensions.

## Retrieval query (Worker via secure origin API or direct read replica—prefer API)

Moodle exposes an internal web service that returns top-k **redacted** chunks for a course—Worker never gets DB credentials.

Alternative (batch): Worker calls origin `local_aitutor` endpoint with JWT + `courseid`.

## Embedding models

- Use one model for ingest and query (e.g. OpenAI `text-embedding-3-small` or Anthropic-compatible via Gateway)
- Document model id in `prompts.ts` or `types.ts` constant

## KV cache key

```
rag_cache:{courseid}:{sha256(normalized_query)}:{prompt_version}
```

TTL: 60 seconds (playbook).
