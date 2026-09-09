import { useContext } from 'react'
import { CartContext } from '../app/cart-context'

export function useCart() {
  return useContext(CartContext)
}
