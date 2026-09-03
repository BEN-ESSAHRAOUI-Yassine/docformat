import api from './client'

export const getBibliography = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/bibliography`)
  return data
}

export const getBibliographyCitations = async (documentId, entryId) => {
  const { data } = await api.get(`/documents/${documentId}/bibliography/${entryId}/citations`)
  return data
}

export const mergeBibliographyEntries = async (documentId, entryId, keepEntries) => {
  const { data } = await api.post(`/documents/${documentId}/bibliography/${entryId}/merge`, {
    keep_entries: keepEntries,
  })
  return data
}
