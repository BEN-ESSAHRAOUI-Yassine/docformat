import api from './client'

export const analyzeStyle = async (documentId, profileId) => {
  const { data } = await api.post(`/documents/${documentId}/analyze-style`, { profile_id: profileId })
  return data
}

export const getStyleViolations = async (documentId, severity) => {
  const params = severity ? { severity } : {}
  const { data } = await api.get(`/documents/${documentId}/style-violations`, { params })
  return data
}
