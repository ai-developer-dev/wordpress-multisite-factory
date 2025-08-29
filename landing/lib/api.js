/**
 * WordPress Site Factory API Client
 * Handles API communication between the landing page and WordPress multisite
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_WORDPRESS_URL || 'https://your-wordpress-site.railway.app';
const API_TOKEN = process.env.NEXT_PUBLIC_API_TOKEN;

/**
 * API Error Class
 */
class ApiError extends Error {
  constructor(message, status, response) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.response = response;
  }
}

/**
 * Make API request with proper error handling
 */
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE_URL}/wp-json/site-factory/v1${endpoint}`;
  
  const defaultOptions = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${API_TOKEN}`,
      ...options.headers,
    },
  };

  const requestOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers,
    },
  };

  try {
    const response = await fetch(url, requestOptions);
    const data = await response.json();

    if (!response.ok) {
      throw new ApiError(
        data.message || 'API request failed',
        response.status,
        data
      );
    }

    return data;
  } catch (error) {
    if (error instanceof ApiError) {
      throw error;
    }

    // Network or other errors
    throw new ApiError(
      'Network error or server unavailable',
      0,
      null
    );
  }
}

/**
 * Create a new WordPress site
 * @param {Object} businessData - Business information from the form
 * @returns {Promise<Object>} Site creation response
 */
export async function createWordPressSite(businessData) {
  const payload = {
    business_name: businessData.businessName,
    email: businessData.email,
    phone: businessData.phone,
    address: businessData.address,
    business_type: businessData.businessType,
    template: businessData.template || 'default'
  };

  return await apiRequest('/create-site', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

/**
 * Get site creation status
 * @param {number} siteId - The site ID to check
 * @returns {Promise<Object>} Site status information
 */
export async function getSiteStatus(siteId) {
  return await apiRequest(`/site-status/${siteId}`, {
    method: 'GET',
  });
}

/**
 * Get available business templates
 * @returns {Promise<Object>} Available templates
 */
export async function getBusinessTemplates() {
  return await apiRequest('/templates', {
    method: 'GET',
  });
}

/**
 * Validate business form data
 * @param {Object} formData - Form data to validate
 * @returns {Object} Validation result
 */
export function validateBusinessForm(formData) {
  const errors = {};

  // Business name validation
  if (!formData.businessName || formData.businessName.trim().length < 2) {
    errors.businessName = 'Business name must be at least 2 characters long';
  } else if (formData.businessName.length > 100) {
    errors.businessName = 'Business name must be less than 100 characters';
  }

  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!formData.email) {
    errors.email = 'Email is required';
  } else if (!emailRegex.test(formData.email)) {
    errors.email = 'Please enter a valid email address';
  }

  // Phone validation
  const phoneRegex = /^[\d\s\-\+\(\)]+$/;
  if (!formData.phone) {
    errors.phone = 'Phone number is required';
  } else if (!phoneRegex.test(formData.phone)) {
    errors.phone = 'Please enter a valid phone number';
  }

  // Address validation
  if (!formData.address || formData.address.trim().length < 10) {
    errors.address = 'Please provide a complete address';
  }

  // Business type validation
  const allowedBusinessTypes = ['restaurant', 'retail', 'professional', 'healthcare', 'education', 'nonprofit', 'other'];
  if (!formData.businessType || !allowedBusinessTypes.includes(formData.businessType)) {
    errors.businessType = 'Please select a valid business type';
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
}

/**
 * Track site creation analytics
 * @param {Object} siteData - Site creation data
 */
export function trackSiteCreation(siteData) {
  // Add your analytics tracking here (Google Analytics, etc.)
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'site_created', {
      'business_type': siteData.business_type,
      'template': siteData.template,
      'site_id': siteData.site_id
    });
  }
}

/**
 * Format site URL for display
 * @param {string} siteUrl - Raw site URL
 * @returns {string} Formatted URL
 */
export function formatSiteUrl(siteUrl) {
  try {
    const url = new URL(siteUrl);
    return url.hostname + url.pathname;
  } catch {
    return siteUrl;
  }
}

/**
 * Check if API is available
 * @returns {Promise<boolean>} API availability status
 */
export async function checkApiHealth() {
  try {
    const healthUrl = `${API_BASE_URL}/health.php`;
    const response = await fetch(healthUrl, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      },
    });
    
    const data = await response.json();
    return response.ok && data.status === 'ok';
  } catch {
    return false;
  }
}

export { ApiError };