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
    grid: <><circle cx="6" cy="6" r="1.5" fill="currentColor" stroke="none" /><circle cx="12" cy="6" r="1.5" fill="currentColor" stroke="none" /><circle cx="18" cy="6" r="1.5" fill="currentColor" stroke="none" /><circle cx="6" cy="12" r="1.5" fill="currentColor" stroke="none" /><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none" /><circle cx="18" cy="12" r="1.5" fill="currentColor" stroke="none" /><circle cx="6" cy="18" r="1.5" fill="currentColor" stroke="none" /><circle cx="12" cy="18" r="1.5" fill="currentColor" stroke="none" /><circle cx="18" cy="18" r="1.5" fill="currentColor" stroke="none" /></>,
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

function HeaderPanel({ id, title, onClose, children }) {
  return (
    <section className="header-popover" id={id} aria-label={title}>
      <div className="header-popover-title">
        <h2>{title}</h2>
        <button className="icon-button" type="button" aria-label={`Fermer ${title.toLowerCase()}`} onClick={onClose}><Icon name="close" /></button>
      </div>
      {children}
    </section>
  )
}

export function Layout() {
  const { user, loading: authLoading, logout } = useAuth()
  const { cart, loading: cartLoading } = useCart()
  const { theme, toggleTheme } = useTheme()
  const [menuOpen, setMenuOpen] = useState(false)
  const [activePanel, setActivePanel] = useState(null)
  const closeButton = useRef(null)
  const menuButton = useRef(null)
  const headerTools = useRef(null)
  const navigate = useNavigate()
  const cartCount = cart?.items_count || 0

  function closePanel() {
    setActivePanel(null)
  }

  function togglePanel(panel) {
    setMenuOpen(false)
    setActivePanel((current) => current === panel ? null : panel)
  }

  function closeMenu(restoreFocus = false) {
    if (document.activeElement instanceof HTMLElement) document.activeElement.blur()
    setMenuOpen(false)
    if (restoreFocus) window.setTimeout(() => menuButton.current?.focus(), 0)
  }

  function openMenu() {
    closePanel()
    setMenuOpen(true)
  }

  useEffect(() => {
    if (!menuOpen) return undefined
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    closeButton.current?.focus()

    function onKeyDown(event) {
      if (event.key === 'Escape') closeMenu(true)
    }

    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [menuOpen])

  useEffect(() => {
    if (!activePanel) return undefined

    function onPointerDown(event) {
      if (!headerTools.current?.contains(event.target)) closePanel()
    }

    function onKeyDown(event) {
      if (event.key === 'Escape') closePanel()
    }

    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [activePanel])

  async function signOut() {
    await logout()
    closePanel()
    closeMenu()
    navigate('/')
  }

  function search(event) {
    event.preventDefault()
    const query = String(new FormData(event.currentTarget).get('q') || '').trim()
    closePanel()
    navigate(query ? `/shop?q=${encodeURIComponent(query)}` : '/shop')
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

        <div className="header-tools" ref={headerTools}>
          <div className="header-actions desktop-actions">
            <button className={`icon-button${activePanel === 'navigation' ? ' active' : ''}`} type="button" aria-label="Ouvrir la navigation" aria-expanded={activePanel === 'navigation'} aria-controls="header-navigation-panel" onClick={() => togglePanel('navigation')}><Icon name="grid" /></button>
            <NavLink className="icon-button" to="/wishlist" aria-label="Ma wishlist"><Icon name="heart" /></NavLink>
            <button className={`icon-button${activePanel === 'cart' ? ' active' : ''} cart-action`} type="button" aria-label={`Ouvrir le panier, ${cartCount} article${cartCount > 1 ? 's' : ''}`} aria-expanded={activePanel === 'cart'} aria-controls="header-cart-panel" onClick={() => togglePanel('cart')}><Icon name="cart" />{cartCount > 0 && <span className="count">{cartCount}</span>}</button>
            <button className={`icon-button${activePanel === 'profile' ? ' active' : ''}`} type="button" aria-label={user ? 'Ouvrir mon profil' : 'Ouvrir la connexion'} aria-expanded={activePanel === 'profile'} aria-controls="header-profile-panel" onClick={() => togglePanel('profile')}><Icon name="account" /></button>
          </div>

          <div className="mobile-actions">
            <button ref={menuButton} className="icon-button" type="button" aria-label="Ouvrir le menu" aria-expanded={menuOpen} aria-controls="mobile-drawer" onClick={openMenu}><Icon name="menu" /></button>
            <button className={`icon-button${activePanel === 'profile' ? ' active' : ''}`} type="button" aria-label={user ? 'Ouvrir mon profil' : 'Ouvrir la connexion'} aria-expanded={activePanel === 'profile'} aria-controls="header-profile-panel" onClick={() => togglePanel('profile')}><Icon name="account" /></button>
            <button className={`icon-button${activePanel === 'cart' ? ' active' : ''} cart-action`} type="button" aria-label={`Ouvrir le panier, ${cartCount} article${cartCount > 1 ? 's' : ''}`} aria-expanded={activePanel === 'cart'} aria-controls="header-cart-panel" onClick={() => togglePanel('cart')}><Icon name="cart" />{cartCount > 0 && <span className="count">{cartCount}</span>}</button>
          </div>

          {activePanel === 'navigation' && (
            <HeaderPanel id="header-navigation-panel" title="Navigation" onClose={closePanel}>
              <form className="header-search" role="search" onSubmit={search}>
                <Icon name="search" />
                <input name="q" type="search" placeholder="Rechercher un produit" aria-label="Rechercher un produit" />
                <button type="submit">Rechercher</button>
              </form>
              <div className="header-panel-links">
                <NavLink to="/" onClick={closePanel}>Accueil</NavLink>
                <NavLink to="/shop" onClick={closePanel}>Boutique</NavLink>
                <NavLink to="/wishlist" onClick={closePanel}>Wishlist</NavLink>
                {user && <NavLink to="/account/orders" onClick={closePanel}>Commandes</NavLink>}
                {user?.role === 'admin' && <NavLink to="/admin" onClick={closePanel}>Administration</NavLink>}
                <button type="button" onClick={toggleTheme}><Icon name={theme === 'dark' ? 'sun' : 'moon'} />{themeLabel}</button>
              </div>
            </HeaderPanel>
          )}

          {activePanel === 'profile' && (
            <HeaderPanel id="header-profile-panel" title="Profil" onClose={closePanel}>
              {authLoading ? <p className="popover-status">Chargement du profil…</p> : user ? (
                <>
                  <div className="profile-summary">
                    <span className="profile-avatar" aria-hidden="true">{user.name?.charAt(0).toUpperCase() || 'S'}</span>
                    <div><strong>{user.name}</strong><span>{user.email}</span><small>{user.role === 'admin' ? 'Administrateur' : 'Client'}</small></div>
                  </div>
                  <div className="header-panel-links single-column">
                    <NavLink to="/account" onClick={closePanel}>Mon compte</NavLink>
                    <NavLink to="/account/orders" onClick={closePanel}>Mes commandes</NavLink>
                    <NavLink to="/account/addresses" onClick={closePanel}>Mes adresses</NavLink>
                    {user.role === 'admin' && <NavLink to="/admin" onClick={closePanel}>Tableau de bord admin</NavLink>}
                    <button className="danger" type="button" onClick={signOut}>Se déconnecter</button>
                  </div>
                </>
              ) : (
                <>
                  <p className="popover-status">Connectez-vous pour retrouver vos commandes, adresses et favoris.</p>
                  <div className="popover-actions">
                    <NavLink className="button" to="/login" onClick={closePanel}>Connexion</NavLink>
                    <NavLink className="button secondary" to="/register" onClick={closePanel}>Créer un compte</NavLink>
                  </div>
                </>
              )}
            </HeaderPanel>
          )}

          {activePanel === 'cart' && (
            <HeaderPanel id="header-cart-panel" title="Panier" onClose={closePanel}>
              {cartLoading ? <p className="popover-status">Chargement du panier…</p> : cart?.items?.length ? (
                <>
                  <div className="mini-cart-list">
                    {cart.items.slice(0, 4).map((item) => (
                      <article className="mini-cart-item" key={item.id}>
                        <div><strong>{item.product?.name}</strong><span>{item.variant?.name || item.variant?.sku} · Qté {item.quantity}</span></div>
                        <b>{item.line_total} MAD</b>
                      </article>
                    ))}
                  </div>
                  <div className="mini-cart-total"><span>Sous-total</span><strong>{cart.subtotal} MAD</strong></div>
                  <NavLink className="button popover-primary" to="/cart" onClick={closePanel}>Voir le panier</NavLink>
                </>
              ) : (
                <>
                  <p className="popover-status">Votre panier est vide.</p>
                  <NavLink className="button popover-primary" to="/shop" onClick={closePanel}>Découvrir la boutique</NavLink>
                </>
              )}
            </HeaderPanel>
          )}
        </div>
      </header>

      <main><Outlet /></main>

      <footer className="site-footer">
        <div><Logo /><p>Des pièces intemporelles, pensées pour durer.</p></div>
        <div><strong>Navigation</strong><NavLink to="/shop">Boutique</NavLink><NavLink to="/account/orders">Commandes</NavLink></div>
        <div><strong>Projet</strong><a href="https://github.com/5eef" target="_blank" rel="noreferrer">Youssef BOUGHIOUL</a></div>
      </footer>

      {menuOpen && (
        <div className="drawer-layer open">
          <button className="drawer-overlay" type="button" aria-label="Fermer le menu" onClick={() => closeMenu(true)} />
          <aside className="mobile-drawer" id="mobile-drawer" role="dialog" aria-modal="true" aria-label="Menu principal">
            <div className="drawer-header">
              <NavLink to="/" aria-label="Seef E-commerce — Accueil" onClick={() => closeMenu()}><Logo /></NavLink>
              <button ref={closeButton} className="icon-button" type="button" aria-label="Fermer le menu" onClick={() => closeMenu(true)}><Icon name="close" /></button>
            </div>
            <nav className="drawer-nav" aria-label="Navigation mobile" onClick={() => closeMenu()}>
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
      )}
    </div>
  )
}
