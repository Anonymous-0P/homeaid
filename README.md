<div align="center">

# 🏠 HomeAid – Home Services Platform

Modern PHP/MySQL platform connecting customers with verified local service providers for plumbing, electrical, cleaning, repairs, and more.

<br/>

</div>

## ✨ What’s inside

- Multi-role portals: Customer, Provider, Admin
- Bookings with status flow (pending/accepted/rejected/completed)
- Email notifications (Gmail SMTP via PHPMailer)
- Email verification for new users + resend flow
- Password reset (forgot/reset) via secure tokens
- Provider KYC (Aadhaar number/file) and admin moderation
- Location-aware provider search with sensible fallback
- Nice landing page with About/Contact; contact form sends email

> Stack: PHP 7.4+/8.x, MySQL, Vanilla JS, CSS, PHPMailer, WAMP/XAMPP friendly

---

## 🗂️ Project structure

```
homeaid/
├─ index.php                      # Landing page (About/Contact/CTA)
├─ services.php                   # (Optional) services listing
├─ logout.php                     # Generic logout
├─ check_session.php              # Session status probe
├─ create_admin.php               # Bootstrap an admin user
├─ email_preview.php              # Local email template previews
├─ test_*.php                     # Local test utilities
│
├─ admin/                         # Admin panel
│  ├─ login.php
│  ├─ dashboard.php
│  ├─ manage_users.php
│  ├─ manage_providers.php
│  ├─ manage_services.php
│  ├─ manage_bookings.php
│  ├─ reports.php
│  ├─ delete_user.php | delete_provider.php | delete_service.php
│  └─ …
│
├─ api/                           # Lightweight endpoints
│  ├─ book_service.php
│  ├─ get_providers.php
│  ├─ search_providers.php
│  ├─ notifications.php
│  └─ contact.php                 # Contact form → email (PHPMailer)
│
├─ Auth/                          # Auth utilities & flows
│  ├─ login.php                   # (Generic or helper)
│  ├─ verify_email.php            # Email verification endpoint
│  ├─ forgot_password.php         # Request reset link
│  ├─ reset_password.php          # Complete password reset
│  └─ resend_verification.php     # Resend verification email
│
├─ customer/                      # Customer portal
│  ├─ login.php | register.php
│  ├─ dashboard.php | my_bookings.php | cart.php
│  ├─ book_service.php | confirm_booking.php
│  └─ notifications.php
│
├─ provider/                      # Provider portal
│  ├─ login.php | register.php
│  ├─ dashboard.php | earnings.php | notifications.php
│  ├─ set_rates.php | update_booking.php | edit_profile.php
│  └─ register: Aadhaar upload + location
│
├─ includes/                      # Shared helpers/UI
│  ├─ header.php | footer.php | navbar.php
│  ├─ session_manager.php | session_info.php
│  └─ email_functions.php         # PHPMailer integration + templates
│
├─ config/
│  ├─ db.php                      # DB connection (mysqli)
│  └─ email_config.php            # Gmail SMTP/app password config
│
├─ assets/
│  ├─ css/style.css               # Site-wide styles
│  ├─ js/booking.js               # Booking UI
│  ├─ images/                     # Static images
│  └─ uploads/                    # Provider photos / KYC docs
│
├─ phpmailer/                     # Vendor library (bundled)
└─ database/homeaid.sql           # Base schema
```

---

## 🚀 Setup (Windows + WAMP/XAMPP)

1) Place the folder
- WAMP: `C:\wamp\www\homeaid\`
- XAMPP: `C:\xampp\htdocs\homeaid\`

2) Create DB and import
- Create a database named `homeaid`
- Import `database/homeaid.sql`

3) Configure database
Edit `config/db.php` with your local credentials.

4) Configure email (PHPMailer + Gmail)
Edit `config/email_config.php`:
- Enable 2FA on your Gmail account
- Create an App Password (16 chars)
- Set SMTP_USERNAME to your Gmail and SMTP_PASSWORD to the app password
- Adjust SMTP_DEBUG=0 for production (2 for local troubleshooting)

5) Verify homepage
Open `http://localhost/homeaid/`

6) Create an admin (optional)
Visit `http://localhost/homeaid/create_admin.php` once to bootstrap an admin.

Notes
- Localhost links use HTTP (not HTTPS) automatically.
- The helper `getBaseUrl()` normalizes URLs to your app root, avoiding nested paths.

