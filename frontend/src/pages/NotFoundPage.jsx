import { Link } from 'react-router-dom'
export function NotFoundPage() { return <section className="form-page"><div className="panel success"><p className="eyebrow">Erreur 404</p><h1>Page introuvable.</h1><Link className="button" to="/">Retour à l’accueil</Link></div></section> }
