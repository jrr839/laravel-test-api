# Next.js Integration Guide - Laravel REST API

**Complete TypeScript Integration for Next.js 14+ App Router**

This guide provides everything you need to integrate your Laravel REST API with a Next.js/TypeScript frontend using full type safety generated from the OpenAPI specification.

---

## Table of Contents

1. [Overview & Prerequisites](#overview--prerequisites)
2. [Quick Start](#quick-start-5-minutes)
3. [Type Generation Setup](#type-generation-setup)
4. [API Client Architecture](#api-client-architecture)
5. [Authentication Flow](#authentication-flow-implementation)
6. [Server Components Pattern](#server-components-pattern)
7. [Client Components Pattern](#client-components-pattern)
8. [Complete Endpoint Examples](#complete-endpoint-examples)
   - [User Registration](#1-user-registration)
   - [User Login](#2-user-login)
   - [Get User Profile](#3-get-user-profile)
   - [Logout Current Device](#4-logout-current-device)
   - [Logout All Devices](#5-logout-all-devices)
   - [Forgot Password](#6-forgot-password)
   - [Reset Password](#7-reset-password)
9. [Best Practices & Security](#best-practices--security)
10. [Development Workflow](#development-workflow)
11. [Production Deployment](#production-deployment)
12. [Appendix & Quick Reference](#appendix--quick-reference)

---

## Overview & Prerequisites

### What This Guide Covers

This guide demonstrates how to build a **type-safe Next.js frontend** that consumes the Laravel REST API with:

- ✅ **Automatic TypeScript type generation** from OpenAPI spec
- ✅ **Complete authentication flow** (register, login, logout, password reset)
- ✅ **Server Component and Client Component patterns** for Next.js 14+ App Router
- ✅ **Error handling** for all scenarios (validation, auth, rate limiting)
- ✅ **Secure token management** using httpOnly cookies
- ✅ **Production-ready architecture** with best practices

### Prerequisites

**Required:**
- Node.js 18+ and npm/pnpm/yarn
- TypeScript 5+
- Next.js 14+ (App Router)
- Running Laravel API at `http://localhost/api`

**Knowledge:**
- Basic TypeScript and React
- Next.js App Router fundamentals
- REST API concepts

### Technology Stack

| Technology | Purpose |
|------------|---------|
| **Next.js 14+** | Frontend framework (App Router) |
| **TypeScript 5+** | Type safety |
| **openapi-typescript** | Generate types from OpenAPI spec |
| **Laravel Sanctum** | API token authentication |
| **Server Actions** | Form submission (Next.js) |

### API Endpoints

Your Laravel API provides 7 authentication endpoints:

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/auth/register` | POST | No | Create new user account |
| `/api/auth/login` | POST | No | Authenticate and get token |
| `/api/auth/user` | GET | Yes | Get authenticated user |
| `/api/auth/logout` | POST | Yes | Revoke current token |
| `/api/auth/logout/all` | DELETE | Yes | Revoke all user tokens |
| `/api/auth/forgot-password` | POST | No | Send password reset email |
| `/api/auth/reset-password` | POST | No | Reset password with token |

---

## Quick Start (5 Minutes)

Let's get you from zero to making your first type-safe API call in under 5 minutes.

### Step 1: Create Next.js Project

```bash
# Create new Next.js project with TypeScript
npx create-next-app@latest my-app --typescript --app --eslint --tailwind

cd my-app
```

### Step 2: Install Dependencies

```bash
npm install openapi-typescript openapi-typescript-helpers
npm install -D @types/node
```

### Step 3: Download OpenAPI Spec

Download the OpenAPI specification from your running Laravel API:

```bash
# Make sure your Laravel API is running at http://localhost
curl http://localhost/docs.openapi -o openapi.yaml
```

📝 **Note:** The OpenAPI spec is available at `/docs.openapi` when you're authenticated with the documentation user (jonathanrr839@gmail.com).

### Step 4: Generate Types

```bash
# Generate TypeScript types from OpenAPI spec
npx openapi-typescript ./openapi.yaml -o ./lib/api-types.ts
```

You should see output like:
```
✓ openapi.yaml -> lib/api-types.ts [XXXms]
```

### Step 5: Create API Client

Create `lib/api-client.ts`:

```typescript
// lib/api-client.ts
const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost/api'

export async function apiClient<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${path}`

  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    },
  })

  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.message || `HTTP ${response.status}`)
  }

  // Handle 204 No Content
  if (response.status === 204) {
    return undefined as T
  }

  return response.json()
}
```

### Step 6: Make Your First API Call

Create `app/test/page.tsx`:

```typescript
// app/test/page.tsx
import { apiClient } from '@/lib/api-client'
import type { paths } from '@/lib/api-types'

type LoginResponse = paths['/api/auth/login']['post']['responses']['200']['content']['application/json']

export default async function TestPage() {
  try {
    const data = await apiClient<LoginResponse>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'password123',
      }),
    })

    return (
      <div>
        <h1>User: {data.data.user.name}</h1>
        <p>Token: {data.data.token.substring(0, 20)}...</p>
      </div>
    )
  } catch (error) {
    return <div>Error: {error.message}</div>
  }
}
```

### Step 7: Add Environment Variables

Create `.env.local`:

```env
NEXT_PUBLIC_API_BASE_URL=http://localhost/api
```

### Step 8: Run Your App

```bash
npm run dev
```

Visit `http://localhost:3000/test` to see your typed API call in action!

💡 **Pro Tip:** Notice how TypeScript gives you autocomplete for `data.data.user.name` and `data.data.token` - that's the power of type generation!

---

## Type Generation Setup

Let's set up automatic type generation so you always have up-to-date types.

### Approach 1: NPM Script (Recommended)

This approach regenerates types before every dev/build command.

**Step 1: Create Generation Script**

Create `scripts/generate-api-types.js`:

```javascript
// scripts/generate-api-types.js
const { exec } = require('child_process')
const https = require('https')
const http = require('http')
const fs = require('fs')

const OPENAPI_URL = process.env.NEXT_PUBLIC_API_BASE_URL
  ? `${process.env.NEXT_PUBLIC_API_BASE_URL.replace('/api', '')}/docs.openapi`
  : 'http://localhost/docs.openapi'

console.log(`📥 Fetching OpenAPI spec from ${OPENAPI_URL}...`)

const protocol = OPENAPI_URL.startsWith('https') ? https : http

protocol.get(OPENAPI_URL, (res) => {
  let data = ''

  res.on('data', (chunk) => {
    data += chunk
  })

  res.on('end', () => {
    fs.writeFileSync('./openapi.yaml', data)
    console.log('✓ OpenAPI spec downloaded')

    console.log('🔨 Generating TypeScript types...')
    exec('npx openapi-typescript ./openapi.yaml -o ./lib/api-types.ts', (error, stdout, stderr) => {
      if (error) {
        console.error(`❌ Error: ${error.message}`)
        process.exit(1)
      }
      console.log('✓ TypeScript types generated')
    })
  })
}).on('error', (err) => {
  console.error(`❌ Failed to fetch OpenAPI spec: ${err.message}`)
  console.log('⚠️  Using existing openapi.yaml if available...')

  if (fs.existsSync('./openapi.yaml')) {
    exec('npx openapi-typescript ./openapi.yaml -o ./lib/api-types.ts', (error, stdout, stderr) => {
      if (error) {
        console.error(`❌ Error: ${error.message}`)
        process.exit(1)
      }
      console.log('✓ TypeScript types generated from cached spec')
    })
  } else {
    process.exit(1)
  }
})
```

**Step 2: Update package.json**

```json
{
  "scripts": {
    "generate:types": "node scripts/generate-api-types.js",
    "predev": "npm run generate:types",
    "dev": "next dev",
    "prebuild": "npm run generate:types",
    "build": "next build"
  }
}
```

Now types regenerate automatically before `npm run dev` and `npm run build`!

### Approach 2: Local File

If you prefer manual control, download and commit the OpenAPI spec:

```bash
# Download spec
curl http://localhost/docs.openapi -o openapi.yaml

# Generate types
npx openapi-typescript ./openapi.yaml -o ./lib/api-types.ts

# Commit both files
git add openapi.yaml lib/api-types.ts
```

Update package.json:

```json
{
  "scripts": {
    "generate:types": "npx openapi-typescript ./openapi.yaml -o ./lib/api-types.ts"
  }
}
```

Run manually when API changes:
```bash
npm run generate:types
```

### Approach 3: Remote URL (Advanced)

Generate directly from the live API URL:

```bash
npx openapi-typescript http://localhost/docs.openapi -o ./lib/api-types.ts
```

⚠️ **Warning:** This requires the API to be running and accessible. Not recommended for CI/CD pipelines.

### Type Helper Utilities

Create `lib/api-type-helpers.ts` for convenient type extraction:

```typescript
// lib/api-type-helpers.ts
import type { paths } from './api-types'

/**
 * Extract request body type for a given path and method
 */
export type RequestBody<
  Path extends keyof paths,
  Method extends keyof paths[Path]
> = paths[Path][Method] extends { requestBody: { content: { 'application/json': infer T } } }
  ? T
  : never

/**
 * Extract successful response type for a given path and method
 */
export type SuccessResponse<
  Path extends keyof paths,
  Method extends keyof paths[Path]
> = paths[Path][Method] extends { responses: { 200: { content: { 'application/json': infer T } } } }
  ? T
  : paths[Path][Method] extends { responses: { 201: { content: { 'application/json': infer T } } } }
  ? T
  : paths[Path][Method] extends { responses: { 204: any } }
  ? void
  : never

/**
 * Extract error response type for a given path, method, and status code
 */
export type ErrorResponse<
  Path extends keyof paths,
  Method extends keyof paths[Path],
  Status extends number
> = paths[Path][Method] extends { responses: { [K in Status]: { content: { 'application/json': infer T } } } }
  ? T
  : never

// Usage examples:
// type LoginRequest = RequestBody<'/api/auth/login', 'post'>
// type LoginSuccess = SuccessResponse<'/api/auth/login', 'post'>
// type LoginError = ErrorResponse<'/api/auth/login', 'post', 401>
```

---

## API Client Architecture

Now let's build a complete, production-ready API client with error handling, auth token management, and a typed service layer.

### Core API Client

Create `lib/api-client.ts`:

```typescript
// lib/api-client.ts

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost/api'

/**
 * Custom error class for API errors
 */
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public data?: any
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

/**
 * Configuration for API requests
 */
export interface ApiClientConfig extends RequestInit {
  token?: string
}

/**
 * Core API client function with type safety
 */
export async function apiClient<T>(
  path: string,
  config: ApiClientConfig = {}
): Promise<T> {
  const { token, ...options } = config
  const url = `${API_BASE_URL}${path}`

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  }

  // Add authorization header if token provided
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    })

    // Handle successful empty responses (204 No Content)
    if (response.status === 204) {
      return undefined as T
    }

    // Parse response body
    const data = await response.json().catch(() => ({}))

    // Handle error responses
    if (!response.ok) {
      throw new ApiError(
        data.message || `HTTP ${response.status}`,
        response.status,
        data
      )
    }

    return data as T
  } catch (error) {
    // Re-throw ApiError as-is
    if (error instanceof ApiError) {
      throw error
    }

    // Wrap network errors
    throw new ApiError(
      error instanceof Error ? error.message : 'Network request failed',
      0
    )
  }
}

/**
 * HTTP method helpers
 */
export const api = {
  get: <T>(path: string, config?: ApiClientConfig) =>
    apiClient<T>(path, { ...config, method: 'GET' }),

  post: <T>(path: string, body?: any, config?: ApiClientConfig) =>
    apiClient<T>(path, {
      ...config,
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    }),

  put: <T>(path: string, body?: any, config?: ApiClientConfig) =>
    apiClient<T>(path, {
      ...config,
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    }),

  patch: <T>(path: string, body?: any, config?: ApiClientConfig) =>
    apiClient<T>(path, {
      ...config,
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
    }),

  delete: <T>(path: string, config?: ApiClientConfig) =>
    apiClient<T>(path, { ...config, method: 'DELETE' }),
}
```

### Token Storage (Server-Side)

Create `lib/token-storage.ts` for secure server-side token management:

```typescript
// lib/token-storage.ts
'use server'

import { cookies } from 'next/headers'

const TOKEN_COOKIE_NAME = 'auth_token'

const COOKIE_OPTIONS = {
  httpOnly: true,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax' as const,
  maxAge: 60 * 60 * 24 * 30, // 30 days
  path: '/',
}

/**
 * Set authentication token in httpOnly cookie
 */
export async function setAuthToken(token: string): Promise<void> {
  const cookieStore = await cookies()
  cookieStore.set(TOKEN_COOKIE_NAME, token, COOKIE_OPTIONS)
}

/**
 * Get authentication token from httpOnly cookie
 */
export async function getAuthToken(): Promise<string | undefined> {
  const cookieStore = await cookies()
  return cookieStore.get(TOKEN_COOKIE_NAME)?.value
}

/**
 * Remove authentication token from httpOnly cookie
 */
export async function removeAuthToken(): Promise<void> {
  const cookieStore = await cookies()
  cookieStore.delete(TOKEN_COOKIE_NAME)
}
```

### Token Storage (Client-Side Alternative)

Create `lib/token-storage-client.ts` if you prefer client-side storage:

```typescript
// lib/token-storage-client.ts

const TOKEN_STORAGE_KEY = 'auth_token'

/**
 * Set authentication token in sessionStorage
 * ⚠️ Warning: Less secure than httpOnly cookies, vulnerable to XSS
 */
export function setAuthToken(token: string): void {
  if (typeof window === 'undefined') return
  sessionStorage.setItem(TOKEN_STORAGE_KEY, token)
}

/**
 * Get authentication token from sessionStorage
 */
export function getAuthToken(): string | null {
  if (typeof window === 'undefined') return null
  return sessionStorage.getItem(TOKEN_STORAGE_KEY)
}

/**
 * Remove authentication token from sessionStorage
 */
export function removeAuthToken(): void {
  if (typeof window === 'undefined') return
  sessionStorage.removeItem(TOKEN_STORAGE_KEY)
}
```

⚠️ **Security Warning:** Only use client-side storage for development or non-production apps. httpOnly cookies are much more secure.

### Error Utilities

Create `lib/api-errors.ts` for error handling:

```typescript
// lib/api-errors.ts
import { ApiError } from './api-client'

/**
 * Type guard to check if error is ApiError
 */
export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError
}

/**
 * Check if error is a validation error (422)
 */
export function isValidationError(error: unknown): boolean {
  return isApiError(error) && error.status === 422
}

/**
 * Extract validation errors from 422 response
 */
export function getValidationErrors(error: unknown): Record<string, string[]> {
  if (!isValidationError(error)) return {}
  const apiError = error as ApiError
  return apiError.data?.errors || {}
}

/**
 * Get user-friendly error message
 */
export function getErrorMessage(error: unknown): string {
  if (isApiError(error)) {
    return error.message
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'An unexpected error occurred'
}

/**
 * Get first validation error message for a field
 */
export function getFieldError(
  errors: Record<string, string[]>,
  field: string
): string | undefined {
  return errors[field]?.[0]
}

/**
 * Handle API error with optional custom handlers
 */
export function handleApiError(
  error: unknown,
  handlers?: {
    onValidationError?: (errors: Record<string, string[]>) => void
    onUnauthorized?: () => void
    onRateLimited?: () => void
    onNotFound?: () => void
    onServerError?: () => void
  }
): void {
  if (!isApiError(error)) {
    console.error('Unexpected error:', error)
    return
  }

  switch (error.status) {
    case 422:
      handlers?.onValidationError?.(getValidationErrors(error))
      break
    case 401:
      handlers?.onUnauthorized?.()
      break
    case 429:
      handlers?.onRateLimited?.()
      break
    case 404:
      handlers?.onNotFound?.()
      break
    case 500:
    case 503:
      handlers?.onServerError?.()
      break
  }
}
```

### API Service Layer

Create `lib/api-service.ts` with typed methods for all endpoints:

```typescript
// lib/api-service.ts
import { api } from './api-client'
import type { RequestBody, SuccessResponse } from './api-type-helpers'

/**
 * Authentication service with typed methods
 */
export const authService = {
  /**
   * Register a new user
   */
  async register(
    data: RequestBody<'/api/auth/register', 'post'>
  ): Promise<SuccessResponse<'/api/auth/register', 'post'>> {
    return api.post('/auth/register', data)
  },

  /**
   * Login with email and password
   */
  async login(
    credentials: RequestBody<'/api/auth/login', 'post'>
  ): Promise<SuccessResponse<'/api/auth/login', 'post'>> {
    return api.post('/auth/login', credentials)
  },

  /**
   * Get authenticated user profile
   */
  async getUser(
    token?: string
  ): Promise<SuccessResponse<'/api/auth/user', 'get'>> {
    return api.get('/auth/user', { token })
  },

  /**
   * Logout from current device (revoke current token)
   */
  async logout(token?: string): Promise<void> {
    return api.post('/auth/logout', undefined, { token })
  },

  /**
   * Logout from all devices (revoke all tokens)
   */
  async logoutAll(token?: string): Promise<void> {
    return api.delete('/auth/logout/all', { token })
  },

  /**
   * Request password reset email
   */
  async forgotPassword(
    data: RequestBody<'/api/auth/forgot-password', 'post'>
  ): Promise<SuccessResponse<'/api/auth/forgot-password', 'post'>> {
    return api.post('/auth/forgot-password', data)
  },

  /**
   * Reset password with token
   */
  async resetPassword(
    data: RequestBody<'/api/auth/reset-password', 'post'>
  ): Promise<SuccessResponse<'/api/auth/reset-password', 'post'>> {
    return api.post('/auth/reset-password', data)
  },
}
```

🎉 **You now have a complete, type-safe API client!** TypeScript will autocomplete all request bodies and response types.

---

## Authentication Flow Implementation

Let's implement a complete authentication system with context, protected routes, and server-side utilities.

### Auth Context Provider

Create `contexts/auth-context.tsx`:

```typescript
// contexts/auth-context.tsx
'use client'

import { createContext, useContext, useState, useEffect, ReactNode } from 'react'
import { authService } from '@/lib/api-service'
import { getAuthToken, removeAuthToken, setAuthToken } from '@/lib/token-storage-client'
import { useRouter } from 'next/navigation'
import type { SuccessResponse } from '@/lib/api-type-helpers'

type User = SuccessResponse<'/api/auth/user', 'get'>['data']

interface AuthContextType {
  user: User | null
  isLoading: boolean
  isAuthenticating: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>
  refetchUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isAuthenticating, setIsAuthenticating] = useState(false)
  const router = useRouter()

  // Fetch user on mount if token exists
  useEffect(() => {
    const loadUser = async () => {
      const token = getAuthToken()
      if (!token) {
        setIsLoading(false)
        return
      }

      try {
        const response = await authService.getUser(token)
        setUser(response.data)
      } catch (error) {
        removeAuthToken()
        setUser(null)
      } finally {
        setIsLoading(false)
      }
    }

    loadUser()
  }, [])

  const login = async (email: string, password: string) => {
    setIsAuthenticating(true)
    try {
      const response = await authService.login({ email, password })
      setAuthToken(response.data.token)
      setUser(response.data.user)
      router.push('/dashboard')
    } finally {
      setIsAuthenticating(false)
    }
  }

  const register = async (
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string
  ) => {
    setIsAuthenticating(true)
    try {
      const response = await authService.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      setAuthToken(response.data.token)
      setUser(response.data.user)
      router.push('/dashboard')
    } finally {
      setIsAuthenticating(false)
    }
  }

  const logout = async () => {
    const token = getAuthToken()
    if (token) {
      try {
        await authService.logout(token)
      } catch (error) {
        // Logout locally even if API call fails
        console.error('Logout error:', error)
      }
    }
    removeAuthToken()
    setUser(null)
    router.push('/login')
  }

  const refetchUser = async () => {
    const token = getAuthToken()
    if (!token) return

    try {
      const response = await authService.getUser(token)
      setUser(response.data)
    } catch (error) {
      removeAuthToken()
      setUser(null)
    }
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticating,
        login,
        logout,
        register,
        refetchUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}
```

**Add to your root layout:**

```typescript
// app/layout.tsx
import { AuthProvider } from '@/contexts/auth-context'

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <AuthProvider>
          {children}
        </AuthProvider>
      </body>
    </html>
  )
}
```

### Protected Route Component

Create `components/protected-route.tsx`:

```typescript
// components/protected-route.tsx
'use client'

import { useAuth } from '@/contexts/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!isLoading && !user) {
      router.push('/login')
    }
  }, [user, isLoading, router])

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-lg">Loading...</div>
      </div>
    )
  }

  if (!user) {
    return null
  }

  return <>{children}</>
}
```

**Usage:**

```typescript
// app/dashboard/page.tsx
'use client'

import { ProtectedRoute } from '@/components/protected-route'
import { useAuth } from '@/contexts/auth-context'

export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <DashboardContent />
    </ProtectedRoute>
  )
}

function DashboardContent() {
  const { user } = useAuth()
  return (
    <div>
      <h1>Welcome, {user?.name}!</h1>
    </div>
  )
}
```

### Server-Side Auth Utilities

Create `lib/server-auth.ts`:

```typescript
// lib/server-auth.ts
'use server'

import { redirect } from 'next/navigation'
import { getAuthToken } from './token-storage'
import { authService } from './api-service'
import type { SuccessResponse } from './api-type-helpers'

type User = SuccessResponse<'/api/auth/user', 'get'>['data']

/**
 * Require authentication - redirect to login if not authenticated
 */
export async function requireAuth(): Promise<User> {
  const token = await getAuthToken()

  if (!token) {
    redirect('/login')
  }

  try {
    const response = await authService.getUser(token)
    return response.data
  } catch (error) {
    redirect('/login')
  }
}

/**
 * Get authenticated user if available, otherwise return null
 */
export async function getOptionalAuth(): Promise<User | null> {
  const token = await getAuthToken()

  if (!token) {
    return null
  }

  try {
    const response = await authService.getUser(token)
    return response.data
  } catch (error) {
    return null
  }
}
```

**Usage in Server Components:**

```typescript
// app/profile/page.tsx
import { requireAuth } from '@/lib/server-auth'

export default async function ProfilePage() {
  const user = await requireAuth()

  return (
    <div>
      <h1>Profile</h1>
      <p>Name: {user.name}</p>
      <p>Email: {user.email}</p>
    </div>
  )
}
```

---

## Server Components Pattern

**When to Use Server Components:**
- Initial page loads requiring auth
- SEO-critical pages
- Dashboard pages displaying server data
- Pages with minimal interactivity

### Advantages

✅ No client-side JavaScript for auth check
✅ Faster initial render
✅ SEO-friendly
✅ Secure (tokens never sent to client)

### Complete Example: User Profile Page

```typescript
// app/profile/page.tsx
import { requireAuth } from '@/lib/server-auth'
import { ProfileDisplay } from './profile-display'

export default async function ProfilePage() {
  // Server-side auth check - redirects if not authenticated
  const user = await requireAuth()

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">My Profile</h1>
      <ProfileDisplay user={user} />
    </div>
  )
}
```

```typescript
// app/profile/profile-display.tsx
import type { SuccessResponse } from '@/lib/api-type-helpers'

type User = SuccessResponse<'/api/auth/user', 'get'>['data']

export function ProfileDisplay({ user }: { user: User }) {
  return (
    <div className="bg-white shadow rounded-lg p-6">
      <div className="space-y-4">
        <div>
          <label className="text-sm font-medium text-gray-700">Name</label>
          <p className="mt-1 text-lg">{user.name}</p>
        </div>
        <div>
          <label className="text-sm font-medium text-gray-700">Email</label>
          <p className="mt-1 text-lg">{user.email}</p>
        </div>
        <div>
          <label className="text-sm font-medium text-gray-700">Member Since</label>
          <p className="mt-1 text-lg">
            {new Date(user.created_at).toLocaleDateString()}
          </p>
        </div>
      </div>
    </div>
  )
}
```

---

## Client Components Pattern

**When to Use Client Components:**
- Interactive forms
- Real-time updates
- Client-side navigation
- Complex UI state management

### Advantages

✅ Interactive UX
✅ No page refresh
✅ Better error handling UX
✅ Real-time validation

### Complete Example: Login Form

```typescript
// app/login/page.tsx
'use client'

import { useState, FormEvent } from 'react'
import { useAuth } from '@/contexts/auth-context'
import { getErrorMessage, getValidationErrors, isValidationError } from '@/lib/api-errors'
import Link from 'next/link'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const { login, isAuthenticating } = useAuth()

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})

    try {
      await login(email, password)
      // Redirects to /dashboard on success (handled in AuthContext)
    } catch (err) {
      if (isValidationError(err)) {
        setFieldErrors(getValidationErrors(err))
      } else {
        setError(getErrorMessage(err))
      }
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Sign in to your account
          </h2>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {error && (
            <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                Email address
              </label>
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
              {fieldErrors.email && (
                <p className="mt-1 text-sm text-red-600">{fieldErrors.email[0]}</p>
              )}
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                Password
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
              {fieldErrors.password && (
                <p className="mt-1 text-sm text-red-600">{fieldErrors.password[0]}</p>
              )}
            </div>
          </div>

          <div>
            <button
              type="submit"
              disabled={isAuthenticating}
              className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {isAuthenticating ? 'Signing in...' : 'Sign in'}
            </button>
          </div>

          <div className="text-sm text-center">
            <Link href="/forgot-password" className="text-indigo-600 hover:text-indigo-500">
              Forgot your password?
            </Link>
          </div>

          <div className="text-sm text-center">
            Don't have an account?{' '}
            <Link href="/register" className="text-indigo-600 hover:text-indigo-500">
              Sign up
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
```

---

## Complete Endpoint Examples

Let's implement complete, production-ready examples for all 7 authentication endpoints.

For each endpoint, we'll show **both patterns**:
- **Pattern A:** Server Component + Server Action (recommended)
- **Pattern B:** Client Component + useState (alternative)

### 1. User Registration

#### Pattern A: Server Component + Server Action

```typescript
// app/register/page.tsx
import { RegisterForm } from './register-form'

export default function RegisterPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Create your account
          </h2>
        </div>
        <RegisterForm />
      </div>
    </div>
  )
}
```

```typescript
// app/register/register-form.tsx
'use client'

import { useActionState } from 'react'
import { registerAction } from './actions'
import Link from 'next/link'

export function RegisterForm() {
  const [state, formAction, isPending] = useActionState(registerAction, null)

  return (
    <form action={formAction} className="mt-8 space-y-6">
      {state?.error && (
        <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
          {state.error}
        </div>
      )}

      <div className="space-y-4">
        <div>
          <label htmlFor="name" className="block text-sm font-medium text-gray-700">
            Full Name
          </label>
          <input
            id="name"
            name="name"
            type="text"
            required
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          />
          {state?.errors?.name && (
            <p className="mt-1 text-sm text-red-600">{state.errors.name[0]}</p>
          )}
        </div>

        <div>
          <label htmlFor="email" className="block text-sm font-medium text-gray-700">
            Email address
          </label>
          <input
            id="email"
            name="email"
            type="email"
            required
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          />
          {state?.errors?.email && (
            <p className="mt-1 text-sm text-red-600">{state.errors.email[0]}</p>
          )}
        </div>

        <div>
          <label htmlFor="password" className="block text-sm font-medium text-gray-700">
            Password
          </label>
          <input
            id="password"
            name="password"
            type="password"
            required
            minLength={8}
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          />
          {state?.errors?.password && (
            <p className="mt-1 text-sm text-red-600">{state.errors.password[0]}</p>
          )}
        </div>

        <div>
          <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">
            Confirm Password
          </label>
          <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            required
            minLength={8}
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        {isPending ? 'Creating account...' : 'Create account'}
      </button>

      <div className="text-sm text-center">
        Already have an account?{' '}
        <Link href="/login" className="text-indigo-600 hover:text-indigo-500">
          Sign in
        </Link>
      </div>
    </form>
  )
}
```

```typescript
// app/register/actions.ts
'use server'

import { redirect } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { setAuthToken } from '@/lib/token-storage'
import { getValidationErrors, isValidationError, getErrorMessage } from '@/lib/api-errors'

export async function registerAction(prevState: any, formData: FormData) {
  const name = formData.get('name') as string
  const email = formData.get('email') as string
  const password = formData.get('password') as string
  const password_confirmation = formData.get('password_confirmation') as string

  try {
    const response = await authService.register({
      name,
      email,
      password,
      password_confirmation,
    })

    // Store token in httpOnly cookie
    await setAuthToken(response.data.token)

    // Redirect to dashboard
    redirect('/dashboard')
  } catch (error) {
    if (isValidationError(error)) {
      return {
        success: false,
        errors: getValidationErrors(error),
      }
    }

    return {
      success: false,
      error: getErrorMessage(error),
    }
  }
}
```

#### Pattern B: Client Component (Alternative)

```typescript
// app/register-client/page.tsx
'use client'

import { useState, FormEvent } from 'react'
import { useAuth } from '@/contexts/auth-context'
import { getErrorMessage, getValidationErrors, isValidationError } from '@/lib/api-errors'
import Link from 'next/link'

export default function RegisterClientPage() {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const { register, isAuthenticating } = useAuth()

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})

    try {
      await register(name, email, password, passwordConfirmation)
      // Redirects to /dashboard on success (handled in AuthContext)
    } catch (err) {
      if (isValidationError(err)) {
        setFieldErrors(getValidationErrors(err))
      } else {
        setError(getErrorMessage(err))
      }
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Create your account
          </h2>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {error && (
            <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                Full Name
              </label>
              <input
                id="name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
              />
              {fieldErrors.name && (
                <p className="mt-1 text-sm text-red-600">{fieldErrors.name[0]}</p>
              )}
            </div>

            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                Email address
              </label>
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
              />
              {fieldErrors.email && (
                <p className="mt-1 text-sm text-red-600">{fieldErrors.email[0]}</p>
              )}
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                Password
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                minLength={8}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
              />
              {fieldErrors.password && (
                <p className="mt-1 text-sm text-red-600">{fieldErrors.password[0]}</p>
              )}
            </div>

            <div>
              <label htmlFor="passwordConfirmation" className="block text-sm font-medium text-gray-700">
                Confirm Password
              </label>
              <input
                id="passwordConfirmation"
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                required
                minLength={8}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={isAuthenticating}
            className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {isAuthenticating ? 'Creating account...' : 'Create account'}
          </button>

          <div className="text-sm text-center">
            Already have an account?{' '}
            <Link href="/login" className="text-indigo-600 hover:text-indigo-500">
              Sign in
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
```

### 2. User Login

#### Pattern A: Server Component + Server Action

```typescript
// app/login/actions.ts
'use server'

import { redirect } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { setAuthToken } from '@/lib/token-storage'
import { getValidationErrors, isValidationError, getErrorMessage } from '@/lib/api-errors'

export async function loginAction(prevState: any, formData: FormData) {
  const email = formData.get('email') as string
  const password = formData.get('password') as string

  try {
    const response = await authService.login({ email, password })
    await setAuthToken(response.data.token)
    redirect('/dashboard')
  } catch (error) {
    if (isValidationError(error)) {
      return {
        success: false,
        errors: getValidationErrors(error),
      }
    }

    return {
      success: false,
      error: getErrorMessage(error),
    }
  }
}
```

*(Login form component shown earlier in "Client Components Pattern" section)*

### 3. Get User Profile

#### Pattern A: Server Component

```typescript
// app/profile/page.tsx
import { requireAuth } from '@/lib/server-auth'

export default async function ProfilePage() {
  const user = await requireAuth()

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">My Profile</h1>

      <div className="bg-white shadow rounded-lg p-6 max-w-2xl">
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-gray-700">Name</label>
            <p className="mt-1 text-lg">{user.name}</p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Email</label>
            <p className="mt-1 text-lg">{user.email}</p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Email Verified</label>
            <p className="mt-1 text-lg">
              {user.email_verified_at ? 'Yes' : 'No'}
            </p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Member Since</label>
            <p className="mt-1 text-lg">
              {new Date(user.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              })}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
```

#### Pattern B: Client Component

```typescript
// app/profile-client/page.tsx
'use client'

import { ProtectedRoute } from '@/components/protected-route'
import { useAuth } from '@/contexts/auth-context'

export default function ProfileClientPage() {
  return (
    <ProtectedRoute>
      <ProfileContent />
    </ProtectedRoute>
  )
}

function ProfileContent() {
  const { user } = useAuth()

  if (!user) return null

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">My Profile</h1>

      <div className="bg-white shadow rounded-lg p-6 max-w-2xl">
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-gray-700">Name</label>
            <p className="mt-1 text-lg">{user.name}</p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Email</label>
            <p className="mt-1 text-lg">{user.email}</p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Email Verified</label>
            <p className="mt-1 text-lg">
              {user.email_verified_at ? 'Yes' : 'No'}
            </p>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700">Member Since</label>
            <p className="mt-1 text-lg">
              {new Date(user.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              })}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
```

### 4. Logout Current Device

#### Pattern A: Server Action

```typescript
// app/dashboard/logout-button.tsx
'use client'

import { useActionState } from 'react'
import { logoutAction } from './actions'

export function LogoutButton() {
  const [state, formAction, isPending] = useActionState(logoutAction, null)

  return (
    <form action={formAction}>
      <button
        type="submit"
        disabled={isPending}
        className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
      >
        {isPending ? 'Logging out...' : 'Logout'}
      </button>
    </form>
  )
}
```

```typescript
// app/dashboard/actions.ts
'use server'

import { redirect } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { getAuthToken, removeAuthToken } from '@/lib/token-storage'

export async function logoutAction() {
  const token = await getAuthToken()

  if (token) {
    try {
      await authService.logout(token)
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  await removeAuthToken()
  redirect('/login')
}
```

#### Pattern B: Client Component

```typescript
// components/logout-button-client.tsx
'use client'

import { useAuth } from '@/contexts/auth-context'
import { useState } from 'react'

export function LogoutButtonClient() {
  const { logout } = useAuth()
  const [isLoggingOut, setIsLoggingOut] = useState(false)

  async function handleLogout() {
    setIsLoggingOut(true)
    try {
      await logout()
    } finally {
      setIsLoggingOut(false)
    }
  }

  return (
    <button
      onClick={handleLogout}
      disabled={isLoggingOut}
      className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
    >
      {isLoggingOut ? 'Logging out...' : 'Logout'}
    </button>
  )
}
```

### 5. Logout All Devices

#### Pattern A: Server Action

```typescript
// app/settings/logout-all-button.tsx
'use client'

import { useActionState, useState } from 'react'
import { logoutAllAction } from './actions'

export function LogoutAllButton() {
  const [showConfirm, setShowConfirm] = useState(false)
  const [state, formAction, isPending] = useActionState(logoutAllAction, null)

  if (!showConfirm) {
    return (
      <button
        onClick={() => setShowConfirm(true)}
        className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
      >
        Logout All Devices
      </button>
    )
  }

  return (
    <div className="space-y-2">
      <p className="text-sm text-red-600">
        This will log you out from all devices. Are you sure?
      </p>
      <form action={formAction} className="flex gap-2">
        <button
          type="submit"
          disabled={isPending}
          className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
        >
          {isPending ? 'Logging out...' : 'Yes, logout all'}
        </button>
        <button
          type="button"
          onClick={() => setShowConfirm(false)}
          className="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
        >
          Cancel
        </button>
      </form>
    </div>
  )
}
```

```typescript
// app/settings/actions.ts
'use server'

import { redirect } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { getAuthToken, removeAuthToken } from '@/lib/token-storage'

export async function logoutAllAction() {
  const token = await getAuthToken()

  if (token) {
    try {
      await authService.logoutAll(token)
    } catch (error) {
      console.error('Logout all error:', error)
    }
  }

  await removeAuthToken()
  redirect('/login')
}
```

#### Pattern B: Client Component

```typescript
// components/logout-all-button-client.tsx
'use client'

import { useState } from 'react'
import { authService } from '@/lib/api-service'
import { getAuthToken, removeAuthToken } from '@/lib/token-storage-client'
import { useRouter } from 'next/navigation'

export function LogoutAllButtonClient() {
  const [showConfirm, setShowConfirm] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const router = useRouter()

  async function handleLogoutAll() {
    setIsLoading(true)
    const token = getAuthToken()

    if (token) {
      try {
        await authService.logoutAll(token)
      } catch (error) {
        console.error('Logout all error:', error)
      }
    }

    removeAuthToken()
    router.push('/login')
  }

  if (!showConfirm) {
    return (
      <button
        onClick={() => setShowConfirm(true)}
        className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
      >
        Logout All Devices
      </button>
    )
  }

  return (
    <div className="space-y-2">
      <p className="text-sm text-red-600">
        This will log you out from all devices. Are you sure?
      </p>
      <div className="flex gap-2">
        <button
          onClick={handleLogoutAll}
          disabled={isLoading}
          className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
        >
          {isLoading ? 'Logging out...' : 'Yes, logout all'}
        </button>
        <button
          onClick={() => setShowConfirm(false)}
          className="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
        >
          Cancel
        </button>
      </div>
    </div>
  )
}
```

### 6. Forgot Password

#### Pattern A: Server Action

```typescript
// app/forgot-password/page.tsx
import { ForgotPasswordForm } from './forgot-password-form'

export default function ForgotPasswordPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Reset your password
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            Enter your email address and we'll send you a password reset link.
          </p>
        </div>
        <ForgotPasswordForm />
      </div>
    </div>
  )
}
```

```typescript
// app/forgot-password/forgot-password-form.tsx
'use client'

import { useActionState } from 'react'
import { forgotPasswordAction } from './actions'
import Link from 'next/link'

export function ForgotPasswordForm() {
  const [state, formAction, isPending] = useActionState(forgotPasswordAction, null)

  return (
    <form action={formAction} className="mt-8 space-y-6">
      {state?.success && (
        <div className="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
          {state.message}
        </div>
      )}

      {state?.error && (
        <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
          {state.error}
        </div>
      )}

      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          Email address
        </label>
        <input
          id="email"
          name="email"
          type="email"
          required
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
        />
        {state?.errors?.email && (
          <p className="mt-1 text-sm text-red-600">{state.errors.email[0]}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        {isPending ? 'Sending...' : 'Send reset link'}
      </button>

      <div className="text-sm text-center">
        <Link href="/login" className="text-indigo-600 hover:text-indigo-500">
          Back to login
        </Link>
      </div>
    </form>
  )
}
```

```typescript
// app/forgot-password/actions.ts
'use server'

import { authService } from '@/lib/api-service'
import { getValidationErrors, isValidationError, getErrorMessage } from '@/lib/api-errors'

export async function forgotPasswordAction(prevState: any, formData: FormData) {
  const email = formData.get('email') as string

  try {
    const response = await authService.forgotPassword({ email })

    return {
      success: true,
      message: response.message,
    }
  } catch (error) {
    if (isValidationError(error)) {
      return {
        success: false,
        errors: getValidationErrors(error),
      }
    }

    return {
      success: false,
      error: getErrorMessage(error),
    }
  }
}
```

#### Pattern B: Client Component

```typescript
// app/forgot-password-client/page.tsx
'use client'

import { useState, FormEvent } from 'react'
import { authService } from '@/lib/api-service'
import { getErrorMessage, getValidationErrors, isValidationError } from '@/lib/api-errors'
import Link from 'next/link'

export default function ForgotPasswordClientPage() {
  const [email, setEmail] = useState('')
  const [success, setSuccess] = useState(false)
  const [successMessage, setSuccessMessage] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const [isLoading, setIsLoading] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setSuccess(false)
    setIsLoading(true)

    try {
      const response = await authService.forgotPassword({ email })
      setSuccess(true)
      setSuccessMessage(response.message)
      setEmail('')
    } catch (err) {
      if (isValidationError(err)) {
        setFieldErrors(getValidationErrors(err))
      } else {
        setError(getErrorMessage(err))
      }
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Reset your password
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            Enter your email address and we'll send you a password reset link.
          </p>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {success && (
            <div className="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
              {successMessage}
            </div>
          )}

          {error && (
            <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
              Email address
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            />
            {fieldErrors.email && (
              <p className="mt-1 text-sm text-red-600">{fieldErrors.email[0]}</p>
            )}
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {isLoading ? 'Sending...' : 'Send reset link'}
          </button>

          <div className="text-sm text-center">
            <Link href="/login" className="text-indigo-600 hover:text-indigo-500">
              Back to login
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
```

### 7. Reset Password

#### Pattern A: Server Action

```typescript
// app/reset-password/page.tsx
import { ResetPasswordForm } from './reset-password-form'

export default function ResetPasswordPage({
  searchParams,
}: {
  searchParams: { token?: string; email?: string }
}) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Reset your password
          </h2>
        </div>
        <ResetPasswordForm token={searchParams.token} email={searchParams.email} />
      </div>
    </div>
  )
}
```

```typescript
// app/reset-password/reset-password-form.tsx
'use client'

import { useActionState } from 'react'
import { resetPasswordAction } from './actions'

export function ResetPasswordForm({ token, email }: { token?: string; email?: string }) {
  const [state, formAction, isPending] = useActionState(resetPasswordAction, null)

  return (
    <form action={formAction} className="mt-8 space-y-6">
      <input type="hidden" name="token" value={token || ''} />

      {state?.success && (
        <div className="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
          Password reset successfully! Redirecting to login...
        </div>
      )}

      {state?.error && (
        <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
          {state.error}
        </div>
      )}

      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          Email address
        </label>
        <input
          id="email"
          name="email"
          type="email"
          defaultValue={email || ''}
          required
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
        />
        {state?.errors?.email && (
          <p className="mt-1 text-sm text-red-600">{state.errors.email[0]}</p>
        )}
      </div>

      <div>
        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
          New Password
        </label>
        <input
          id="password"
          name="password"
          type="password"
          required
          minLength={8}
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
        />
        {state?.errors?.password && (
          <p className="mt-1 text-sm text-red-600">{state.errors.password[0]}</p>
        )}
      </div>

      <div>
        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">
          Confirm Password
        </label>
        <input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          required
          minLength={8}
          className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
        />
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
      >
        {isPending ? 'Resetting password...' : 'Reset password'}
      </button>
    </form>
  )
}
```

```typescript
// app/reset-password/actions.ts
'use server'

import { redirect } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { getValidationErrors, isValidationError, getErrorMessage } from '@/lib/api-errors'

export async function resetPasswordAction(prevState: any, formData: FormData) {
  const token = formData.get('token') as string
  const email = formData.get('email') as string
  const password = formData.get('password') as string
  const password_confirmation = formData.get('password_confirmation') as string

  try {
    await authService.resetPassword({
      token,
      email,
      password,
      password_confirmation,
    })

    // Show success message briefly, then redirect
    setTimeout(() => redirect('/login'), 2000)

    return {
      success: true,
    }
  } catch (error) {
    if (isValidationError(error)) {
      return {
        success: false,
        errors: getValidationErrors(error),
      }
    }

    return {
      success: false,
      error: getErrorMessage(error),
    }
  }
}
```

#### Pattern B: Client Component

```typescript
// app/reset-password-client/page.tsx
'use client'

import { useState, FormEvent, useEffect } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { authService } from '@/lib/api-service'
import { getErrorMessage, getValidationErrors, isValidationError } from '@/lib/api-errors'

export default function ResetPasswordClientPage() {
  const searchParams = useSearchParams()
  const router = useRouter()
  const [token, setToken] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const [isLoading, setIsLoading] = useState(false)

  useEffect(() => {
    const tokenParam = searchParams.get('token')
    const emailParam = searchParams.get('email')
    if (tokenParam) setToken(tokenParam)
    if (emailParam) setEmail(emailParam)
  }, [searchParams])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setSuccess(false)
    setIsLoading(true)

    try {
      await authService.resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      setSuccess(true)
      setTimeout(() => router.push('/login'), 2000)
    } catch (err) {
      if (isValidationError(err)) {
        setFieldErrors(getValidationErrors(err))
      } else {
        setError(getErrorMessage(err))
      }
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="text-center text-3xl font-bold text-gray-900">
            Reset your password
          </h2>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {success && (
            <div className="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
              Password reset successfully! Redirecting to login...
            </div>
          )}

          {error && (
            <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
              Email address
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            />
            {fieldErrors.email && (
              <p className="mt-1 text-sm text-red-600">{fieldErrors.email[0]}</p>
            )}
          </div>

          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
              New Password
            </label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            />
            {fieldErrors.password && (
              <p className="mt-1 text-sm text-red-600">{fieldErrors.password[0]}</p>
            )}
          </div>

          <div>
            <label htmlFor="passwordConfirmation" className="block text-sm font-medium text-gray-700">
              Confirm Password
            </label>
            <input
              id="passwordConfirmation"
              type="password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              minLength={8}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"
            />
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {isLoading ? 'Resetting password...' : 'Reset password'}
          </button>
        </form>
      </div>
    </div>
  )
}
```

---

## Best Practices & Security

### Security Considerations

⚠️ **Critical Security Rules:**

1. **NEVER use localStorage for auth tokens** - Vulnerable to XSS attacks
   ```typescript
   // ❌ BAD - Don't do this
   localStorage.setItem('token', token)

   // ✅ GOOD - Use httpOnly cookies
   await setAuthToken(token) // Server-side cookie
   ```

2. **Always use HTTPS in production** - Prevents man-in-the-middle attacks
   ```env
   # Production .env
   NEXT_PUBLIC_API_BASE_URL=https://api.yourdomain.com/api
   ```

3. **Enable CSRF protection** - Laravel Sanctum handles this automatically

4. **Sanitize error messages** - Don't leak sensitive information
   ```typescript
   // ❌ BAD - Exposes internal details
   throw new Error(error.stack)

   // ✅ GOOD - User-friendly message
   throw new Error('Login failed. Please check your credentials.')
   ```

5. **Validate all user inputs** - Never trust client-side data

### CORS Configuration

Your Laravel API must allow requests from your Next.js domain:

```php
// Laravel: config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000', // Development
        'https://yourdomain.com', // Production
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

💡 **Pro Tip:** For development, you can use `'allowed_origins' => ['*']`, but NEVER do this in production!

### Error Handling Strategy

Handle these common scenarios:

```typescript
// 1. Network errors (fetch failed)
try {
  await api.get('/auth/user')
} catch (error) {
  if (error.status === 0) {
    showToast('Network error. Please check your connection.')
  }
}

// 2. Validation errors (422)
if (isValidationError(error)) {
  const errors = getValidationErrors(error)
  // Display field-specific errors
}

// 3. Unauthorized (401)
if (error.status === 401) {
  removeAuthToken()
  router.push('/login')
}

// 4. Rate limiting (429)
if (error.status === 429) {
  showToast('Too many attempts. Please wait 60 seconds.')
}

// 5. Server errors (500)
if (error.status >= 500) {
  showToast('Server error. Please try again later.')
}
```

### Loading States & UX

**Always show loading indicators:**

```typescript
const [isLoading, setIsLoading] = useState(false)

async function handleSubmit() {
  setIsLoading(true)
  try {
    await api.post('/auth/login', data)
  } finally {
    setIsLoading(false)
  }
}

return (
  <button disabled={isLoading}>
    {isLoading ? 'Loading...' : 'Submit'}
  </button>
)
```

**Use optimistic updates where appropriate:**

```typescript
// Optimistically update UI before API call
setUser({ ...user, name: newName })
try {
  await api.patch('/auth/user', { name: newName })
} catch (error) {
  // Revert on error
  setUser(originalUser)
}
```

### Environment Variables

```env
# .env.local (Development)
NEXT_PUBLIC_API_BASE_URL=http://localhost/api

# .env.production (Production)
NEXT_PUBLIC_API_BASE_URL=https://api.yourdomain.com/api
```

⚠️ **Important:** Variables prefixed with `NEXT_PUBLIC_` are exposed to the browser. Never put secrets in them!

---

## Development Workflow

### Local Development Setup

**Terminal 1: Laravel API**
```bash
cd example-api
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
```

**Terminal 2: Next.js App**
```bash
cd my-nextjs-app
npm run dev
```

**Access URLs:**
- Laravel API: http://localhost/api
- Laravel Docs: http://localhost/docs
- Next.js App: http://localhost:3000

### Type Regeneration Workflow

When you modify the Laravel API:

1. **Change API** (add/modify endpoints in Laravel)
2. **Regenerate OpenAPI spec:**
   ```bash
   ./vendor/bin/sail artisan scribe:generate
   ```
3. **Download new spec:**
   ```bash
   curl http://localhost/docs.openapi -o openapi.yaml
   ```
4. **Regenerate types:**
   ```bash
   npm run generate:types
   ```
5. **Fix TypeScript errors** in Next.js
6. **Test all affected endpoints**

💡 **Pro Tip:** If using Approach 1 (NPM Script), steps 3-4 happen automatically on `npm run dev`!

### Testing Checklist

- [ ] Create test user in Laravel:
  ```bash
  ./vendor/bin/sail artisan tinker
  User::factory()->create(['email' => 'test@example.com'])
  ```
- [ ] Test registration flow (valid + invalid data)
- [ ] Test login (valid credentials, invalid credentials, rate limiting)
- [ ] Verify token persistence (refresh page, still logged in)
- [ ] Test protected routes (authenticated + unauthenticated)
- [ ] Test logout (single device + all devices)
- [ ] Test password reset flow (request link + reset)

### Debugging Tips

1. **Network Tab** - Inspect API calls in browser DevTools
   - Check request headers (Authorization: Bearer token)
   - Inspect response bodies for error details

2. **React DevTools** - Check component state and props
   - Verify auth context state
   - Check user object structure

3. **Cookies** - Inspect in Application tab
   - Verify auth_token cookie exists
   - Check httpOnly, secure, sameSite flags

4. **Console Logs** - Add strategic logging
   ```typescript
   console.log('Token:', await getAuthToken())
   console.log('User:', user)
   console.log('API Response:', response)
   ```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Enable HTTPS for API domain
- [ ] Configure CORS for production Next.js domain
- [ ] Set environment variables in hosting platform
- [ ] Use httpOnly cookies with `secure: true` flag
- [ ] Enable error monitoring (Sentry, LogRocket)
- [ ] Test authentication flow on staging environment
- [ ] Verify token expiration handling (if enabled)
- [ ] Review and test all error scenarios

### Environment Variables (Production)

```env
# Next.js Production (.env.production)
NEXT_PUBLIC_API_BASE_URL=https://api.yourdomain.com/api
NEXT_PUBLIC_APP_URL=https://yourdomain.com
```

### CORS Configuration (Laravel Production)

```php
// config/cors.php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
],
'supports_credentials' => true,
```

### Token Storage (Production)

Update `lib/token-storage.ts`:

```typescript
const COOKIE_OPTIONS = {
  httpOnly: true,
  secure: true, // ✅ Always true in production
  sameSite: 'lax' as const,
  maxAge: 60 * 60 * 24 * 30, // 30 days
  path: '/',
  domain: '.yourdomain.com', // Share cookies across subdomains
}
```

### Deployment Platforms

**Recommended: Vercel**
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel --prod
```

**Netlify**
```bash
# Install Netlify CLI
npm i -g netlify-cli

# Deploy
netlify deploy --prod
```

**Docker (Self-Hosted)**
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build
EXPOSE 3000
CMD ["npm", "start"]
```

### Error Monitoring Setup

```bash
npm install @sentry/nextjs
```

```typescript
// sentry.client.config.ts
import * as Sentry from '@sentry/nextjs'

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
})
```

---

## Appendix & Quick Reference

### API Endpoints Table

| Endpoint | Method | Auth Required | Success | Error Codes |
|----------|--------|---------------|---------|-------------|
| `/api/auth/register` | POST | No | 201 | 422 |
| `/api/auth/login` | POST | No | 200 | 401, 429 |
| `/api/auth/user` | GET | Yes | 200 | 401 |
| `/api/auth/logout` | POST | Yes | 204 | 401 |
| `/api/auth/logout/all` | DELETE | Yes | 204 | 401 |
| `/api/auth/forgot-password` | POST | No | 200 | 400 |
| `/api/auth/reset-password` | POST | No | 200 | 400, 422 |

### HTTP Status Codes

| Code | Name | Handling Strategy |
|------|------|-------------------|
| 200 | OK | Parse response data |
| 201 | Created | Parse response data |
| 204 | No Content | No response body, return void |
| 400 | Bad Request | Show error message from API |
| 401 | Unauthorized | Clear token, redirect to login |
| 422 | Validation Error | Display field-specific errors |
| 429 | Too Many Requests | Show "Too many attempts" message |
| 500 | Server Error | Show generic error, log to monitoring |

### TypeScript Types Reference

```typescript
// Generated from OpenAPI spec (lib/api-types.ts)
import type { paths } from '@/lib/api-types'
import type { RequestBody, SuccessResponse } from '@/lib/api-type-helpers'

// User type
type User = {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
}

// Auth response (login/register)
type AuthResponse = {
  data: {
    user: User
    token: string
    token_type: 'Bearer'
  }
}

// User response (GET /api/auth/user)
type UserResponse = {
  data: User
}

// Validation error
type ValidationError = {
  message: string
  errors: Record<string, string[]>
}

// Generic message response
type MessageResponse = {
  message: string
}

// Type helpers
type LoginRequest = RequestBody<'/api/auth/login', 'post'>
type LoginSuccess = SuccessResponse<'/api/auth/login', 'post'>
type RegisterRequest = RequestBody<'/api/auth/register', 'post'>
```

### Troubleshooting Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| **CORS errors** | Different origin domains | Configure Laravel `config/cors.php` with allowed origins |
| **401 Unauthorized** | Missing or invalid token | Check `getAuthToken()` returns valid token |
| **Type errors** | Outdated generated types | Run `npm run generate:types` to regenerate |
| **429 Rate limiting** | Too many login attempts | Wait 60 seconds or handle in UI with countdown |
| **Token not persisting** | Cookie configuration wrong | Verify httpOnly, sameSite, secure flags |
| **Redirect loop** | Auth check logic error | Review `requireAuth()` implementation |
| **Network timeout** | API not running or wrong URL | Check `NEXT_PUBLIC_API_BASE_URL` and Laravel status |
| **Types not updating** | Cache issue | Delete `lib/api-types.ts` and regenerate |

### Useful Commands

```bash
# Generate types from local spec
npm run generate:types

# Generate types from remote URL
npx openapi-typescript http://localhost/docs.openapi -o ./lib/api-types.ts

# Type check
npm run type-check

# Build for production
npm run build

# Start production server
npm start
```

### Useful Links

- **Laravel API Documentation:** http://localhost/docs
- **OpenAPI Spec:** http://localhost/docs.openapi
- **Postman Collection:** http://localhost/docs.postman
- **Next.js App Router Docs:** https://nextjs.org/docs/app
- **openapi-typescript:** https://openapi-ts.dev
- **Laravel Sanctum:** https://laravel.com/docs/sanctum
- **TypeScript Handbook:** https://www.typescriptlang.org/docs/

### File Structure Reference

```
my-nextjs-app/
├── app/
│   ├── layout.tsx                    # Root layout with AuthProvider
│   ├── login/
│   │   ├── page.tsx                  # Login page
│   │   └── actions.ts                # Login server action
│   ├── register/
│   │   ├── page.tsx                  # Register page
│   │   ├── register-form.tsx         # Register form component
│   │   └── actions.ts                # Register server action
│   ├── forgot-password/
│   │   ├── page.tsx                  # Forgot password page
│   │   ├── forgot-password-form.tsx
│   │   └── actions.ts
│   ├── reset-password/
│   │   ├── page.tsx                  # Reset password page
│   │   ├── reset-password-form.tsx
│   │   └── actions.ts
│   ├── dashboard/
│   │   ├── page.tsx                  # Protected dashboard
│   │   ├── logout-button.tsx
│   │   └── actions.ts
│   └── profile/
│       └── page.tsx                  # User profile (server component)
├── components/
│   └── protected-route.tsx           # Client-side route protection
├── contexts/
│   └── auth-context.tsx              # Auth context provider
├── lib/
│   ├── api-client.ts                 # Core API client
│   ├── api-service.ts                # Typed service layer
│   ├── api-errors.ts                 # Error handling utilities
│   ├── api-types.ts                  # Generated types (auto)
│   ├── api-type-helpers.ts           # Type helper utilities
│   ├── token-storage.ts              # Server-side token storage
│   ├── token-storage-client.ts       # Client-side token storage
│   └── server-auth.ts                # Server-side auth utilities
├── scripts/
│   └── generate-api-types.js         # Type generation script
├── openapi.yaml                      # OpenAPI spec (downloaded)
├── .env.local                        # Local environment variables
├── .env.production                   # Production environment variables
└── package.json
```

---

## Summary

🎉 **Congratulations!** You now have everything you need to integrate your Laravel REST API with Next.js using full TypeScript type safety.

### What You've Learned

✅ Generate types automatically from OpenAPI spec
✅ Build a complete, type-safe API client
✅ Implement authentication with secure token management
✅ Use both Server Components and Client Components patterns
✅ Handle all error scenarios properly
✅ Deploy to production securely

### Next Steps

1. **Start with Quick Start** - Get your first API call working in 5 minutes
2. **Set up type generation** - Choose your preferred approach
3. **Build your auth flow** - Use the complete endpoint examples
4. **Add your features** - Extend the patterns to your own endpoints
5. **Deploy to production** - Follow the deployment checklist

### Need Help?

- **Laravel API Docs:** http://localhost/docs
- **GitHub Issues:** Report bugs or request features
- **Community:** Join discussions and get help

---

**Made with ❤️ for Next.js + Laravel developers**
