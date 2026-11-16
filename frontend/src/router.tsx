import { createBrowserRouter } from 'react-router'
import { Layout } from './components/layout/Layout'
import { HomePage } from './pages/HomePage'
import { NotFoundPage } from './pages/NotFoundPage'
import { StorePage } from './pages/StorePage'

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'store', element: <StorePage mode="store" /> },
      { path: 'search', element: <StorePage mode="search" /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
