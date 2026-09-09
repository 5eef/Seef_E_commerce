import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function ProtectedRoute({ admin = false }) {
  const { user, loading } = useAuth()
  if (loading) return <div className="state" role="status">Chargement…</div>
  if (!user) return <Navigate to="/login" replace />
  if (admin && user.role !== 'admin') return <Navigate to="/" replace />
  return <Outlet />
}
