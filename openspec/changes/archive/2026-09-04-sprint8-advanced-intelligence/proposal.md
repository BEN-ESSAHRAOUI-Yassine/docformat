## Why

The platform now detects structure, references, and quality issues, but cannot reason about the document's content. Advanced intelligence — similarity/plagiarism detection, AI-content analysis, automatic corrections, and paraphrasing/synonym suggestions — differentiates the product. These are probabilistic features and must be implemented behind replaceable provider adapters and always presented as estimates, never as fact. A real external provider (Groq) is configured; a deterministic offline provider backs it for testability and graceful fallback.

## What Changes

- New provider adapter architecture: `AiProvider` interface + `ProviderManager` registry + container bindings, with a real `GroqProvider` (OpenAI-compatible HTTP via Laravel `Http` facade using `.env` key/url/model) as default and a `LocalHeuristicProvider` (deterministic, offline) as fallback. Provider selection per feature, retry/fallback, config in `config/services.php`.
- New similarity detection engine: chunk the document and compare against the owner's stored corpus; results as probabilistic issues (source `similarity`, review mode `similarity`) with overall %, matching sections, source, confidence, match type. External/search matching behind an adapter (config-gated).
- New AI-content analysis: detect weakly-supported assertions, sections lacking references, and citation-context suggestions; every result carries a confidence label and non-definitive framing. Findings as probabilistic issues (source `ai`). Per-document AI activation toggle.
- New automatic correction engine: deterministic rule-based corrections (capitalization, punctuation, double spaces, spelling/technical terms) plus an optional provider grammar pass; each correction has original/suggested/reason/confidence/reversible; non-reversible corrections never auto-applied. Findings as issues (source `grammar`/`spelling`).
- New paraphrasing + synonym engine behind the provider adapter; suggestions only (accept/reject/edit/ignore via the existing review flow); never designed to conceal plagiarism — suggests citation when a source is detected.
- Cross-cutting: new `IssueSource` cases (`similarity`, `ai`, `grammar`, `spelling`, `paraphrase`) wired into `IssueCollector`; `AnalyzeIntelligenceJob` on the `docformat.nlp` queue; frontend surfaces findings in the existing issue panel filters with consent/estimate framing.

## Capabilities

### New Capabilities
- `provider-adapters`: Provider interface, registry, bindings, real Groq adapter, offline fallback, retry/fallback, and per-feature selection.
- `similarity-detection`: Local-corpus chunked comparison, external matching behind an adapter, and probabilistic similarity issue results.
- `ai-content-analysis`: Probabilistic AI-content analysis with confidence framing and per-document activation.
- `correction-engine`: Deterministic + provider-assisted corrections with reasons, confidence, and reversibility.
- `paraphrasing`: Provider-backed paraphrase and synonym suggestions, never auto-applied, citation-first.

### Modified Capabilities
- `document-issues`: New `similarity`, `ai`, `grammar`, `spelling`, `paraphrase` sources added to the normalized issue pipeline.

## Impact

- New config in `config/services.php` and `.env.example` (Groq key/url/model; `AI_DEFAULT`).
- New services: `ProviderManager`, `GroqProvider`, `LocalHeuristicProvider`, `SimilarityEngine`, `AiContentService`, `CorrectionEngine`, `ParaphraseEngine`, `SynonymEngine`.
- New job: `AnalyzeIntelligenceJob` (on `docformat.nlp`).
- New endpoints (auth:sanctum): `POST /documents/{document}/analyze-intelligence`, `GET /documents/{document}/similarity`, `GET /documents/{document}/ai-analysis`, `POST /documents/{document}/corrections/run`, `POST /documents/{document}/paraphrase/suggest`, `POST /documents/{document}/synonyms/suggest`, and an AI-activation toggle.
- New `IssueSource` cases + `reviewMode()`/`label()` mappings; `IssueCollector::collectIntelligence()`.
- Frontend: intelligence findings in the issue panel, an AI/similarity status surface, external-processing consent notice, `intelligence.js` client.
- No new composer dependency (Guzzle already installed via Laravel framework; Groq accessed over `Http`).
