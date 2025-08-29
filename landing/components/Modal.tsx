'use client'

import { useState } from 'react'
import { createWordPressSite, validateBusinessForm, trackSiteCreation } from '../lib/api'

interface FormData {
  businessName: string
  street: string
  city: string
  state: string
  zip: string
  email: string
  phone: string
  businessType: string
  description: string
}

interface ModalProps {
  isOpen: boolean
  onClose: () => void
}

export default function Modal({ isOpen, onClose }: ModalProps) {
  const [formData, setFormData] = useState<FormData>({
    businessName: '',
    street: '',
    city: '',
    state: '',
    zip: '',
    email: '',
    phone: '',
    businessType: 'Business',
    description: ''
  })
  
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitResult, setSubmitResult] = useState<{
    success: boolean
    message: string
    siteUrl?: string
    adminUrl?: string
  } | null>(null)

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value
    }))
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }))
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsSubmitting(true)
    setSubmitResult(null)
    setErrors({})

    // Combine address fields
    const address = [
      formData.street,
      formData.city,
      formData.state,
      formData.zip
    ].filter(Boolean).join(', ')

    // Prepare business data
    const businessData = {
      businessName: formData.businessName,
      email: formData.email,
      phone: formData.phone,
      address: address || formData.street || 'No address provided',
      businessType: formData.businessType.toLowerCase().replace(/\s+/g, '_'),
      template: 'default'
    }

    // Validate form data
    const validation = validateBusinessForm(businessData)
    
    if (!validation.isValid) {
      setErrors(validation.errors)
      setIsSubmitting(false)
      return
    }

    try {
      const result = await createWordPressSite(businessData)

      if (result.success) {
        // Track successful creation
        trackSiteCreation(result)

        setSubmitResult({
          success: true,
          message: 'Your website has been created successfully! Check your email for login credentials.',
          siteUrl: result.site_url,
          adminUrl: result.admin_url
        })
      } else {
        setSubmitResult({
          success: false,
          message: result.message || 'Failed to create website. Please try again.'
        })
      }
    } catch (error: any) {
      console.error('Site creation error:', error)
      
      let errorMessage = 'An error occurred. Please check your connection and try again.'
      
      if (error.status === 429) {
        errorMessage = 'Too many requests. Please wait a moment and try again.'
      } else if (error.status === 401) {
        errorMessage = 'Authentication failed. Please contact support.'
      } else if (error.message) {
        errorMessage = error.message
      }

      setSubmitResult({
        success: false,
        message: errorMessage
      })
    } finally {
      setIsSubmitting(false)
    }
  }

  const resetForm = () => {
    setFormData({
      businessName: '',
      street: '',
      city: '',
      state: '',
      zip: '',
      email: '',
      phone: '',
      businessType: 'Business',
      description: ''
    })
    setSubmitResult(null)
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold text-gray-900">
              Launch Your Website
            </h2>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 text-2xl"
            >
              ×
            </button>
          </div>

          {submitResult ? (
            <div className="text-center py-8">
              <div className={`text-6xl mb-4 ${submitResult.success ? 'text-green-500' : 'text-red-500'}`}>
                {submitResult.success ? '✅' : '❌'}
              </div>
              <h3 className={`text-xl font-semibold mb-4 ${submitResult.success ? 'text-green-800' : 'text-red-800'}`}>
                {submitResult.success ? 'Success!' : 'Oops!'}
              </h3>
              <p className="text-gray-600 mb-6">{submitResult.message}</p>
              
              {submitResult.success && submitResult.siteUrl && (
                <div className="mb-6">
                  <p className="text-sm text-gray-500 mb-2">Your new website:</p>
                  <a
                    href={submitResult.siteUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary-600 hover:text-primary-700 font-medium break-all"
                  >
                    {submitResult.siteUrl}
                  </a>
                </div>
              )}
              
              <div className="flex gap-3 justify-center">
                {submitResult.success ? (
                  <button
                    onClick={onClose}
                    className="btn-primary"
                  >
                    Close
                  </button>
                ) : (
                  <>
                    <button
                      onClick={resetForm}
                      className="btn-secondary"
                    >
                      Try Again
                    </button>
                    <button
                      onClick={onClose}
                      className="btn-primary"
                    >
                      Close
                    </button>
                  </>
                )}
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="businessName" className="form-label">
                    Business Name *
                  </label>
                  <input
                    type="text"
                    id="businessName"
                    name="businessName"
                    value={formData.businessName}
                    onChange={handleInputChange}
                    required
                    className={`form-input ${errors.businessName ? 'border-red-500' : ''}`}
                    placeholder="Acme Corporation"
                  />
                  {errors.businessName && (
                    <p className="mt-1 text-sm text-red-600">{errors.businessName}</p>
                  )}
                </div>
                
                <div>
                  <label htmlFor="businessType" className="form-label">
                    Business Type
                  </label>
                  <select
                    id="businessType"
                    name="businessType"
                    value={formData.businessType}
                    onChange={handleInputChange}
                    className="form-input"
                  >
                    <option value="Business">Business</option>
                    <option value="CPA">CPA</option>
                    <option value="Law Firm">Law Firm</option>
                    <option value="Medical Practice">Medical Practice</option>
                    <option value="Restaurant">Restaurant</option>
                    <option value="Retail">Retail</option>
                    <option value="Consulting">Consulting</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <div>
                <label htmlFor="street" className="form-label">
                  Street Address
                </label>
                <input
                  type="text"
                  id="street"
                  name="street"
                  value={formData.street}
                  onChange={handleInputChange}
                  className="form-input"
                  placeholder="123 Main Street"
                />
              </div>

              <div className="grid md:grid-cols-3 gap-4">
                <div>
                  <label htmlFor="city" className="form-label">
                    City
                  </label>
                  <input
                    type="text"
                    id="city"
                    name="city"
                    value={formData.city}
                    onChange={handleInputChange}
                    className="form-input"
                    placeholder="New York"
                  />
                </div>
                
                <div>
                  <label htmlFor="state" className="form-label">
                    State
                  </label>
                  <input
                    type="text"
                    id="state"
                    name="state"
                    value={formData.state}
                    onChange={handleInputChange}
                    className="form-input"
                    placeholder="NY"
                  />
                </div>
                
                <div>
                  <label htmlFor="zip" className="form-label">
                    ZIP Code
                  </label>
                  <input
                    type="text"
                    id="zip"
                    name="zip"
                    value={formData.zip}
                    onChange={handleInputChange}
                    className="form-input"
                    placeholder="10001"
                  />
                </div>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="email" className="form-label">
                    Email Address *
                  </label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                    className={`form-input ${errors.email ? 'border-red-500' : ''}`}
                    placeholder="you@example.com"
                  />
                  {errors.email && (
                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                  )}
                </div>
                
                <div>
                  <label htmlFor="phone" className="form-label">
                    Phone Number
                  </label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value={formData.phone}
                    onChange={handleInputChange}
                    className={`form-input ${errors.phone ? 'border-red-500' : ''}`}
                    placeholder="(555) 123-4567"
                  />
                  {errors.phone && (
                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                  )}
                </div>
              </div>

              <div>
                <label htmlFor="description" className="form-label">
                  Business Description
                </label>
                <textarea
                  id="description"
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={3}
                  className="form-input"
                  placeholder="Brief description of your business and services..."
                />
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={onClose}
                  className="btn-secondary flex-1"
                  disabled={isSubmitting}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="btn-primary flex-1"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <span className="flex items-center justify-center">
                      <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Creating...
                    </span>
                  ) : (
                    'Create My Website'
                  )}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  )
}

