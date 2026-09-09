import { useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { firstError } from '../services/api'

export function AuthPage({ register = false }) {
  const { user, login, register: createAccount } = useAuth()
  const navigate = useNavigate()
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  if (user) return <Navigate to="/account" replace />

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    const data = Object.fromEntries(new FormData(event.currentTarget))
    try {
      register ? await createAccount(data) : await login(data)
      navigate('/account')
    } catch (reason) { setError(firstError(reason)) } finally { setBusy(false) }
  }

  return <section className="form-page"><form className="panel stack" onSubmit={submit}><p className="eyebrow">Compte Seef</p><h1>{register ? 'Créer un compte' : 'Bienvenue'}</h1>{register && <label>Nom<input name="name" autoComplete="name" required /></label>}<label>Email<input name="email" type="email" autoComplete="email" required /></label>{register && <label>Téléphone<input name="phone" autoComplete="tel" /></label>}<label>Mot de passe<input name="password" type="password" autoComplete={register ? 'new-password' : 'current-password'} required /></label>{register && <label>Confirmer le mot de passe<input name="password_confirmation" type="password" autoComplete="new-password" required /></label>}{error && <p className="form-error" role="alert">{error}</p>}<button className="button" disabled={busy}>{busy ? 'Envoi…' : register ? 'Créer mon compte' : 'Se connecter'}</button><p>{register ? 'Déjà client ?' : 'Nouveau chez Seef ?'} <Link className="text-link" to={register ? '/login' : '/register'}>{register ? 'Connexion' : 'Inscription'}</Link></p></form></section>
}
