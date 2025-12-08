# API Development Plan

**Project:** Laravel REST API
**Started:** 2025-12-07
**Status:** 🚧 In Progress
**Current Phase:** Phase 4 Complete & Merged | Ready for Phase 5

---

## Overview

Building a complete REST API authentication system using Laravel Sanctum for token-based authentication. This will coexist with the existing Fortify web authentication and provide secure, testable endpoints for mobile apps and third-party integrations.

### Technology Stack

- **Laravel:** 12.x
- **Authentication:** Laravel Sanctum (token-based) + Fortify (web-based)
- **Testing:** Pest 4.x
- **Email Testing:** MailHog (Docker)
- **API Testing:** Postman

---

## Implementation Checklist

### Phase 0: Project Documentation ✅

- [x] **Create API development plan document**
  - File: `API-DEVELOPMENT-PLAN.md`
  - Purpose: Track progress across Claude Code sessions
  - Status: ✅ Created on 2025-12-07

- [x] **Create session handoff template**
  - File: `SESSION-HANDOFF.md`
  - Purpose: Enable session continuity across context limits
  - Status: ✅ Created on 2025-12-07

- [x] **Update CLAUDE.md with new session instructions**
  - Added mandatory steps for new sessions
  - Emphasizes reading handoff docs and using Context7
  - Status: ✅ Updated on 2025-12-07

### Phase 1: Foundation Setup ✅

- [x] **Add HasApiTokens trait to User model**
  - File: [app/Models/User.php](app/Models/User.php)
  - Add `use Laravel\Sanctum\HasApiTokens;` to imports
  - Add trait to class
  - Status: ✅ Completed on 2025-12-08

- [x] **Create UserResource for API responses**
  - Command: `./vendor/bin/sail artisan make:resource Api/UserResource`
  - File: `app/Http/Resources/Api/UserResource.php`
  - Expose: id, name, email, email_verified_at, created_at
  - Status: ✅ Completed on 2025-12-08

- [x] **Create AuthResource for login/register responses**
  - Command: `./vendor/bin/sail artisan make:resource Api/AuthResource`
  - File: `app/Http/Resources/Api/AuthResource.php`
  - Wrap UserResource + add token, token_type fields
  - Status: ✅ Completed on 2025-12-08

- [x] **Configure API exception handling**
  - File: [bootstrap/app.php](bootstrap/app.php)
  - Add JSON error responses for API routes
  - Handle: ValidationException, AuthenticationException, ThrottleRequestsException
  - Status: ✅ Completed on 2025-12-08

- [x] **Configure rate limiter for login**
  - Location: `bootstrap/app.php`
  - Name: `api-login`
  - Limit: 5 attempts per minute per email+IP
  - Status: ✅ Completed on 2025-12-08

### Phase 2: User Registration ✅

- [x] **Create RegisterRequest**
  - Command: `./vendor/bin/sail artisan make:request Api/Auth/RegisterRequest`
  - File: `app/Http/Requests/Api/Auth/RegisterRequest.php`
  - Validation: name, email (unique, lowercase), password (confirmed, min:8)
  - Status: ✅ Completed on 2025-12-08

- [x] **Create RegisterController**
  - Command: `./vendor/bin/sail artisan make:controller Api/Auth/RegisterController`
  - File: `app/Http/Controllers/Api/Auth/RegisterController.php`
  - Method: `store()` - Create user, generate token, return 201
  - Status: ✅ Completed on 2025-12-08

- [x] **Add registration route**
  - File: [routes/api.php](routes/api.php)
  - Route: `POST /api/auth/register`
  - Status: ✅ Completed on 2025-12-08

- [x] **Write registration tests**
  - Command: `./vendor/bin/sail artisan make:test --pest Api/Auth/RegistrationTest`
  - File: `tests/Feature/Api/Auth/RegistrationTest.php`
  - Cover: success, validation errors, duplicate email, password confirmation, lowercase email
  - Status: ✅ Completed on 2025-12-08 (10 comprehensive tests)

