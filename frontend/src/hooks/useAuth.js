import { useContext } from 'react'
import { AuthContext } from '../app/auth-context'

export function useAuth() {
  return useContext(AuthContext)
}
