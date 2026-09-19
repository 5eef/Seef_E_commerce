import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { describe, expect, it, vi } from 'vitest'
import { AuthContext } from '../app/auth-context'
import { CartContext } from '../app/cart-context'
import { Layout } from './Layout'

function renderLayout() {
  const auth = {
    user: { id: 1, name: 'Seef Client', email: 'client@seef.test', role: 'customer' },
    loading: false,
    logout: vi.fn(),
  }
  const cart = {
    cart: {
      items_count: 1,
      subtotal: '120.00',
      items: [{
        id: 5,
        quantity: 1,
        line_total: '120.00',
        product: { name: 'Chemise Seef' },
        variant: { name: 'M' },
      }],
    },
    loading: false,
  }

  render(
    <AuthContext.Provider value={auth}>
      <CartContext.Provider value={cart}>
        <MemoryRouter>
          <Routes>
            <Route element={<Layout />}>
              <Route index element={<p>Accueil test</p>} />
            </Route>
          </Routes>
        </MemoryRouter>
      </CartContext.Provider>
    </AuthContext.Provider>,
  )
}

describe('Layout header panels', () => {
  it('shows authenticated account details and the server cart summary', async () => {
    const user = userEvent.setup()
    renderLayout()

    await user.click(screen.getAllByRole('button', { name: 'Ouvrir mon profil' })[0])
    expect(screen.getByText('client@seef.test')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Mes commandes' })).toHaveAttribute('href', '/account/orders')

    await user.click(screen.getAllByRole('button', { name: /Ouvrir le panier, 1 article$/ })[0])
    expect(screen.getByText('Chemise Seef')).toBeInTheDocument()
    expect(screen.getAllByText('120.00 MAD')).toHaveLength(2)
    expect(screen.getByRole('link', { name: 'Voir le panier' })).toHaveAttribute('href', '/cart')
  })
})
