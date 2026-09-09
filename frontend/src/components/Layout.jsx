import { useEffect, useRef, useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import logo from '../assets/seef_logo_ecomerce.png'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'
import { useTheme } from '../hooks/useTheme'

function Icon({ name }) {
  const paths = {
    account: <><circle cx="12" cy="8" r="3.25" /><path d="M5.5 19c.7-3.3 2.9-5 6.5-5s5.8 1.7 6.5 5" /></>,
    cart: <><path d="M3 4h2l1.6 9.1a2 2 0 0 0 2 1.7h7.8a2 2 0 0 0 1.9-1.4L20 7H6" /><circle cx="9" cy="19" r="1" /><circle cx="17" cy="19" r="1" /></>,
    close: <path d="m6 6 12 12M18 6 6 18" />,
    heart: <path d="M20.8 5.8c0 5.3-8.8 11.2-8.8 11.2S3.2 11.1 3.2 5.8A4.3 4.3 0 0 1 12 4.5a4.3 4.3 0 0 1 8.8 1.3Z" />,
    menu: <path d="M4 7h16M4 12h16M4 17h16" />,
    moon: <path d="M20 15.2A8 8 0 0 1 8.8 4a8.2 8.2 0 1 0 11.2 11.2Z" />,
    search: <><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4 4" /></>,
    sun: <><circle cx="12" cy="12" r="3.5" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></>,
  }

  return <svg className="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">{paths[name]}</svg>
}

function Logo({ compact = false }) {
  return <span className={compact ? 'logo-lockup compact' : 'logo-lockup'}><img src={logo} alt="" /><span>Seef</span></span>
}

export function Layout() {
  const { user, logout } = useAuth()
  const { cart } = useCart()
  const { theme, toggleTheme } = useTheme()
  const [menuOpen, setMenuOpen] = useState(false)
  const closeButton = useRef(null)
  const navigate = useNavigate()
  const cartCount = cart?.items_count || 0

  useEffect(() => {
    if (!menuOpen) return undefined
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    closeButton.current?.focus()

    function onKeyDown(event) {
      if (event.key === 'Escape') setMenuOpen(false)
    }

    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [menuOpen])

  async function signOut() {
    await logout()
    setMenuOpen(false)
    navigate('/')
  }

  const themeLabel = theme === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre'

  return (
    <div className="site-shell">
      <header className="site-header">
        <NavLink className="brand-link" to="/" aria-label="Seef E-commerce — Accueil"><Logo compact /></NavLink>
        <nav className="desktop-nav" aria-label="Navigation principale">
          <NavLink to="/">Accueil</NavLink>
          <NavLink to="/shop">Boutique</NavLink>
          {user?.role === 'admin' && <NavLink to="/admin">Admin</NavLink>}
        </nav>
        <div className="header-actions desktop-actions">
          <NavLink className="icon-button" to="/shop" aria-label="Rechercher"><Icon name="search" /></NavLink>
          <NavLink className="icon-button" to={user ? '/account' : '/login'} aria-label={user ? 'Mon compte' : 'Se connecter'}><Icon name="account" /></NavLink>
          <NavLink className="icon-button" to="/wishlist" aria-label="Ma wishlist"><Icon name="heart" /></NavLink>
          <button className="icon-button" type="button" onClick={toggleTheme} aria-label={themeLabel} title={themeLabel}><Icon name={theme === 'dark' ? 'sun' : 'moon'} /></button>
          <NavLink className="icon-button cart-action" to="/cart" aria-label={`Panier, ${cartCount} article${cartCount > 1 ? 's' : ''}`}><Icon name="cart" />{cartCount > 0 && <span className="count">{cartCount}</span>}</NavLink>
          {user && <button className="logout-button" type="button" onClick={signOut}>Déconnexion</button>}
        </div>
        <div className="mobile-actions">
          <NavLink className="icon-button cart-action" to="/cart" aria-label={`Panier, ${cartCount} article${cartCount > 1 ? 's' : ''}`}><Icon name="cart" />{cartCount > 0 && <span className="count">{cartCount}</span>}</NavLink>
          <button className="icon-button" type="button" aria-label="Ouvrir le menu" aria-expanded={menuOpen} aria-controls="mobile-drawer" onClick={() => setMenuOpen(true)}><Icon name="menu" /></button>
        </div>
      </header>

      <main><Outlet /></main>

      <footer className="site-footer">
        <div><Logo /><p>Des pièces intemporelles, pensées pour durer.</p></div>
        <div><strong>Navigation</strong><NavLink to="/shop">Boutique</NavLink><NavLink to="/account/orders">Commandes</NavLink></div>
        <div><strong>Projet</strong><a href="https://github.com/5eef" target="_blank" rel="noreferrer">Youssef BOUGHIOUL</a></div>
      </footer>

      <div className={`drawer-layer${menuOpen ? ' open' : ''}`} aria-hidden={!menuOpen}>
        <button className="drawer-overlay" type="button" aria-label="Fermer le menu" onClick={() => setMenuOpen(false)} />
        <aside className="mobile-drawer" id="mobile-drawer" role="dialog" aria-modal="true" aria-label="Menu principal">
          <div className="drawer-header">
            <NavLink to="/" aria-label="Seef E-commerce — Accueil"><Logo /></NavLink>
            <button ref={closeButton} className="icon-button" type="button" aria-label="Fermer le menu" onClick={() => setMenuOpen(false)}><Icon name="close" /></button>
          </div>
          <nav className="drawer-nav" aria-label="Navigation mobile" onClick={() => setMenuOpen(false)}>
            <NavLink to="/">Accueil</NavLink>
            <NavLink to="/shop">Boutique</NavLink>
            {user && <NavLink to="/wishlist">Wishlist</NavLink>}
            <NavLink to={user ? '/account' : '/login'}>{user ? 'Mon compte' : 'Connexion'}</NavLink>
            {user && <NavLink to="/account/orders">Mes commandes</NavLink>}
            {user?.role === 'admin' && <NavLink to="/admin">Administration</NavLink>}
            <NavLink to="/cart">Panier <span>{cartCount}</span></NavLink>
          </nav>
          <div className="drawer-controls">
            <button type="button" onClick={toggleTheme}><Icon name={theme === 'dark' ? 'sun' : 'moon'} /><span>{theme === 'dark' ? 'Mode clair' : 'Mode sombre'}</span></button>
            {user && <button type="button" onClick={signOut}>Déconnexion</button>}
          </div>
          <div className="drawer-footer"><strong>Seef E-commerce</strong><span>Youssef BOUGHIOUL</span></div>
        </aside>
      </div>
    </div>
  )
}
