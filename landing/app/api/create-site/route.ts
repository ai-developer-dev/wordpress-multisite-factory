import { NextRequest, NextResponse } from 'next/server'

export async function POST(request: NextRequest) {
  try {
    // Parse request body
    const body = await request.json()
    
    // Validate required fields
    if (!body.site_title || !body.admin_email || !body.desired_domain) {
      return NextResponse.json(
        { error: 'Missing required fields' },
        { status: 400 }
      )
    }
    
    // Get environment variables (server-side only)
    const factoryEndpoint = process.env.FACTORY_CREATE_ENDPOINT
    const factoryToken = process.env.SITE_FACTORY_TOKEN
    
    if (!factoryEndpoint || !factoryToken) {
      console.error('Missing environment variables for Site Factory')
      return NextResponse.json(
        { error: 'Site Factory configuration error' },
        { status: 500 }
      )
    }
    
    // Forward request to WordPress Site Factory
    const response = await fetch(factoryEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Auth': factoryToken,
        'User-Agent': 'Website-Factory-Landing/1.0'
      },
      body: JSON.stringify(body)
    })
    
    const result = await response.json()
    
    // Return the WordPress response
    if (response.ok) {
      return NextResponse.json(result)
    } else {
      return NextResponse.json(
        { error: result.message || 'Failed to create site' },
        { status: response.status }
      )
    }
    
  } catch (error) {
    console.error('Error in create-site API:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}

// Only allow POST requests
export async function GET() {
  return NextResponse.json(
    { error: 'Method not allowed' },
    { status: 405 }
  )
}