- [x] **Run registration tests**
  - Command: `./vendor/bin/sail artisan test --filter=Registration`
  - Status: ✅ All 10 tests passing

### Phase 3: User Login ✅

- [x] **Create LoginRequest**
  - Command: `./vendor/bin/sail artisan make:request Api/Auth/LoginRequest`
  - File: `app/Http/Requests/Api/Auth/LoginRequest.php`
  - Add custom `authenticate()` method with rate limiting
  - Status: ✅ Completed on 2025-12-08

- [x] **Create LoginController**
  - Command: `./vendor/bin/sail artisan make:controller Api/Auth/LoginController`
  - File: `app/Http/Controllers/Api/Auth/LoginController.php`
  - Method: `store()` - Authenticate, generate token, return 200
  - Status: ✅ Completed on 2025-12-08

- [x] **Add login route**
  - File: [routes/api.php](routes/api.php)
  - Route: `POST /api/auth/login`
  - Status: ✅ Completed on 2025-12-08

- [x] **Write login tests**
  - Command: `./vendor/bin/sail artisan make:test --pest Api/Auth/LoginTest`
  - File: `tests/Feature/Api/Auth/LoginTest.php`
  - Cover: success, invalid credentials, rate limiting, validation errors
  - Status: ✅ Completed on 2025-12-08 (8 comprehensive tests)

- [x] **Run login tests**
  - Command: `./vendor/bin/sail artisan test --filter=Login`
  - Status: ✅ All 8 tests passing

### Phase 4: Get Authenticated User ✅

- [x] **Create UserController**
  - Command: `./vendor/bin/sail artisan make:controller Api/Auth/UserController`
  - File: `app/Http/Controllers/Api/Auth/UserController.php`
  - Method: `show()` - Return authenticated user
  - Status: ✅ Completed on 2025-12-08

- [x] **Add user profile route**
  - File: [routes/api.php](routes/api.php)
  - Route: `GET /api/auth/user`
  - Middleware: `auth:sanctum`
  - Status: ✅ Completed on 2025-12-08

- [x] **Write user profile tests**
  - Command: `./vendor/bin/sail artisan make:test --pest Api/Auth/UserTest`
  - File: `tests/Feature/Api/Auth/UserTest.php`
  - Cover: authenticated access, unauthenticated 401, invalid token, login token integration
  - Status: ✅ Completed on 2025-12-08 (5 comprehensive tests)

- [x] **Run user profile tests**
  - Command: `./vendor/bin/sail artisan test --filter=User`
  - Status: ✅ All 5 tests passing

### Phase 5: Logout & Token Management ⏳

- [ ] **Create LogoutController**
  - Command: `./vendor/bin/sail artisan make:controller Api/Auth/LogoutController`
  - File: `app/Http/Controllers/Api/Auth/LogoutController.php`
  - Methods: `destroy()` (current token), `destroyAll()` (all tokens)

- [ ] **Add logout routes**
  - File: [routes/api.php](routes/api.php)
  - Routes: `POST /api/auth/logout`, `DELETE /api/auth/logout/all`

- [ ] **Write logout tests**
  - Command: `./vendor/bin/sail artisan make:test --pest Api/Auth/LogoutTest`
  - File: `tests/Feature/Api/Auth/LogoutTest.php`
  - Cover: single logout, logout all, token revocation

- [ ] **Run logout tests**
  - Command: `./vendor/bin/sail artisan test --filter=Logout`

### Phase 6: Password Recovery via Email ⏳

- [ ] **Add MailHog to Docker setup**
  - File: [compose.yaml](compose.yaml)
  - Add MailHog service with ports 1025 (SMTP) and 8025 (Web UI)

- [ ] **Configure mail settings**
  - File: `.env`
  - Set MAIL_MAILER=smtp, MAIL_HOST=mailhog, MAIL_PORT=1025
  - Verify MailHog UI at <http://localhost:8025>

