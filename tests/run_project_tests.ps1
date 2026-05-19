param(
    [string]$BaseUrl = "http://localhost/cinema",
    [string]$PhpPath = "C:\xampp\php\php.exe",
    [string]$MysqlPath = "C:\xampp\mysql\bin\mysql.exe",
    [string]$Database = "cinema_db",
    [string]$MysqlUser = "root",
    [string]$MysqlPassword = ""
)

$ErrorActionPreference = "Stop"

$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$testUserEmail = "test_user_$timestamp@example.com"
$testAdminEmail = "test_admin_$timestamp@example.com"
$testPassword = "test12345"

$testClientId = $null
$testAdminId = $null
$testFilmId = $null
$testSessionId = $null

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "== $Message ==" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Invoke-Mysql {
    param([string]$Query)

    $args = @("-u$MysqlUser")
    if ($MysqlPassword -ne "") {
        $args += "-p$MysqlPassword"
    }
    $args += @("-N", "-B", "-e", $Query)

    & $MysqlPath @args
}

function Invoke-MysqlScalar {
    param([string]$Query)

    Invoke-Mysql $Query | Select-Object -First 1
}

function Assert-Contains {
    param(
        [string]$Text,
        [string]$Pattern,
        [string]$Message
    )

    if (-not $Text.Contains($Pattern)) {
        throw $Message
    }
}

