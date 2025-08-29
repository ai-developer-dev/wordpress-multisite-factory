import './globals.css'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Website Factory - Launch Your Website Today',
  description: 'Create your professional website in minutes with our automated website factory.',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-gray-50">
        {children}
      </body>
    </html>
  )
}

