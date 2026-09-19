import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import { AuthContext } from '../app/auth-context'
import { ProtectedRoute } from './ProtectedRoute'

function renderRoute(auth, { admin = false, initialEntry = '/private' } = {}) {
  return render(
    <AuthContext.Provider value={auth}>
      <MemoryRouter initialEntries={[initialEntry]}>
        <Routes>
          <Route path="/login" element={<p>Login page</p>} />
          <Route path="/" element={<p>Home page</p>} />
          <Route element={<ProtectedRoute admin={admin} />}>
            <Route path="/private" element={<p>Private page</p>} />
          </Route>
        </Routes>
      </MemoryRouter>
    </AuthContext.Provider>,
  )
}

describe('ProtectedRoute', () => {
  it('shows a loading state while the session is unresolved', () => {
    renderRoute({ user: null, loading: true })
    expect(screen.getByRole('status')).toHaveTextContent('Chargement')
  })

  it('redirects anonymous visitors to login', () => {
    renderRoute({ user: null, loading: false })
    expect(screen.getByText('Login page')).toBeInTheDocument()
  })

  it('renders authenticated customer routes', () => {
    renderRoute({ user: { role: 'customer' }, loading: false })
    expect(screen.getByText('Private page')).toBeInTheDocument()
  })

  it('keeps the admin route restricted to administrators', () => {
    renderRoute({ user: { role: 'customer' }, loading: false }, { admin: true })
    expect(screen.getByText('Home page')).toBeInTheDocument()
  })
})
