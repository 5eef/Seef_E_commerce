import { act, renderHook, waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuth } from '../hooks/useAuth'
import { api, csrf } from '../services/api'
import { AuthProvider } from './AuthContext'

vi.mock('../services/api', () => ({
  api: { get: vi.fn(), post: vi.fn() },
  csrf: vi.fn(),
}))

function wrapper({ children }) {
  return <AuthProvider>{children}</AuthProvider>
}

describe('AuthProvider', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    api.get.mockResolvedValue({ user: null })
  })

  it('loads the current session', async () => {
    const user = { id: 1, name: 'Seef', role: 'customer' }
    api.get.mockResolvedValue({ user })
    const { result } = renderHook(() => useAuth(), { wrapper })

    await waitFor(() => expect(result.current.loading).toBe(false))
    expect(api.get).toHaveBeenCalledWith('/auth/session')
    expect(result.current.user).toEqual(user)
  })

  it('initializes CSRF before login and exposes the authenticated user', async () => {
    const user = { id: 2, name: 'Admin', role: 'admin' }
    api.post.mockResolvedValue({ user })
    const { result } = renderHook(() => useAuth(), { wrapper })
    await waitFor(() => expect(result.current.loading).toBe(false))

    await act(() => result.current.login({ email: 'admin@example.test', password: 'secret' }))

    expect(csrf).toHaveBeenCalledOnce()
    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      email: 'admin@example.test',
      password: 'secret',
    })
    expect(result.current.user).toEqual(user)
  })

  it('clears local auth state after logout', async () => {
    api.get.mockResolvedValue({ user: { id: 1, role: 'customer' } })
    api.post.mockResolvedValue({})
    const { result } = renderHook(() => useAuth(), { wrapper })
    await waitFor(() => expect(result.current.user).not.toBeNull())

    await act(() => result.current.logout())

    expect(api.post).toHaveBeenCalledWith('/auth/logout')
    expect(result.current.user).toBeNull()
  })
})
