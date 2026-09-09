import { useCallback, useEffect, useState } from 'react'

function currentTheme() {
  return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light'
}

export function useTheme() {
  const [theme, setTheme] = useState(currentTheme)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    document.documentElement.style.colorScheme = theme
    localStorage.setItem('theme', theme)
  }, [theme])

  const toggleTheme = useCallback(() => {
    setTheme((value) => value === 'dark' ? 'light' : 'dark')
  }, [])

  return { theme, toggleTheme }
}
