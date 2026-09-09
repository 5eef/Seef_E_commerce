export async function proxyToBackend(context) {
  const backendUrl = context.env.BACKEND_URL

  if (!backendUrl) {
    return Response.json(
      { message: 'The backend proxy is not configured.' },
      { status: 503 },
    )
  }

  const incomingUrl = new URL(context.request.url)
  const targetUrl = new URL(
    `${incomingUrl.pathname}${incomingUrl.search}`,
    backendUrl.endsWith('/') ? backendUrl : `${backendUrl}/`,
  )
  const headers = new Headers(context.request.headers)
  headers.delete('host')
  headers.set('X-Forwarded-Host', incomingUrl.host)
  headers.set('X-Forwarded-Proto', 'https')

  const requestInit = {
    method: context.request.method,
    headers,
    redirect: 'manual',
  }

  if (!['GET', 'HEAD'].includes(context.request.method)) {
    requestInit.body = context.request.body
  }

  const upstream = await fetch(targetUrl, requestInit)

  return new Response(upstream.body, {
    status: upstream.status,
    statusText: upstream.statusText,
    headers: upstream.headers,
  })
}
