import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export function AccountPage() {
  const { user } = useAuth()
  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Espace client</p><h1>Bonjour, {user.name}</h1></div><div className="account-grid"><Link className="panel" to="/account/orders"><h2>Mes commandes</h2><p>Consultez votre historique et le suivi.</p></Link><Link className="panel" to="/account/addresses"><h2>Mes adresses</h2><p>Gérez vos adresses de livraison et facturation.</p></Link><Link className="panel" to="/wishlist"><h2>Ma wishlist</h2><p>Retrouvez les pièces enregistrées.</p></Link></div></section>
}
