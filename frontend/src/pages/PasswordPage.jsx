import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { api, firstError } from '../services/api'

export function PasswordPage({ reset = false }) {
  const [params] = useSearchParams(); const [message, setMessage] = useState(''); const [error, setError] = useState(''); const [busy, setBusy] = useState(false)
  async function submit(event) { event.preventDefault(); setBusy(true); setError(''); const data = Object.fromEntries(new FormData(event.currentTarget)); if (reset) data.token = params.get('token') || data.token; try { const response = await api.post(reset ? '/auth/reset-password' : '/auth/forgot-password', data); setMessage(response.message || 'Demande enregistrée.') } catch (reason) { setError(firstError(reason)) } finally { setBusy(false) } }
  return <section className="form-page"><form className="panel stack" onSubmit={submit}><p className="eyebrow">Sécurité</p><h1>{reset ? 'Nouveau mot de passe' : 'Mot de passe oublié'}</h1><label>Email<input name="email" type="email" defaultValue={params.get('email') || ''} required /></label>{reset && <><label>Jeton<input name="token" defaultValue={params.get('token') || ''} required /></label><label>Nouveau mot de passe<input name="password" type="password" required /></label><label>Confirmer<input name="password_confirmation" type="password" required /></label></>}{message && <p className="success-text">{message}</p>}{error && <p className="form-error">{error}</p>}<button className="button" disabled={busy}>Envoyer</button></form></section>
}
