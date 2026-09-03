import api from './client'

export const getCitations = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/citations`)
  return data
}

export const getCitationBibliographyEntry = async (documentId, citationId) => {
  const { data } = await api.get(`/documents/${documentId}/citations/${citationId}/bibliography-entry`)
  return data
}

export const validateReferences = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/validate-references`)
  return data
}

export const getReferenceIssues = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/reference-issues`)
  return data
}