- [ ] **Create ForgotPasswordRequest**
  - Command: `./vendor/bin/sail artisan make:request Api/Auth/ForgotPasswordRequest`
  - File: `app/Http/Requests/Api/Auth/ForgotPasswordRequest.php`
  - Validation: email (required, email, exists)

- [ ] **Create ResetPasswordRequest**
  - Command: `./vendor/bin/sail artisan make:request Api/Auth/ResetPasswordRequest`
  - File: `app/Http/Requests/Api/Auth/ResetPasswordRequest.php`
  - Validation: email, token, password (confirmed, min:8)

- [ ] **Create PasswordResetController**
  - Command: `./vendor/bin/sail artisan make:controller Api/Auth/PasswordResetController`
  - File: `app/Http/Controllers/Api/Auth/PasswordResetController.php`
  - Methods: `sendResetLink()`, `reset()`

- [ ] **Add password reset routes**
  - File: [routes/api.php](routes/api.php)
  - Routes: `POST /api/auth/forgot-password`, `POST /api/auth/reset-password`
  - Add rate limiting to forgot-password

- [ ] **Write password reset tests**
  - Command: `./vendor/bin/sail artisan make:test --pest Api/Auth/PasswordResetTest`
  - File: `tests/Feature/Api/Auth/PasswordResetTest.php`
  - Cover: send link, reset password, validation, expired token

- [ ] **Run password reset tests**
  - Command: `./vendor/bin/sail artisan test --filter=PasswordReset`

- [ ] **Test in MailHog**
  - Manually test password reset flow
  - Verify emails appear in MailHog UI

### Phase 7: Final Verification ⏳

- [ ] **Update this document**
  - Mark completed phases with ✅
  - Add implementation date
  - Document any issues encountered

- [ ] **Run complete test suite**
  - Command: `./vendor/bin/sail artisan test`
  - All tests must pass

- [ ] **Run Laravel Pint formatter**
  - Command: `./vendor/bin/sail pint --dirty`

- [ ] **Create Postman collection**
  - Collection: "Laravel API Authentication"
  - 7 requests: register, login, get user, logout, logout all, forgot password, reset password

- [ ] **Manual testing in Postman**
  - Test all happy paths
  - Test all error scenarios
  - Test rate limiting
  - Test password reset end-to-end

---

## API Endpoints

| Method | Endpoint | Auth | Description | Status |
|--------|----------|------|-------------|--------|
| POST | /api/auth/register | No | Create user + issue token | ✅ Complete |
| POST | /api/auth/login | No | Authenticate + issue token | ✅ Complete |
| GET | /api/auth/user | Yes | Get authenticated user | ✅ Complete |
| POST | /api/auth/logout | Yes | Revoke current token | ⏳ Pending |
| DELETE | /api/auth/logout/all | Yes | Revoke all user tokens | ⏳ Pending |
| POST | /api/auth/forgot-password | No | Send password reset email | ⏳ Pending |
| POST | /api/auth/reset-password | No | Reset password with token | ⏳ Pending |

---

## Response Format Examples

### Success Response (Login/Register)

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2025-12-07T12:00:00.000000Z"
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

### Validation Error (422)

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

### Authentication Error (401)

```json
{
  "message": "These credentials do not match our records."
}
```

### Rate Limit Error (429)

```json
{
  "message": "Too many login attempts. Please try again in 60 seconds."
}
```

---

## Testing Strategy

### Unit/Feature Tests (Pest)

- All endpoints have comprehensive test coverage
- Tests run automatically: `./vendor/bin/sail artisan test`
- Filter specific tests: `./vendor/bin/sail artisan test --filter=<TestName>`

### Manual Testing (Postman)

- Collection with all endpoints
- Environment variables for base_url and api_token
- Test happy paths and error scenarios

