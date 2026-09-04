## 1. Provider Adapter Architecture (Plane Task 67)

- [ ] 1.1 Add Groq config keys to `config/services.php` and `.env.example` (AI_DEFAULT, GROQ_API_KEY, GROQ_API_URL, GROQ_MODEL). Verify config readable.
- [ ] 1.2 Create `AiProvider` interface with `analyze(text, options)`, `paraphrase(text, options)`, `suggestSynonyms(word, context)` methods. Verify interface exists.
- [ ] 1.3 Create `GroqProvider` implementing the interface using Laravel `Http` facade (key/url/model from config). Verify it sends a request to the configured endpoint.
- [ ] 1.4 Create `LocalHeuristicProvider` implementing the interface deterministically offline. Verify deterministic results with no network.
- [ ] 1.5 Create `ProviderManager` registry resolving providers by name and falling back on failure. Verify selection + fallback.
- [ ] 1.6 Bind the provider manager and default providers in `AppServiceProvider`. Verify resolution via the container.

## 2. Similarity Detection (Plane Task 63)

- [ ] 2.1 Create `SimilarityEngine` that chunks a document into comparable units and compares against the owner's stored corpus. Verify a corpus match is found.
- [ ] 2.2 Return similarity results with percentage, matching sections, source, confidence, and match type. Verify result shape.
- [ ] 2.3 Surface similarity findings as probabilistic `DocumentIssue` rows (source `similarity`, review mode `similarity`). Verify issue created and marked probabilistic.
- [ ] 2.4 Ensure local-only comparison by default (no outbound calls). Verify privacy.

## 3. AI-Content Analysis (Plane Task 64)

- [ ] 3.1 Create `AiContentService` using the AI provider to produce weak-assertion / missing-reference / citation-suggestion findings. Verify findings produced with confidence framing.
- [ ] 3.2 Surface AI findings as probabilistic `DocumentIssue` rows (source `ai`, review mode `ai`). Verify issue created.
- [ ] 3.3 Add per-document AI activation toggle (default off). Verify disabled documents are not analyzed.

## 4. Correction Engine (Plane Task 65)

- [ ] 4.1 Create `CorrectionEngine` with deterministic rules (capitalization, punctuation, double spaces, spelling/terminology). Verify each rule detects its case.
- [ ] 4.2 Output corrections with original/suggested/reason/confidence/reversibility. Verify shape and non-reversible flag.
- [ ] 4.3 Surface corrections as probabilistic `DocumentIssue` rows (source `grammar`/`spelling`, review mode `grammar`). Verify issue created.
- [ ] 4.4 Add an optional provider-assisted grammar pass (config/feature-gated). Verify passthrough when enabled.

## 5. Paraphrasing & Synonyms (Plane Task 66)

- [ ] 5.1 Create `ParaphraseEngine` (provider-backed) producing alternative wording with confidence. Verify a suggestion is returned.
- [ ] 5.2 Create `SynonymEngine` producing synonyms that preserve technical meaning. Verify synonyms returned.
- [ ] 5.3 Ensure suggestions are never auto-applied and route to the review flow. Verify suggestion-only behavior.
- [ ] 5.4 Enforce citation-first: when a corpus match is detected, surface a "suggest citing" note. Verify note surfaced.

## 6. Issue Pipeline & Job Wiring

- [ ] 6.1 Extend `IssueSource` enum with `Similarity`, `Ai`, `Grammar`, `Spelling`, `Paraphrase` cases and map `reviewMode()`/`label()`. Verify enum compiles and maps correctly.
- [ ] 6.2 Add `collectIntelligence()` to `IssueCollector` and wire it into `collect()`. Verify intelligence issues are produced on collection.
- [ ] 6.3 Create `AnalyzeIntelligenceJob` on the `docformat.nlp` queue running similarity, AI, and correction with the per-document AI toggle. Verify job dispatches the right work.
- [ ] 6.4 Add endpoints: `POST /documents/{document}/analyze-intelligence`, `GET /documents/{document}/similarity`, `GET /documents/{document}/ai-analysis`, `POST /documents/{document}/corrections/run`, `POST /documents/{document}/paraphrase/suggest`, `POST /documents/{document}/synonyms/suggest`, `POST /documents/{document}/ai/toggle`. Verify each route + ownership 403.
- [ ] 6.5 Wire `QualityEngine` so probabilistic intelligence issues do not lower deterministic scores. Verify unaffected category score.

## 7. Frontend

- [ ] 7.1 Create `frontend/src/api/intelligence.js` client (analyzeIntelligence, getSimilarity, getAiAnalysis, runCorrections, suggestParaphrase, suggestSynonyms, toggleAi). Verify client endpoints.
- [ ] 7.2 Ensure the issue panel filters render intelligence sources (`similarity`, `ai`, `grammar`) with estimate framing. Verify tabs render.
- [ ] 7.3 Add an AI/similarity status surface + external-processing consent notice and AI activation toggle to the workspace. Verify toggle + notice render.
- [ ] 7.4 Build frontend and confirm no build errors. Verify clean build.

## 8. Tests & Verification

- [ ] 8.1 Write unit tests for Groq provider (with Http::fake) and LocalHeuristicProvider determinism. Verify all pass.
- [ ] 8.2 Write tests for ProviderManager selection + fallback. Verify all pass.
- [ ] 8.3 Write feature tests for similarity engine (corpus match, no match, privacy). Verify all pass.
- [ ] 8.4 Write tests for AI-content analysis and correction engine (rule cases, confidence, non-reversible). Verify all pass.
- [ ] 8.5 Write tests for paraphrase/synonym suggestions and citation-first note. Verify all pass.
- [ ] 8.6 Write feature tests for intelligence endpoints + ownership + AI toggle. Verify all pass.
- [ ] 8.7 Run `vendor/bin/pint --dirty` and `php artisan test --compact`. Verify clean output with no regressions.

## 9. OpenSpec Archive & Plane Sync

- [ ] 9.1 Sync delta specs to main specs (provider-adapters, similarity-detection, ai-content-analysis, correction-engine, paraphrasing new; document-issues modified) and archive `sprint8-advanced-intelligence`. Verify archived.
- [ ] 9.2 Update Plane: move Sprint 8 tasks 63-67 through to Done with evidence comments; note the change split (63-67 = advanced-intelligence, 68 = security) on the epic for traceability. Verify tasks and epic reflected.
