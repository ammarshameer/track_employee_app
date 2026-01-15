## Root Cause
- The app posts to `http://10.234.150.102/emp_track_2/backend/api/auth/login.php` but the client cannot reach that IP, causing a socket timeout. The base URL is hard-coded at `lib/services/api_service.dart:8`.
- The backend `login.php` only accepts POST (see `backend/api/auth/login.php:16`), so a reachable server should respond quickly; timeouts indicate network reachability, not app logic.

## Verify Backend is Up
- Start Apache and MySQL in XAMPP.
- In a browser on the PC, open `http://localhost/emp_track_2/backend/api/auth/login.php` (expect 405 for GET, confirms route exists).
- If it errors, fix Apache vhost/document root and ensure `backend/config/database.php` points to MySQL (see `backend/config/database.php:7-10`).

## Find Correct Base URL
- Determine your PC’s IPv4 address on the LAN (`ipconfig` → use the Wi‑Fi/Ethernet adapter IPv4 like `192.168.x.x`).
- Confirm the device/emulator can reach `http://<PC_IP>/emp_track_2/backend/api/auth/login.php` in its browser (expect 405).

## Update App Base URL
- Edit `lib/services/api_service.dart:8` to the reachable address:
  - Android emulator: `http://10.0.2.2/emp_track_2/backend/api`.
  - iOS simulator (macOS dev): `http://localhost/emp_track_2/backend/api`.
  - Physical device on same Wi‑Fi: `http://<PC_IP>/emp_track_2/backend/api`.
- No other code change is required; login call uses `'$baseUrl/auth/login.php'` (`lib/services/api_service.dart:13-19`, invoked from `lib/screens/login_screen.dart:75`).

## Allow Network Access
- Ensure PC and device are on the same network and not behind VPN that blocks LAN.
- Open Windows Firewall for Apache HTTP Server or add inbound rule for port `80/TCP`.
- If corporate/VPN blocks 10.x addresses, use a tunneling URL (e.g., ngrok) as the base URL temporarily.

## Validate End-to-End
- From the device/emulator, open the new URL in a browser to confirm reachability.
- Try logging in; on success, the app saves the session (`lib/services/api_service.dart:95-103`) and navigates to the dashboard (`lib/screens/login_screen.dart:77-91`).
- If you still see timeouts, test `backend/test_connection.php` or a simple ping to the base URL to isolate network vs. backend.

## Optional Improvements
- Make the base URL configurable at runtime via an app setting stored in `SharedPreferences` instead of hard-coding.
- Add a preflight connectivity check and clearer error messages (e.g., detect DNS/timeout separately).
- Add short `http.Client` timeouts for faster failures, while keeping reachability as the primary fix.