import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'
import { api, firstError } from '../services/api'

export function CheckoutPage() {
  const { user } = useAuth(); const { cart, refresh } = useCart(); const navigate = useNavigate(); const [error, setError] = useState(''); const [busy, setBusy] = useState(false)
  if (cart && !cart.items?.length) return <Navigate to="/cart" replace />
  async function submit(event) {
    event.preventDefault(); setBusy(true); setError(''); const values = Object.fromEntries(new FormData(event.currentTarget))
    const payload = { email: values.email, phone: values.phone, payment_method: values.payment_method, coupon_code: values.coupon_code || null, billing_same_as_shipping: true, shipping_address: { first_name: values.first_name, last_name: values.last_name, phone: values.phone, address_line_1: values.address_line_1, address_line_2: values.address_line_2 || null, city: values.city, region: values.region || null, postal_code: values.postal_code || null, country_code: values.country_code } }
    try { const response = await api.post('/checkout', payload); await refresh(); navigate(`/checkout/success?order=${response.data.order_number}`) } catch (reason) { setError(firstError(reason)); setBusy(false) }
  }
  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Finaliser</p><h1>Checkout</h1></div><div className="cart-layout"><form className="panel stack" onSubmit={submit}><h2>Livraison</h2><div className="form-grid"><label>Prénom<input name="first_name" defaultValue={user?.name?.split(' ')[0] || ''} required /></label><label>Nom<input name="last_name" defaultValue={user?.name?.split(' ').slice(1).join(' ')} required /></label></div><label>Email<input name="email" type="email" defaultValue={user?.email || ''} required /></label><label>Téléphone<input name="phone" defaultValue={user?.phone || ''} required /></label><label>Adresse<input name="address_line_1" required /></label><label>Complément<input name="address_line_2" /></label><div className="form-grid"><label>Ville<input name="city" required /></label><label>Région<input name="region" /></label><label>Code postal<input name="postal_code" /></label><label>Pays<input name="country_code" defaultValue="MA" maxLength="2" required /></label></div><label>Coupon<input name="coupon_code" /></label><label>Paiement<select name="payment_method" defaultValue="cod"><option value="cod">Paiement à la livraison</option><option value="card">Carte — validation manuelle</option><option value="bank_transfer">Virement bancaire</option></select></label>{error && <p className="form-error" role="alert">{error}</p>}<button className="button" disabled={busy}>{busy ? 'Traitement…' : 'Confirmer la commande'}</button></form><aside className="panel summary"><h2>Résumé</h2><p><span>{cart?.total_quantity} article(s)</span><strong>{cart?.subtotal} MAD</strong></p><p className="muted">Tous les montants sont recalculés par le serveur.</p></aside></div></section>
}
