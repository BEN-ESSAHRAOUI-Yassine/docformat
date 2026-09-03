import api from './client'

export const getAbbreviations = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/abbreviations`)
  return data
}

export const getAbbreviationIssues = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/abbreviation-issues`)
  return data
}