### Email Testing (MailHog)

- Web UI: <http://localhost:8025>
- SMTP: mailhog:1025 (Docker)
- All emails captured, no actual sending

---

## Future Enhancements (Out of Scope)

The following features are planned for future implementation:

- [ ] Email verification via API
- [ ] Two-factor authentication for API
- [ ] Token abilities/permissions system
- [ ] API versioning (v1, v2)
- [ ] Refresh token implementation
- [ ] Social authentication (Socialite) via API
- [ ] Production email service (Mailgun, Postmark, etc.)

---

## Notes

### Architecture Decisions

- **Sanctum for API auth:** Token-based, stateless, perfect for mobile/SPA
- **Fortify for web auth:** Session-based, handles Inertia/React UI
- **Coexistence:** Both systems work independently, no conflicts
- **Token expiration:** Disabled by default (configurable)

### Development Workflow

1. Implement feature (controller, request, resource)
2. Write comprehensive tests
3. Run tests, fix failures
4. Manual test in Postman
5. Run Pint formatter
6. Update this document

### Security Notes

- **HTTPS required in production** - Tokens sent in Authorization header
- **Secure token storage** - Never use localStorage for web clients
- **Rate limiting** - Prevents brute force attacks
- **Password reset tokens** - Expire after 60 minutes
- **MailHog is dev-only** - Switch to production mail service for staging/prod

---

## Progress Log

### 2025-12-07 - Session 1

**Phase 0: Project Documentation**
- ✅ Created comprehensive implementation plan
- ✅ Created API-DEVELOPMENT-PLAN.md for tracking progress
- ✅ Created SESSION-HANDOFF.md template for session continuity
- ✅ Updated CLAUDE.md with new session instructions
- ✅ Phase 0 completed in full

**Next Session:** Start Phase 1 - Foundation Setup

### 2025-12-08 - Session 2

#### Phase 1: Foundation Setup

- ✅ Added git branching workflow instructions to CLAUDE.md
- ✅ Created feature branch: `feature/phase-1-foundation-setup`
- ✅ Added HasApiTokens trait to User model
- ✅ Created UserResource with API response fields (id, name, email, email_verified_at, created_at)
- ✅ Created AuthResource with user/token composition pattern
- ✅ Configured API exception handling in bootstrap/app.php (handles 401, 422, 429, 404, 500)
- ✅ Configured api-login rate limiter (5 attempts/min per email+IP)
- ✅ Ran Pint formatter - all files comply with code style
- ✅ Phase 1 completed in full

#### Merge Status

- ✅ All tests passing (41 tests)
- ✅ Feature branch merged to main via fast-forward merge
- ✅ Feature branch deleted
- ✅ Sanctum configuration and migrations committed
- ✅ 8 commits total (Phase 1 + Sanctum setup)
- **Current branch:** `main`
- **Next session:** Create `feature/phase-2-user-registration` branch and start Phase 2

### 2025-12-08 - Session 3

#### Phase 2: User Registration

- ✅ Created feature branch: `feature/phase-2-user-registration`
- ✅ Searched Context7 for Sanctum and Laravel 12 documentation
- ✅ Created RegisterRequest with comprehensive validation rules (name, email, password)
- ✅ Created RegisterController with store() method
- ✅ Added POST /api/auth/register route
- ✅ Wrote 10 comprehensive registration tests covering:
  - Successful registration with token generation
  - Validation errors (missing fields, invalid formats)
  - Unique email constraint
  - Password confirmation requirement
  - Minimum password length (8 characters)
  - Lowercase email requirement
  - Token authentication verification
- ✅ Ran tests - all 10 tests passing (12 total with existing web registration tests)
- ✅ Ran Pint formatter - all files comply with code style
- ✅ Phase 2 completed in full

#### Files Created
- `app/Http/Requests/Api/Auth/RegisterRequest.php`
- `app/Http/Controllers/Api/Auth/RegisterController.php`
- `tests/Feature/Api/Auth/RegistrationTest.php`