try {
    Write-Host "Cinema project functional test runner" -ForegroundColor Yellow
    Write-Host "Base URL: $BaseUrl"
    Write-Host "Database: $Database"

    if (-not (Test-Path $PhpPath)) {
        throw "PHP executable was not found: $PhpPath"
    }

    if (-not (Test-Path $MysqlPath)) {
        throw "MySQL executable was not found: $MysqlPath"
    }

    Write-Step "PHP syntax check"
    $phpFiles = Get-ChildItem -Path . -Recurse -Filter *.php
    foreach ($file in $phpFiles) {
        $lintResult = & $PhpPath -l $file.FullName 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "PHP syntax error in $($file.FullName): $lintResult"
        }
    }
    Write-Ok "Syntax check passed for $($phpFiles.Count) PHP files"

    Write-Step "Database check"
    $tables = Invoke-Mysql "USE $Database; SHOW TABLES;"
    foreach ($table in @("clients", "films", "sessions", "booking")) {
        if ($tables -notcontains $table) {
            throw "Required table was not found: $table"
        }
    }
    Write-Ok "Required tables found: clients, films, sessions, booking"

    $counts = Invoke-Mysql "USE $Database; SELECT 'clients', COUNT(*) FROM clients UNION ALL SELECT 'films', COUNT(*) FROM films UNION ALL SELECT 'sessions', COUNT(*) FROM sessions UNION ALL SELECT 'booking', COUNT(*) FROM booking;"
    Write-Host "Current table counts:"
    $counts | ForEach-Object { Write-Host "  $_" }

    Write-Step "HTTP page availability"
    $sessionForBooking = Invoke-MysqlScalar "USE $Database; SELECT id FROM sessions WHERE session_date >= CURDATE() ORDER BY session_date, session_time LIMIT 1;"
    if (-not $sessionForBooking) {
        $sessionForBooking = Invoke-MysqlScalar "USE $Database; SELECT id FROM sessions ORDER BY id LIMIT 1;"
    }
    if (-not $sessionForBooking) {
        throw "No session found for booking page test"
    }

    $pages = @(
        "$BaseUrl/index.php",
        "$BaseUrl/login.php",
        "$BaseUrl/register.php",
        "$BaseUrl/booking.php?session_id=$sessionForBooking"
    )

    foreach ($page in $pages) {
        $response = Invoke-WebRequest -Uri $page -UseBasicParsing -TimeoutSec 10
        if ($response.StatusCode -ne 200) {
            throw "$page returned HTTP $($response.StatusCode)"
        }
        Write-Ok "$page returned HTTP 200"
    }

    Write-Step "Anonymous access protection"
    foreach ($protectedPage in @("$BaseUrl/payment.php", "$BaseUrl/my_bookings.php", "$BaseUrl/admin/dashboard.php")) {
        $headers = & curl.exe -I -s $protectedPage
        if (($headers -join "`n") -notmatch "302 Found") {
            throw "$protectedPage did not redirect anonymous user"
        }
        Write-Ok "$protectedPage redirects anonymous user"
    }

    Write-Step "User registration, booking, and payment flow"
    $userWebSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    Invoke-WebRequest `
        -Uri "$BaseUrl/register.php" `
        -Method Post `
        -WebSession $userWebSession `
        -Body @{ email = $testUserEmail; password = $testPassword; confirm_password = $testPassword } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    Invoke-WebRequest `
        -Uri "$BaseUrl/login.php" `
        -Method Post `
        -WebSession $userWebSession `
        -Body @{ email = $testUserEmail; password = $testPassword } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    $testClientId = [int](Invoke-MysqlScalar "USE $Database; SELECT id FROM clients WHERE email = '$testUserEmail';")
    if (-not $testClientId) {
        throw "Temporary user was not created"
    }

    $occupiedSeats = @(Invoke-Mysql "USE $Database; SELECT CONCAT(seat_row, '-', seat_number) FROM booking WHERE session_id = $sessionForBooking;")
    $selectedSeat = $null
    foreach ($row in 1..5) {
        foreach ($seatNumber in 1..8) {
            $candidate = "$row-$seatNumber"
            if ($occupiedSeats -notcontains $candidate) {
                $selectedSeat = $candidate
                break
            }
        }
        if ($selectedSeat) {
            break
        }
    }

    if (-not $selectedSeat) {
        throw "No free seat found for session $sessionForBooking"
    }

    Invoke-WebRequest `
        -Uri "$BaseUrl/booking.php?session_id=$sessionForBooking" `
        -Method Post `
        -WebSession $userWebSession `
        -Body @{ "seats[]" = $selectedSeat } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    $bookingId = [int](Invoke-MysqlScalar "USE $Database; SELECT id FROM booking WHERE client_id = $testClientId AND session_id = $sessionForBooking ORDER BY id DESC LIMIT 1;")
    if (-not $bookingId) {
        throw "Booking row was not created"
    }

    $paymentPage = Invoke-WebRequest -Uri "$BaseUrl/payment.php" -WebSession $userWebSession -UseBasicParsing -TimeoutSec 10
    if (-not ($paymentPage.Content.Contains("payment-card") -or $paymentPage.Content.Contains("К оплате") -or $paymentPage.Content.Contains("Бронь создана"))) {
        throw "Payment page does not contain booking data"
    }

    Write-Ok "User flow passed: client_id=$testClientId; session_id=$sessionForBooking; seat=$selectedSeat; booking_id=$bookingId"

    Write-Step "Admin authorization and management flow"
    $hash = & $PhpPath -r "echo password_hash('$testPassword', PASSWORD_DEFAULT);"
    Invoke-Mysql "USE $Database; INSERT INTO clients (email, password, role) VALUES ('$testAdminEmail', '$hash', 'admin');" | Out-Null
    $testAdminId = [int](Invoke-MysqlScalar "USE $Database; SELECT id FROM clients WHERE email = '$testAdminEmail';")
    if (-not $testAdminId) {
        throw "Temporary admin was not created"
    }

    $adminWebSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    Invoke-WebRequest `
        -Uri "$BaseUrl/login.php" `
        -Method Post `
        -WebSession $adminWebSession `
        -Body @{ email = $testAdminEmail; password = $testPassword } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    $dashboard = Invoke-WebRequest -Uri "$BaseUrl/admin/dashboard.php" -WebSession $adminWebSession -UseBasicParsing -TimeoutSec 10
    Assert-Contains $dashboard.Content "Cinema Admin" "Admin dashboard did not open"

    $testFilmTitle = "test_film_$timestamp"
    Invoke-WebRequest `
        -Uri "$BaseUrl/admin/films.php" `
        -Method Post `
        -WebSession $adminWebSession `
        -Body @{ title = $testFilmTitle; description = "test description"; duration = "100"; price = "300" } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    $testFilmId = [int](Invoke-MysqlScalar "USE $Database; SELECT id FROM films WHERE title = '$testFilmTitle' ORDER BY id DESC LIMIT 1;")
    if (-not $testFilmId) {
        throw "Film was not created from admin panel"
    }

    Invoke-WebRequest `
        -Uri "$BaseUrl/admin/sessions.php" `
        -Method Post `
        -WebSession $adminWebSession `
        -Body @{ add_session = "1"; film_id = "$testFilmId"; session_date = "2026-06-01"; session_time = "12:00"; hall_name = "test_hall" } `
        -UseBasicParsing `
        -TimeoutSec 10 | Out-Null

    $testSessionId = [int](Invoke-MysqlScalar "USE $Database; SELECT id FROM sessions WHERE film_id = $testFilmId AND hall_name = 'test_hall' ORDER BY id DESC LIMIT 1;")
    if (-not $testSessionId) {
        throw "Session was not created from admin panel"
    }

    Write-Ok "Admin flow passed: admin_id=$testAdminId; film_id=$testFilmId; session_id=$testSessionId"

    Write-Step "Test result"
    Write-Host "ALL TESTS PASSED" -ForegroundColor Green
}
finally {
    $cleanup = @()
    if ($testSessionId) {
        $cleanup += "DELETE FROM sessions WHERE id = $testSessionId;"
    }
    if ($testFilmId) {
        $cleanup += "DELETE FROM films WHERE id = $testFilmId;"
    }
    if ($testClientId) {
        $cleanup += "DELETE FROM booking WHERE client_id = $testClientId;"
        $cleanup += "DELETE FROM clients WHERE id = $testClientId;"
    }
    if ($testAdminId) {
        $cleanup += "DELETE FROM clients WHERE id = $testAdminId;"
    }

    if ($cleanup.Count -gt 0) {
        Invoke-Mysql "USE $Database; $($cleanup -join ' ')" | Out-Null
        Write-Host ""
        Write-Host "Temporary test data removed." -ForegroundColor DarkGray
    }
}
