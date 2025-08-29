'use client'

import { useState } from 'react'
import Modal from '../components/Modal'

export default function HomePage() {
  const [isModalOpen, setIsModalOpen] = useState(false)

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <div className="bg-gradient-to-br from-primary-600 to-primary-700 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
          <div className="text-center">
            <h1 className="text-4xl md:text-6xl font-bold mb-6">
              Launch Your Website
              <span className="block text-primary-200">in Minutes</span>
            </h1>
            <p className="text-xl md:text-2xl text-primary-100 mb-8 max-w-3xl mx-auto">
              Our automated website factory creates professional websites instantly. 
              No coding required, no waiting, just results.
            </p>
            <button
              onClick={() => setIsModalOpen(true)}
              className="btn-primary text-lg px-8 py-4 bg-white text-primary-700 hover:bg-gray-100"
            >
              🚀 Launch My Website
            </button>
          </div>
        </div>
      </div>

      {/* Features Section */}
      <div className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Why Choose Our Website Factory?
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Professional websites built automatically with your business information
            </p>
          </div>
          
          <div className="grid md:grid-cols-3 gap-8">
            <div className="text-center">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">⚡</span>
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">Instant Creation</h3>
              <p className="text-gray-600">Your website is ready in seconds, not weeks</p>
            </div>
            
            <div className="text-center">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🎨</span>
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">Professional Design</h3>
              <p className="text-gray-600">Beautiful, responsive designs that work on all devices</p>
            </div>
            
            <div className="text-center">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🔧</span>
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">Easy Management</h3>
              <p className="text-gray-600">Simple admin panel to update your content</p>
            </div>
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-gray-900 text-white py-16">
        <div className="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">
            Ready to Get Started?
          </h2>
          <p className="text-xl text-gray-300 mb-8">
            Join thousands of businesses who have launched their websites with us
          </p>
          <button
            onClick={() => setIsModalOpen(true)}
            className="btn-primary text-lg px-8 py-4"
          >
            Start Building Now
          </button>
        </div>
      </div>

      {/* Modal */}
      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </div>
  )
}

