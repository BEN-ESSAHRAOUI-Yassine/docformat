import api from './client'

export const analyzeIntelligence = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/analyze-intelligence`)
  return data
}

export const getSimilarity = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/similarity`)
  return data
}

export const getAiAnalysis = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/ai-analysis`)
  return data
}

export const runCorrections = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/corrections/run`)
  return data
}

export const suggestParaphrase = async (documentId, text) => {
  const { data } = await api.post(`/documents/${documentId}/paraphrase/suggest`, { text })
  return data
}

export const suggestSynonyms = async (documentId, word) => {
  const { data } = await api.post(`/documents/${documentId}/synonyms/suggest`, { word })
  return data
}

export const toggleAi = async (documentId, enabled) => {
  const { data } = await api.post(`/documents/${documentId}/ai/toggle`, { enabled })
  return data
}
