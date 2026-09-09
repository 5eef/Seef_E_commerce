import { useEffect, useState } from 'react'
import { api, firstError } from '../services/api'

const fields = ['first_name', 'last_name', 'phone', 'address_line_1', 'city', 'postal_code', 'country_code']

export function AddressesPage() {
  const [addresses, setAddresses] = useState([]); const [error, setError] = useState(''); const [busy, setBusy] = useState(false)
  const load = () => api.get('/addresses').then((payload) => setAddresses(payload.data)).catch((reason) => setError(firstError(reason)))
  useEffect(() => { load() }, [])
  async function submit(event) { event.preventDefault(); setBusy(true); setError(''); try { await api.post('/addresses', Object.fromEntries(new FormData(event.currentTarget))); event.currentTarget.reset(); await load() } catch (reason) { setError(firstError(reason)) } finally { setBusy(false) } }
  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Espace client</p><h1>Mes adresses</h1></div><div className="cart-layout"><div className="stack">{addresses.map((address) => <article className="panel" key={address.id}><h2>{address.label || 'Adresse'}</h2><p>{address.first_name} {address.last_name}<br />{address.address_line_1}<br />{address.postal_code} {address.city}, {address.country_code}</p><button className="link-button danger" type="button" onClick={async () => { await api.delete(`/addresses/${address.id}`); setAddresses((current) => current.filter((item) => item.id !== address.id)) }}>Supprimer</button></article>)}</div><form className="panel stack" onSubmit={submit}><h2>Ajouter une adresse</h2><label>Libellé<input name="label" /></label>{fields.map((field) => <label key={field}>{field.replaceAll('_', ' ')}<input name={field} required={!['postal_code'].includes(field)} defaultValue={field === 'country_code' ? 'MA' : ''} /></label>)}{error && <p className="form-error">{error}</p>}<button className="button" disabled={busy}>Enregistrer</button></form></div></section>
}
