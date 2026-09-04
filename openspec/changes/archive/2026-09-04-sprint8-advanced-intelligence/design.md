## Context

See proposal.md — Why. Sprint 5–7 built `DocumentIssue` with a `probabilistic` flag and `source`/`review_mode` strings; `IssueCollector::collect()` (transactional delete-and-rebuild) is the single bolt-on point for new detection sources. `ReviewMode` already has `Similarity`/`Ai`/`Grammar` cases and `IssuePanel` already renders those filter tabs — so intelligence findings can surface through the existing review UI with minimal new UI. `Guzzle` 8.1 is installed and Laravel's `Http` facade works. `.env` has a real Groq key/url/model. No provider registry, similarity engine, AI analysis, correction engine, or paraphrase engine exists.

## Goals / Non-Goals

**Goals:**
- Replaceable `AiProvider` abstraction with a real `GroqProvider` and a deterministic `LocalHeuristicProvider` fallback.
- Similarity detection against the owner's local corpus, surfaced as probabilistic issues.
- AI-content analysis (weak assertions / lacking references / citation suggestions) with confidence framing, surfaced as probabilistic issues, with per-document toggle.
- Deterministic correction engine (+ optional provider pass), surfaced as grammar/spelling issues.
- Paraphrase + synonym suggestions (provider-backed), suggestion-only, citation-first.
- New `IssueSource` cases wired into `IssueCollector`; `AnalyzeIntelligenceJob` on the `docformat.nlp` queue.

**Non-Goals:**
- Guaranteed plagiarism detection (similarity is an estimate, per product rules).
- Definitive AI authorship detection.
- Rewriting entire documents automatically.
- Real web/search provider integrations beyond config-gated adapter stubs (outbound document processing must stay opt-in).

## Decisions

**1. Provider abstraction mirrors the `StyleEngine` registry pattern.**
`AiProvider` interface (`analyze`, `paraphrase`, `suggestSynonyms`) + `ProviderManager` registry + `AppServiceProvider` binding. Providers: `GroqProvider` (default, real HTTP), `LocalHeuristicProvider` (deterministic offline fallback). Provider selected per feature from config; `ProviderManager` tries providers in order and falls back on failure. Chosen over hardcoded calls so any provider can be swapped and tests stay deterministic offline. `Http::fake()` is used in tests to pin the real Groq path without network.

**2. New sources flow through `IssueCollector`, not a separate table.**
Add `collectIntelligence()` to `IssueCollector` issuing `DocumentIssue` rows with the new sources; since `collect()` deletes and rebuilds, intelligence issues are regenerated on re-analysis and stay consistent with the rest of the panel. Non-deterministic features (similarity/AI) are labeled `probabilistic=true` and excluded from deterministic quality scores (already the existing behavior of `QualityEngine`).

**3. Similarity is local-first.**
`SimilarityEngine` chunks a document into paragraphs/sections and compares each against the owner's stored `DocumentVersion`s (re-using `Storage::disk('docformat')` and the `normalize`/`similar_text` approach from `DuplicateDetector`). Results are deterministic and never leave the app. External/search providers are config-gated adapters, not exercised by default.

**4. Correction engine is deterministic by default.**
Rule-based checks (capitalization, punctuation, double spaces, spelling/technical term mapping) are pure, testable, and reversible; an optional Groq grammar pass augments them only when enabled. Non-reversible results are always surfaced via the review flow, never auto-applied.

**5. Paraphrasing is suggestion-only.**
`ParaphraseEngine`/`SynonymEngine` return candidate alternatives with confidence; the frontend routes these through the existing accept/reject/edit/ignore issue decision path. Citation-first behavior is enforced: when a corpus match is detected, the paraphrase result also surfaces a "suggest citing" note.

## Risks / Trade-offs

- **External provider variability** → Mitigation: deterministic offline provider for tests; `Http::fake()` for the Groq contract; retry/fallback in `ProviderManager`.
- **Corpus privacy** → Mitigation: local-only similarity by default; external matching is opt-in behind config.
- **Issue churn** → Mitigation: intelligence issues are collapsed per document via the delete-and-rebuild collector; probabilistic issues don't affect deterministic scores.
- **Non-deterministic output** → Mitigation: all AI/similarity findings carry `probabilistic=true` and are clearly framed as estimates in the UI.

## Migration Plan

Additive only. New config keys in `config/services.php` + `.env.example` (Groq). New `IssueSource` enum cases. No table schema changes (reuses `document_issues`). Rollback-safe.