---

## 🔐 Authentication & verification

- Registration (customer/provider) stores users with `email_verified = 0` and sends a verification link.
- `Auth/verify_email.php` marks the user verified (`email_verified = 1`, `email_verified_at = NOW()`) and auto-redirects to the appropriate login.
- Login gating allows only verified users (with a timestamp) to proceed and shows a link to resend verification if needed.
- Password reset flow:
  - `Auth/forgot_password.php` → stores token and emails a reset link
  - `Auth/reset_password.php` → verifies token and updates password (hash via `password_hash`)

Email sending is centralized in `includes/email_functions.php` using PHPMailer and HTML templates (verification, reset, booking events). For localhost, SSL peer checks are relaxed to work with WAMP.

---

## 📦 Features in detail

- Bookings
  - Customer requests a provider; provider accepts/rejects; status emails are sent to both sides.
  - Customer can track bookings in `customer/my_bookings.php`.

- Provider KYC (Aadhaar)
  - Provider registration accepts Aadhaar number and file upload.
  - File validation and safe storage in `assets/uploads/`.

- Search & location
  - Provider location captured at registration.
  - Customer search can use user-provided location with sensible fallback when geolocation is unavailable.

- Admin moderation
  - Approve/reject/delete users & providers.
  - Manage services and view bookings.

- Email notifications
  - Templates for booking confirmation/acceptance/rejection and status updates.
  - Email verification and password reset templates.

- Contact form → email
  - Landing page contact form posts to `api/contact.php`.
  - Sends message to `SMTP_FROM_EMAIL` with Reply-To set to the sender.
  - Simple 60s per-session cooldown to reduce spam.

---

## 🔌 Key configuration files

- `config/db.php` – MySQL credentials (mysqli)
- `config/email_config.php` – SMTP settings (Gmail):
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USERNAME`, `SMTP_PASSWORD`
  - `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`, `SMTP_DEBUG`, `EMAIL_FALLBACK`
- `includes/email_functions.php` – `sendEmail(...)`, templates, and `getBaseUrl()`

Security notes
- Keep `SMTP_PASSWORD` private. Use environment-specific config when deploying.
- For production, enable HTTPS and remove relaxed SSL options in PHPMailer SMTPOptions.

---

## 🧰 Common tasks

- Resend verification: `Auth/resend_verification.php` (needs email + role)
- View customer bookings: `customer/my_bookings.php`
- Provider rates: `provider/set_rates.php`
- Admin management: `/admin/*`

---

## 🗃️ Database overview (essentials)

- `users` – accounts for all roles (email verification columns included)
- `services` – service catalog
- `provider_services` – provider rates per service
- `bookings` – booking transactions and status
- `notifications` – in-app notifications
- `password_reset_tokens` – password reset tokens
- `email_verification_tokens` – email verification tokens

Some pages include runtime checks to add missing columns/tables when needed for older databases.

---

## 🧪 Local testing helpers

- `email_preview.php`, `test_professional_emails.php`, `test_acceptance_notification.php` – preview/email test pages.
- `check_session.php` – JSON session status (handy for debugging logouts/timeouts).

---

## 🛟 Troubleshooting

SMTP issues (Gmail)
- Ensure 2FA + App Password
- Set `SMTP_DEBUG = 2` to log SMTP in PHP error log
- On localhost, links use HTTP; HTTPS without certificates will fail

Verification link points to a wrong subpath
- `getBaseUrl()` strips nested folders (customer/provider/admin/Auth/includes/api/services) to the app root. Ensure you’re serving from `/homeaid`.

“bind_param() on bool” in registration
- Indicates a failed `prepare()`; check SQL and MySQL version. Use `store_result()/num_rows` for compatibility where `get_result()` isn’t available.

Uploads failing
- Ensure web server can write to `assets/uploads/`.

No providers found in search
- Make sure providers created rates in `provider/set_rates.php` and are active for the service.

---

## 🧭 Roadmap ideas

- Rate limiting for resend verification
- Admin UI to view verification status and trigger resend
- Payment integration (Stripe/PayPal)
- Reviews & ratings
- Real-time chat/updates

---

## 📄 License

Specify your project’s license (e.g., MIT, Apache-2.0, or proprietary). If MIT, add a LICENSE file at the repo root.

---

Last updated: September 6, 2025
