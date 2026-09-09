import { useEffect, useMemo, useState } from 'react'
import { api, csrf } from '../services/api'
import { AuthContext } from './auth-context'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    api.get('/auth/me')
      .then((payload) => active && setUser(payload.user))
      .catch((error) => {
        if (active && error.status !== 401) console.error(error)
      })
      .finally(() => active && setLoading(false))

    return () => { active = false }
  }, [])

  const value = useMemo(() => ({
    user,
    loading,
    async login(credentials) {
      await csrf()
      const payload = await api.post('/auth/login', credentials)
      setUser(payload.user)
      return payload.user
    },
    async register(data) {
      await csrf()
      const payload = await api.post('/auth/register', data)
      setUser(payload.user)
      return payload.user
    },
    async logout() {
      await api.post('/auth/logout')
      setUser(null)
    },
  }), [loading, user])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