#### Files Modified
- `routes/api.php` - Added registration route
- `API-DEVELOPMENT-PLAN.md` - Marked Phase 2 complete

#### Next Steps
- Run full test suite to ensure no regressions
- Commit changes to feature branch
- Merge feature branch to main
- Ready for Phase 3: User Login

### 2025-12-08 - Session 4

#### Phase 3: User Login

- ✅ Created feature branch: `feature/phase-3-user-login`
- ✅ Searched Context7 for Sanctum authentication and Laravel 12 documentation
- ✅ Created LoginRequest with comprehensive validation rules and custom authenticate() method
- ✅ Implemented built-in rate limiting (5 attempts per minute per email+IP combination)
- ✅ Created LoginController with store() method
- ✅ Added POST /api/auth/login route
- ✅ Wrote 8 comprehensive login tests covering:
  - Successful login with valid credentials
  - Validation errors (missing email, missing password, invalid format)
  - Invalid credentials (wrong password, non-existent user)
  - Token authentication after login
  - Rate limiting after multiple failed attempts
- ✅ Ran login tests - all 8 tests passing
- ✅ Ran Pint formatter - all files comply with code style
- ✅ Phase 3 completed in full

#### Files Created
- `app/Http/Requests/Api/Auth/LoginRequest.php`
- `app/Http/Controllers/Api/Auth/LoginController.php`
- `tests/Feature/Api/Auth/LoginTest.php`

#### Files Modified
- `routes/api.php` - Added login route
- `API-DEVELOPMENT-PLAN.md` - Marked Phase 3 complete
- `SESSION-HANDOFF.md` - Updated for Session 5

#### Merge Status

- ✅ All tests passing on feature branch (59 tests)
- ✅ Feature branch merged to main via fast-forward merge
- ✅ Tests run on main - all 59 tests passing (no regressions)
- ✅ Feature branch deleted
- **Current branch:** `main`
- **Next session:** Create `feature/phase-4-get-authenticated-user` branch and start Phase 4

### 2025-12-08 - Session 5

#### Phase 4: Get Authenticated User

- ✅ Created feature branch: `feature/phase-4-get-authenticated-user`
- ✅ Searched Context7 for Sanctum authentication middleware documentation
- ✅ Created UserController with show() method
- ✅ Added GET /api/auth/user route with auth:sanctum middleware
- ✅ Wrote 5 comprehensive user profile tests covering:
  - Authenticated users can retrieve their profile (using Sanctum::actingAs)
  - Unauthenticated users receive 401
  - Invalid tokens receive 401
  - Login token can be used to access profile (integration test)
  - Correct UserResource data structure validation
- ✅ Ran tests - all 5 tests passing (64 total tests)
- ✅ Ran Pint formatter - all files comply with code style
- ✅ Phase 4 completed in full

#### Files Created in Session 5

- `app/Http/Controllers/Api/Auth/UserController.php`
- `tests/Feature/Api/Auth/UserTest.php`

#### Files Modified in Session 5

- `routes/api.php` - Added GET /api/auth/user route with auth:sanctum middleware
- `API-DEVELOPMENT-PLAN.md` - Marked Phase 4 complete
- `SESSION-HANDOFF.md` - Updated for Session 6

#### Merge Status - Session 5

- ✅ All tests passing on feature branch (64 tests)
- ✅ Feature branch merged to main via fast-forward merge
- ✅ Tests run on main - all 64 tests passing (no regressions)
- ✅ Feature branch deleted
- **Current branch:** `main`
- **Next session:** Create `feature/phase-5-logout-token-management` branch and start Phase 5

---

## Questions & Issues

No blockers or issues encountered during Phase 1, 2, 3, or 4.

---

**Last Updated:** 2025-12-08
**Next Steps:** Create feature branch for Phase 5 and implement logout & token management endpoints
