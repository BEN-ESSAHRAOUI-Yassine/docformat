import api from './client'

export const login = async (email, password) => {
  const { data } = await api.post('/login', { email, password })
  return data
}

export const register = async ({ name, email, password, password_confirmation }) => {
  const { data } = await api.post('/register', { name, email, password, password_confirmation })
  return data
}

export const logout = async () => {
  await api.post('/logout')
}

export const forgotPassword = async (email) => {
  await api.post('/forgot-password', { email })
}

export const getUser = async () => {
  const { data } = await api.get('/user')
  return data
}
