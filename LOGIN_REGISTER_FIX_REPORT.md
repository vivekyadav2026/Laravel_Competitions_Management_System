# FSALUDOCLUB — LOGIN & REGISTRATION REPAIR REPORT

**Date:** October 15, 2026  
**Status:** Completed & Fully Verified  
**Scope:** Authentication Flow, Registration Visibility, Dual Login (Email / 10-Digit Mobile + Password), Phone OTP Integration, Welcome Bonus & Referral Pipeline.

---

## 1. Executive Summary

The player authentication subsystem for **Fsaludoclub** has been repaired and modernized without altering the underlying database or compromising security:
1. **Prominent "Register Now" Entry Point:** Fixed the missing registration pathway on the login page (`/showlogin`), surfacing an eye-catching, gold-accented call-to-action button and footer navigation that routes directly to `/register`.
2. **Dual Login Capability:** Players can now authenticate seamlessly using either their **registered Email address** or their **10-digit Indian Mobile Number** (+91 format supported), alongside their password.
3. **Preservation of Mobile OTP Flow:** The primary mobile OTP authentication pipeline (`/auth/send-otp` & `/auth/verify-otp`) remains 100% functional with rate-limiting, HSP SMS integration, and 6-box interactive inputs.
4. **Registration Pipeline & Welcome Bonus:** New user registration (`/register`) now accommodates an optional 10-digit Indian mobile number with automatic normalization, enforces uniqueness, issues a random referral code & player UID (`LUDO#####`), and atomically credits the configurable welcome bonus (default: 100 virtual coins) to the authoritative ledger (`ludo_ledger_transactions`).
5. **Admin Access Separation:** Admin credentials (`admin@fsaludoclub.com` / `Admin@12345`) redirect to `/admin/ludo/dashboard`. Unauthorized players attempting to access administrator endpoints are strictly rejected with HTTP 403 Forbidden.

---

## 2. Problem Analysis & Resolutions

### Problem 1: Register Link Missing on Login Page
- **Cause:** In `resources/views/authentication/sign_in.blade.php`, the primary view focused exclusively on OTP and a collapsible password form, omitting any visible anchor or button to `route('register')`.
- **Resolution:** Added a dedicated, high-contrast gold outlined button (`#registerNowBtn`) with the label **"Register Now • Create New Account"** inside a structured options block directly below the mode switcher, linking to `route('register')`.

### Problem 2: Single-Identifier Limitation
- **Cause:** The traditional password login endpoint strictly looked up users via `User::where('email', $request->email)` and failed when users provided their 10-digit mobile number in the login field.
- **Resolution:**
  - Updated the form input label and placeholder to: **"Email / 10-Digit Mobile Number"**.
  - Implemented smart input normalization using `SmsService::normalizePhone($input)`.
  - In `AuthController::login()`, the system resolves the player by either exact email OR normalized 10-digit phone number:
    ```php
    $user = User::where('email', $input)
        ->when($cleanPhone, function ($query, $phone) {
            return $query->orWhere('phone', $phone);
        })
        ->first();
    ```
  - Verifies credentials using `Hash::check($request->password, $user->password)`.
  - Enforces account suspension checks prior to session regeneration.

### Problem 3: Registration Mobile Number Capture
- **Cause:** The registration form (`resources/views/authentication/sign_up.blade.php`) only captured name, email, password, and referral code, omitting mobile numbers.
- **Resolution:**
  - Added an Indian Mobile Number input field with fixed `+91` badge and numeric validation (`pattern="[0-9]*"`, `maxlength="10"`).
  - Enforced Indian phone number normalization and uniqueness check (`User::where('phone', $cleanPhone)->exists()`) in `AuthController::register()`.

---

## 3. Route Map & Controller Architecture

| Route Name | URI | Method | Controller Action | Description |
|---|---|---|---|---|
| `login` | `/showlogin` | GET | `AuthController@showLogin` | Renders dual-mode login UI (Mobile OTP + Password + Register link) |
| `login.post` | `/login` | POST | `AuthController@login` | Authenticates user via Email OR 10-digit phone + Password |
| `register` | `/register` | GET | `AuthController@showRegister` | Displays player registration form |
| `register.post`| `/register` | POST | `AuthController@register` | Validates & registers user, allocates UID and welcome coins |
| `auth.send_otp`| `/auth/send-otp` | POST | `AuthController@sendOtp` | Dispatches SMS OTP with 60s cooldown |
| `auth.verify_otp`| `/auth/verify-otp`| POST | `AuthController@verifyOtp`| Verifies OTP, auto-creates or logs in player |
| `admin.login` | `/admin/ludo/login`| GET | `AuthController@showAdminLogin`| Renders dedicated Admin Console login portal |
| `admin.login.post`| `/admin/ludo/login`| POST | `AuthController@adminLogin`| Authenticates administrative staff with role check |

---

## 4. Input Validation Rules

### Registration (`POST /register`)
```php
$this->validate($request, [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users,email',
    'phone' => 'nullable|string|max:20',
    'password' => 'required|string|min:8',
    'referral_code' => 'nullable|string|max:30',
]);
```
- If `phone` is provided: Must normalize to a valid 10-digit Indian number starting with digits `6, 7, 8, 9` and must not already exist in `users.phone`.

### Password Login (`POST /login`)
```php
$request->validate([
    'email' => 'required|string',
    'password' => 'required|string',
]);
```
- The `email` parameter accepts raw 10-digit phone numbers (`9899112233`), prefixed phone numbers (`+919899112233`, `09899112233`), or standard email addresses.

---

## 5. Security & Account Safeguards

1. **CSRF Protection:** All registration and login forms require authoritative `@csrf` tokens.
2. **Session Regeneration:** Calling `$request->session()->regenerate()` immediately following successful credentials verification to mitigate session fixation attacks.
3. **Suspension Interceptor:** Suspended player accounts (`status === 'suspended'`) are prohibited from authenticating. The system destroys unauthorized sessions and reports the recorded suspension reason.
4. **Role Isolation:** Administrator routes (`/admin/ludo/*`) enforce the `ludo.admin` middleware. Any player with role `normal` or `user` receives HTTP 403 Forbidden.
5. **Welcome Bonus Ledger Integrity:** The welcome bonus of 100 virtual coins is not incremented via raw SQL `UPDATE`, but recorded atomically inside a DB transaction using `LudoLedgerTransaction::recordAdjustment()`.
