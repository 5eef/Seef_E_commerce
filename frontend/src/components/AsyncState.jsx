export function AsyncState({ loading, error, empty, children }) {
  if (loading) return <div className="state" role="status">Chargement…</div>
  if (error) return <div className="state error" role="alert">{error}</div>
  if (empty) return <div className="state">Aucun résultat.</div>
  return children
}
